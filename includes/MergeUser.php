<?php
use MediaWiki\MediaWikiServices;
use Wikimedia\Rdbms\IDatabase;

/**
 * Contains the actual database backend logic for merging users
 *
 * @suppress PhanUndeclaredMagicConstant https://github.com/phan/phan/issues/2490
 */
class MergeUser {
	/**
	 * @var User
	 */
	private $oldUser, $newUser;

	/**
	 * @var IUserMergeLogger
	 */
	private $logger;

	/** @var integer */
	private $flags;

	const USE_MULTI_COMMIT = 1; // allow begin/commit; useful for jobs or CLI mode

	/**
	 * @param User $oldUser
	 * @param User $newUser
	 * @param IUserMergeLogger $logger
	 * @param int $flags Bitfield (Supports MergeUser::USE_*)
	 */
	public function __construct(
		User $oldUser,
		User $newUser,
		IUserMergeLogger $logger,
		$flags = 0
	) {
		$this->newUser = $newUser;
		$this->oldUser = $oldUser;
		$this->logger = $logger;
		$this->flags = $flags;
	}

	/**
	 * @param User $performer
	 * @param string $fnameTrxOwner
	 */
	public function merge( User $performer, $fnameTrxOwner = __METHOD__ ) {
		$this->mergeEditcount();
		$this->mergeDatabaseTables( $fnameTrxOwner );
		$this->logger->addMergeEntry( $performer, $this->oldUser, $this->newUser );
	}

	/**
	 * @param User $performer
	 * @param callable $msg something that returns a Message object
	 *
	 * @return array Array of failed page moves, see MergeUser::movePages
	 */
	public function delete( User $performer, /* callable */ $msg ) {
		$failed = $this->movePages( $performer, $msg );
		$this->deleteUser();
		$this->logger->addDeleteEntry( $performer, $this->oldUser );

		return $failed;
	}

	/**
	 * Adds edit count of both users
	 */
	private function mergeEditcount() {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->startAtomic( __METHOD__ );

		$totalEdits = $dbw->selectField(
			'user',
			'SUM(user_editcount)',
			[ 'user_id' => [ $this->newUser->getId(), $this->oldUser->getId() ] ],
			__METHOD__
		);

		$totalEdits = (int)$totalEdits;

		# don't run queries if neither user has any edits
		if ( $totalEdits > 0 ) {
			# update new user with total edits
			$dbw->update( 'user',
				[ 'user_editcount' => $totalEdits ],
				[ 'user_id' => $this->newUser->getId() ],
				__METHOD__
			);

			# clear old user's edits
			$dbw->update( 'user',
				[ 'user_editcount' => 0 ],
				[ 'user_id' => $this->oldUser->getId() ],
				__METHOD__
			);
		}

		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * @param IDatabase $dbw
	 * @return void
	 * @suppress PhanTypeMismatchArgument Phan thinks that $newBlock and $oldBlock are both null when
	 *   Block::newFromRow is called, although the previous if/elseif returns if any of them is null.
	 */
	private function mergeBlocks( IDatabase $dbw ) {
		$dbw->startAtomic( __METHOD__ );

		// Pull blocks directly from master
		$qi = Block::getQueryInfo();
		$rows = $dbw->select(
			$qi['tables'],
			$qi['fields'],
			[
				'ipb_user' => [ $this->oldUser->getId(), $this->newUser->getId() ],
			],
			__METHOD__,
			[],
			$qi['joins']
		);

		$newBlock = null;
		$oldBlock = null;
		foreach ( $rows as $row ) {
			if ( (int)$row->ipb_user === $this->oldUser->getId() ) {
				$oldBlock = $row;
			} elseif ( (int)$row->ipb_user === $this->newUser->getId() ) {
				$newBlock = $row;
			}
		}

		if ( !$newBlock && !$oldBlock ) {
			// No one is blocked, yaaay
			$dbw->endAtomic( __METHOD__ );
			return;
		} elseif ( $newBlock && !$oldBlock ) {
			// Only the new user is blocked, so nothing to do.
			$dbw->endAtomic( __METHOD__ );
			return;
		} elseif ( $oldBlock && !$newBlock ) {
			// Just move the old block to the new username
			$dbw->update(
				'ipblocks',
				[ 'ipb_user' => $this->newUser->getId() ],
				[ 'ipb_id' => $oldBlock->ipb_id ],
				__METHOD__
			);
			$dbw->endAtomic( __METHOD__ );
			return;
		}

		// Okay, let's pick the "strongest" block, and re-apply it to
		// the new user.
		$oldBlockObj = Block::newFromRow( $oldBlock );
		$newBlockObj = Block::newFromRow( $newBlock );

		$winner = $this->chooseBlock( $oldBlockObj, $newBlockObj );
		if ( $winner->getId() === $newBlockObj->getId() ) {
			$oldBlockObj->delete();
		} else { // Old user block won
			$newBlockObj->delete(); // Delete current new block
			$dbw->update(
				'ipblocks',
				[ 'ipb_user' => $this->newUser->getId() ],
				[ 'ipb_id' => $winner->getId() ],
				__METHOD__
			);
		}

		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * @param Block $b1
	 * @param Block $b2
	 * @return Block
	 */
	private function chooseBlock( Block $b1, Block $b2 ) {
		// First, see if one is longer than the other.
		if ( $b1->getExpiry() !== $b2->getExpiry() ) {
			// This works for infinite blocks because:
			// "infinity" > "20141024234513"
			if ( $b1->getExpiry() > $b2->getExpiry() ) {
				return $b1;
			} else {
				return $b2;
			}
		}

		// Next check what they block, in order
		$blockProps = [];
		foreach ( [ $b1, $b2 ] as $block ) {
			$blockProps[] = [
				'block' => $block,
				'createaccount' => $block->isCreateAccountBlocked(),
				'sendemail' => $block->isEmailBlocked(),
				'editownusertalk' => !$block->isUsertalkEditAllowed(),
			];
		}
		foreach ( [ 'createaccount', 'sendemail', 'editownusertalk' ] as $action ) {
			if ( $blockProps[0][$action] xor $blockProps[1][$action] ) {
				if ( $blockProps[0][$action] ) {
					return $blockProps[0]['block'];
				} else {
					return $blockProps[1]['block'];
				}
			}
		}

		// Give up, return the second one.
		return $b2;
	}

	private function stageNeedsUser( $stage ) {
		if ( !defined( 'MIGRATION_NEW' ) ) {
			return true;
		}
		if ( !class_exists( ActorMigration::class ) ) {
			return false;
		}

		if ( defined( 'ActorMigration::MIGRATION_STAGE_SCHEMA_COMPAT' ) ) {
			return (bool)( (int)$stage & SCHEMA_COMPAT_WRITE_OLD );
		} else {
			return $stage < MIGRATION_NEW;
		}
	}

	private function stageNeedsActor( $stage ) {
		if ( !defined( 'MIGRATION_NEW' ) ) {
			return false;
		}
		if ( !class_exists( ActorMigration::class ) ) {
			return true;
		}

		if ( defined( 'ActorMigration::MIGRATION_STAGE_SCHEMA_COMPAT' ) ) {
			return (bool)( $stage & SCHEMA_COMPAT_WRITE_NEW );
		} else {
			return $stage > MIGRATION_OLD;
		}
	}

	/**
	 * Function to merge database references from one user to another user
	 *
	 * Merges database references from one user ID or username to another user ID or username
	 * to preserve referential integrity.
	 *
	 * @param string $fnameTrxOwner
	 */
	private function mergeDatabaseTables( $fnameTrxOwner ) {
		global $wgActorTableSchemaMigrationStage;

		// Fields to update with the format:
		// [
		// tableName, idField, textField,
		// 'batchKey' => unique field, 'options' => array(), 'db' => IDatabase
		// 'actorId' => actor ID field,
		// ];
		// textField, batchKey, db, and options are optional
		$updateFields = [
			[ 'archive', 'ar_user', 'ar_user_text', 'batchKey' => 'ar_id', 'actorId' => 'ar_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'revision', 'rev_user', 'rev_user_text', 'batchKey' => 'rev_id', 'actorId' => '',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'filearchive', 'fa_user', 'fa_user_text', 'batchKey' => 'fa_id', 'actorId' => 'fa_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'image', 'img_user', 'img_user_text', 'batchKey' => 'img_name', 'actorId' => 'img_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'oldimage', 'oi_user', 'oi_user_text', 'batchKey' => 'oi_archive_name',
				'actorId' => 'oi_actor', 'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'recentchanges', 'rc_user', 'rc_user_text', 'batchKey' => 'rc_id', 'actorId' => 'rc_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'logging', 'log_user', 'log_user_text', 'batchKey' => 'log_id', 'actorId' => 'log_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'ipblocks', 'ipb_by', 'ipb_by_text', 'batchKey' => 'ipb_id', 'actorId' => 'ipb_by_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage ],
			[ 'watchlist', 'wl_user', 'batchKey' => 'wl_title' ],
			[ 'user_groups', 'ug_user', 'options' => [ 'IGNORE' ] ],
			[ 'user_properties', 'up_user', 'options' => [ 'IGNORE' ] ],
			[ 'user_former_groups', 'ufg_user', 'options' => [ 'IGNORE' ] ],
		];
		if ( $this->stageNeedsActor( $wgActorTableSchemaMigrationStage ) ) {
			$updateFields[] = [
				'revision_actor_temp', 'batchKey' => 'revactor_rev', 'actorId' => 'revactor_actor',
				'actorStage' => $wgActorTableSchemaMigrationStage
			];
		}

		Hooks::run( 'UserMergeAccountFields', [ &$updateFields ] );

		$dbw = wfGetDB( DB_MASTER );
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$ticket = $lbFactory->getEmptyTransactionTicket( __METHOD__ );

		$this->deduplicateWatchlistEntries( $dbw );
		$this->mergeBlocks( $dbw );

		if ( $this->flags & self::USE_MULTI_COMMIT ) {
			// Flush prior writes; this actives the non-transaction path in the loop below.
			$lbFactory->commitMasterChanges( $fnameTrxOwner );
		}

		foreach ( $updateFields as $fieldInfo ) {
			if ( !isset( $fieldInfo[1] ) ) {
				// Actors only
				continue;
			}

			$options = isset( $fieldInfo['options'] ) ? $fieldInfo['options'] : [];
			unset( $fieldInfo['options'] );
			$db = isset( $fieldInfo['db'] ) ? $fieldInfo['db'] : $dbw;
			unset( $fieldInfo['db'] );
			$tableName = array_shift( $fieldInfo );
			$idField = array_shift( $fieldInfo );
			$keyField = isset( $fieldInfo['batchKey'] ) ? $fieldInfo['batchKey'] : null;
			unset( $fieldInfo['batchKey'] );

			if ( isset( $fieldInfo['actorId'] ) && !$this->stageNeedsUser( $fieldInfo['actorStage'] ) ) {
				continue;
			}
			unset( $fieldInfo['actorId'], $fieldInfo['actorStage'] );

			if ( $db->trxLevel() || $keyField === null ) {
				// Can't batch/wait when in a transaction or when no batch key is given
				$db->update(
					$tableName,
					[ $idField => $this->newUser->getId() ]
						+ array_fill_keys( $fieldInfo, $this->newUser->getName() ),
					[ $idField => $this->oldUser->getId() ],
					__METHOD__,
					$options
				);
			} else {
				$limit = 200;
				do {
					$checkSince = microtime( true );
					// Note that UPDATE with ORDER BY + LIMIT is not well supported.
					// Grab a batch of values on a mostly unique column for this user ID.
					$res = $db->select(
						$tableName,
						[ $keyField ],
						[ $idField => $this->oldUser->getId() ],
						__METHOD__,
						[ 'LIMIT' => $limit ]
					);
					$keyValues = [];
					foreach ( $res as $row ) {
						$keyValues[] = $row->$keyField;
					}
					// Update only those rows with the given column values
					if ( count( $keyValues ) ) {
						$db->update(
							$tableName,
							[ $idField => $this->newUser->getId() ]
								+ array_fill_keys( $fieldInfo, $this->newUser->getName() ),
							[ $idField => $this->oldUser->getId(), $keyField => $keyValues ],
							__METHOD__,
							$options
						);
					}
					// Wait for replication to catch up
					$opts = [ 'ifWritesSince' => $checkSince ];
					$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket, $opts );
				} while ( count( $keyValues ) >= $limit );
			}
		}

		if ( $this->stageNeedsActor( $wgActorTableSchemaMigrationStage ) &&
			$this->oldUser->getActorId()
		) {
			$oldActorId = $this->oldUser->getActorId();
			$newActorId = $this->newUser->getActorId( $db );

			foreach ( $updateFields as $fieldInfo ) {
				if ( empty( $fieldInfo['actorId'] ) || !$this->stageNeedsActor( $fieldInfo['actorStage'] ) ) {
					continue;
				}

				$options = isset( $fieldInfo['options'] ) ? $fieldInfo['options'] : [];
				$db = isset( $fieldInfo['db'] ) ? $fieldInfo['db'] : $dbw;
				$tableName = array_shift( $fieldInfo );
				$idField = $fieldInfo['actorId'];
				$keyField = isset( $fieldInfo['batchKey'] ) ? $fieldInfo['batchKey'] : null;

				if ( $db->trxLevel() || $keyField === null ) {
					// Can't batch/wait when in a transaction or when no batch key is given
					$db->update(
						$tableName,
						[ $idField => $newActorId ],
						[ $idField => $oldActorId ],
						__METHOD__,
						$options
					);
				} else {
					$limit = 200;
					do {
						$checkSince = microtime( true );
						// Note that UPDATE with ORDER BY + LIMIT is not well supported.
						// Grab a batch of values on a mostly unique column for this user ID.
						$res = $db->select(
							$tableName,
							[ $keyField ],
							[ $idField => $oldActorId ],
							__METHOD__,
							[ 'LIMIT' => $limit ]
						);
						$keyValues = [];
						foreach ( $res as $row ) {
							$keyValues[] = $row->$keyField;
						}
						// Update only those rows with the given column values
						if ( count( $keyValues ) ) {
							$db->update(
								$tableName,
								[ $idField => $newActorId ],
								[ $idField => $oldActorId, $keyField => $keyValues ],
								__METHOD__,
								$options
							);
						}
						// Wait for replication to catch up
						$opts = [ 'ifWritesSince' => $checkSince ];
						$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket, $opts );
					} while ( count( $keyValues ) >= $limit );
				}
			}
		}

		$dbw->delete( 'user_newtalk', [ 'user_id' => $this->oldUser->getId() ] );

		Hooks::run( 'MergeAccountFromTo', [ &$this->oldUser, &$this->newUser ] );
	}

	/**
	 * Deduplicate watchlist entries
	 * which old (merge-from) and new (merge-to) users are watching
	 *
	 * @param IDatabase $dbw
	 */
	private function deduplicateWatchlistEntries( $dbw ) {
		$dbw->startAtomic( __METHOD__ );

		// Get all titles both watched by the old and new user accounts.
		// Avoid using self-joins as this fails on temporary tables (e.g. unit tests).
		// See https://bugs.mysql.com/bug.php?id=10327.
		$titlesToDelete = [];
		$res = $dbw->select(
			'watchlist',
			[ 'wl_namespace', 'wl_title' ],
			[ 'wl_user' => $this->oldUser->getId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);
		foreach ( $res as $row ) {
			$titlesToDelete[$row->wl_namespace . "|" . $row->wl_title] = false;
		}
		$res = $dbw->select(
			'watchlist',
			[ 'wl_namespace', 'wl_title' ],
			[ 'wl_user' => $this->newUser->getId() ],
			__METHOD__,
			[ 'FOR UPDATE' ]
		);
		foreach ( $res as $row ) {
			$key = $row->wl_namespace . "|" . $row->wl_title;
			if ( isset( $titlesToDelete[$key] ) ) {
				$titlesToDelete[$key] = true;
			}
		}
		$dbw->freeResult( $res );
		$titlesToDelete = array_filter( $titlesToDelete );

		$conds = [];
		foreach ( array_keys( $titlesToDelete ) as $tuple ) {
			list( $ns, $dbKey ) = explode( "|", $tuple, 2 );
			$conds[] = $dbw->makeList(
				[
					'wl_user' => $this->oldUser->getId(),
					'wl_namespace' => $ns,
					'wl_title' => $dbKey
				],
				LIST_AND
			);
		}

		if ( count( $conds ) ) {
			# Perform a multi-row delete
			$dbw->delete(
				'watchlist',
				$dbw->makeList( $conds, LIST_OR ),
				__METHOD__
			);
		}

		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * Function to merge user pages
	 *
	 * Deletes all pages when merging to Anon
	 * Moves user page when the target user page does not exist or is empty
	 * Deletes redirect if nothing links to old page
	 * Deletes the old user page when the target user page exists
	 *
	 * @todo This code is a duplicate of Renameuser and GlobalRename
	 * @suppress PhanParamTooMany Several calls to $message, which is a variadic closure
	 *
	 * @param User $performer
	 * @param callable $msg Function that returns a Message object
	 * @return array Array of old name (string) => new name (Title) where the move failed
	 */
	private function movePages( User $performer, /* callable */ $msg ) {
		global $wgContLang, $wgUser;

		$oldusername = trim( str_replace( '_', ' ', $this->oldUser->getName() ) );
		$oldusername = Title::makeTitle( NS_USER, $oldusername );
		$newusername = Title::makeTitleSafe( NS_USER, $wgContLang->ucfirst( $this->newUser->getName() ) );

		# select all user pages and sub-pages
		$dbr = wfGetDB( DB_REPLICA );
		$pages = $dbr->select(
			'page',
			[ 'page_namespace', 'page_title' ],
			[
				'page_namespace' => [ NS_USER, NS_USER_TALK ],
				'page_title' . $dbr->buildLike( $oldusername->getDBkey() . '/', $dbr->anyString() )
					. ' OR page_title = ' . $dbr->addQuotes( $oldusername->getDBkey() ),
			]
		);

		$message = function ( /* ... */ ) use ( $msg ) {
			return call_user_func_array( $msg, func_get_args() );
		};

		// Need to set $wgUser to attribute log properly.
		$oldUser = $wgUser;
		$wgUser = $performer;

		$failedMoves = [];
		foreach ( $pages as $row ) {
			$oldPage = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			$newPage = Title::makeTitleSafe( $row->page_namespace,
				preg_replace( '!^[^/]+!', $newusername->getDBkey(), $row->page_title ) );

			if ( $this->newUser->getName() === 'Anonymous' ) { # delete ALL old pages
				if ( $oldPage->exists() ) {
					$error = '';
					$oldPageArticle = new Article( $oldPage, 0 );
					$oldPageArticle->doDeleteArticle(
						$message( 'usermerge-autopagedelete' )->inContentLanguage()->text(),
						false, null, null, $error, true
					);
				}
			} elseif ( $newPage->exists()
				&& !$oldPage->isValidMoveTarget( $newPage )
				&& $newPage->getLength() > 0
			) {
				# delete old pages that can't be moved
				$error = '';
				$oldPageArticle = new Article( $oldPage, 0 );
				$oldPageArticle->doDeleteArticle(
					$message( 'usermerge-autopagedelete' )->inContentLanguage()->text(),
					false, null, null, $error, true
				);

			} else { # move content to new page
				# delete target page if it exists and is blank
				if ( $newPage->exists() ) {
					$error = '';
					$newPageArticle = new Article( $newPage, 0 );
					$newPageArticle->doDeleteArticle(
						$message( 'usermerge-autopagedelete' )->inContentLanguage()->text(),
						false, null, null, $error, true
					);
				}

				# move to target location
				$errors = $oldPage->moveTo(
					$newPage,
					false,
					$message(
						'usermerge-move-log',
						$oldusername->getText(),
						$newusername->getText() )->inContentLanguage()->text()
				);
				if ( $errors !== true ) {
					$failedMoves[$oldPage->getPrefixedText()] = $newPage;
				}

				# check if any pages link here
				$res = $dbr->selectField( 'pagelinks',
					'pl_title',
					[ 'pl_title' => $this->oldUser->getName() ],
					__METHOD__
				);
				if ( !$dbr->numRows( $res ) ) {
					# nothing links here, so delete unmoved page/redirect
					$error = '';
					$oldPageArticle = new Article( $oldPage, 0 );
					$oldPageArticle->doDeleteArticle(
						$message( 'usermerge-autopagedelete' )->inContentLanguage()->text(),
						false, null, null, $error, true
					);
				}
			}
		}

		$wgUser = $oldUser;

		return $failedMoves;
	}

	/**
	 * Function to delete users following a successful mergeUser call.
	 *
	 * Removes rows from the user, user_groups, user_properties
	 * and user_former_groups tables.
	 */
	private function deleteUser() {
		$dbw = wfGetDB( DB_MASTER );

		/**
		 * Format is: table => user_id column
		 *
		 * If you want it to use a different db object:
		 * table => array( user_id colum, 'db' => IDatabase );
		 */
		$tablesToDelete = [
			'user_groups' => 'ug_user',
			'user_properties' => 'up_user',
			'user_former_groups' => 'ufg_user',
		];

		Hooks::run( 'UserMergeAccountDeleteTables', [ &$tablesToDelete ] );

		// Make sure these are always set and last
		if ( $dbw->tableExists( 'actor', __METHOD__ ) ) {
			$tablesToDelete['actor'] = 'actor_user';
		}
		$tablesToDelete['user'] = 'user_id';

		foreach ( $tablesToDelete as $table => $field ) {
			// Check if a different database object was passed (Echo or Flow)
			if ( is_array( $field ) ) {
				$db = isset( $field['db'] ) ? $field['db'] : $dbw;
				$field = $field[0];
			} else {
				$db = $dbw;
			}
			$db->delete(
				$table,
				[ $field => $this->oldUser->getId() ]
			);
		}

		Hooks::run( 'DeleteAccount', [ &$this->oldUser ] );

		DeferredUpdates::addUpdate( SiteStatsUpdate::factory( [ 'users' => -1 ] ) );
	}
}
