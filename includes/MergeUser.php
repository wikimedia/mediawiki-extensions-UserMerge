<?php

use MediaWiki\Block\DatabaseBlock;
use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Deferred\SiteStatsUpdate;
use MediaWiki\Extension\UserMerge\Hooks\HookRunner;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IExpression;
use Wikimedia\Rdbms\IMaintainableDatabase;
use Wikimedia\Rdbms\LikeValue;

/**
 * Contains the actual database backend logic for merging users
 */
class MergeUser {
	/**
	 * @var User
	 */
	private $oldUser;
	/**
	 * @var User
	 */
	private $newUser;

	/**
	 * @var IUserMergeLogger
	 */
	private $logger;

	/**
	 * @var DatabaseBlockStore
	 */
	private $blockStore;

	/** @var int */
	private $flags;

	// allow begin/commit; useful for jobs or CLI mode
	public const USE_MULTI_COMMIT = 1;

	/**
	 * @param User $oldUser
	 * @param User $newUser
	 * @param IUserMergeLogger $logger
	 * @param DatabaseBlockStore $blockStore
	 * @param int $flags Bitfield (Supports MergeUser::USE_*)
	 */
	public function __construct(
		User $oldUser,
		User $newUser,
		IUserMergeLogger $logger,
		DatabaseBlockStore $blockStore,
		$flags = 0
	) {
		$this->newUser = $newUser;
		$this->oldUser = $oldUser;
		$this->logger = $logger;
		$this->blockStore = $blockStore;
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
	public function delete( User $performer, $msg ) {
		$failed = $this->movePages( $performer, $msg );
		$this->deleteUser();
		$this->logger->addDeleteEntry( $performer, $this->oldUser );

		return $failed;
	}

	/**
	 * Adds edit count of both users
	 */
	private function mergeEditcount() {
		$dbw = MediaWikiServices::getInstance()
			->getConnectionProvider()
			->getPrimaryDatabase();
		$dbw->startAtomic( __METHOD__ );

		$totalEdits = $dbw->newSelectQueryBuilder()
			->select( 'SUM(user_editcount)' )
			->from( 'user' )
			->where( [ 'user_id' => [ $this->newUser->getId(), $this->oldUser->getId() ] ] )
			->caller( __METHOD__ )
			->fetchField();

		$totalEdits = (int)$totalEdits;

		# don't run queries if neither user has any edits
		if ( $totalEdits > 0 ) {
			# update new user with total edits
			$dbw->newUpdateQueryBuilder()
				->update( 'user' )
				->set( [ 'user_editcount' => $totalEdits ] )
				->where( [ 'user_id' => $this->newUser->getId() ] )
				->caller( __METHOD__ )
				->execute();

			# clear old user's edits
			$dbw->newUpdateQueryBuilder()
				->update( 'user' )
				->set( [ 'user_editcount' => 0 ] )
				->where( [ 'user_id' => $this->oldUser->getId() ] )
				->caller( __METHOD__ )
				->execute();
		}

		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * @param IDatabase $dbw
	 * @return void
	 */
	private function mergeBlocks( IDatabase $dbw ) {
		$dbw->startAtomic( __METHOD__ );

		// Pull blocks directly from primary
		$oldBlocks = $this->blockStore->newListFromConds(
			[ 'bt_user' => $this->oldUser->getId() ],
			true, true
		);
		$newBlocks = $this->blockStore->newListFromConds(
			[ 'bt_user' => $this->newUser->getId() ],
			true, true
		);

		if ( !$oldBlocks ) {
			// No one is blocked or
			// Only the new user is blocked, so nothing to do.
			$dbw->endAtomic( __METHOD__ );
			return;
		}
		if ( !$newBlocks ) {
			// Just move the old blocks to the new username
			foreach ( $oldBlocks as $block ) {
				$this->blockStore->updateTarget( $block, $this->newUser );
			}
			$dbw->endAtomic( __METHOD__ );
			return;
		}

		// Okay, let's pick the "strongest" block, and re-apply it to
		// the new user.
		$oldBlockObj = reset( $oldBlocks );
		$newBlockObj = reset( $newBlocks );
		$winner = $this->chooseBlock( $oldBlockObj, $newBlockObj );
		if ( $winner->getId() === $newBlockObj->getId() ) {
			$oldBlockObj->delete();
		} else {
			// Old user block won
			// Delete current new block
			$newBlockObj->delete();
			$this->blockStore->updateTarget( $oldBlockObj, $this->newUser );
		}

		$dbw->endAtomic( __METHOD__ );
	}

	/**
	 * @param DatabaseBlock $b1
	 * @param DatabaseBlock $b2
	 * @return DatabaseBlock
	 */
	private function chooseBlock( DatabaseBlock $b1, DatabaseBlock $b2 ) {
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

	/**
	 * @param int $stage
	 *
	 * @return bool
	 */
	private function stageNeedsUser( $stage ) {
		if ( !defined( 'MIGRATION_NEW' ) ) {
			return true;
		}

		if ( defined( 'ActorMigration::MIGRATION_STAGE_SCHEMA_COMPAT' ) ) {
			return (bool)( (int)$stage & SCHEMA_COMPAT_WRITE_OLD );
		} else {
			return $stage < MIGRATION_NEW;
		}
	}

	/**
	 * @param int $stage
	 *
	 * @return bool
	 */
	private function stageNeedsActor( $stage ) {
		if ( !defined( 'MIGRATION_NEW' ) ) {
			return false;
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
		// Fields to update with the format:
		// [
		// tableName, idField, textField,
		// 'batchKey' => unique field, 'options' => array(), 'db' => IDatabase
		// 'actorId' => actor ID field,
		// 'actorStage' => actor schema migration stage
		// ];
		// textField, batchKey, db, and options are optional
		$updateFields = [
			[ 'archive', 'batchKey' => 'ar_id', 'actorId' => 'ar_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'revision', 'batchKey' => 'rev_id', 'actorId' => 'rev_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'filearchive', 'batchKey' => 'fa_id', 'actorId' => 'fa_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'image', 'batchKey' => 'img_name', 'actorId' => 'img_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'oldimage', 'batchKey' => 'oi_archive_name', 'actorId' => 'oi_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'recentchanges', 'batchKey' => 'rc_id', 'actorId' => 'rc_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'logging', 'batchKey' => 'log_id', 'actorId' => 'log_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'block', 'batchKey' => 'bl_id', 'actorId' => 'bl_by_actor',
				'actorStage' => SCHEMA_COMPAT_NEW ],
			[ 'watchlist', 'wl_user', 'batchKey' => 'wl_title' ],
			[ 'user_groups', 'ug_user', 'options' => [ 'IGNORE' ] ],
			[ 'user_properties', 'up_user', 'options' => [ 'IGNORE' ] ],
			[ 'user_former_groups', 'ufg_user', 'options' => [ 'IGNORE' ] ],
			[ 'revision_actor_temp', 'batchKey' => 'revactor_rev', 'actorId' => 'revactor_actor',
				'actorStage' => SCHEMA_COMPAT_TEMP ],
		];

		$services = MediaWikiServices::getInstance();
		$hookRunner = new HookRunner( $services->getHookContainer() );
		$hookRunner->onUserMergeAccountFields( $updateFields );

		$lbFactory = $services->getDBLoadBalancerFactory();
		$dbw = $lbFactory->getPrimaryDatabase();
		$ticket = $lbFactory->getEmptyTransactionTicket( __METHOD__ );

		$this->deduplicateWatchlistEntries( $dbw );
		$this->mergeBlocks( $dbw );

		if ( $this->flags & self::USE_MULTI_COMMIT ) {
			// Flush prior writes; this actives the non-transaction path in the loop below.
			$lbFactory->commitPrimaryChanges( $fnameTrxOwner );
		}

		foreach ( $updateFields as $fieldInfo ) {
			if ( !isset( $fieldInfo[1] ) ) {
				// Actors only
				continue;
			}

			$options = $fieldInfo['options'] ?? [];
			unset( $fieldInfo['options'] );
			/**
			 * @var IMaintainableDatabase
			 */
			$db = $fieldInfo['db'] ?? $dbw;
			unset( $fieldInfo['db'] );
			$tableName = array_shift( $fieldInfo );
			$idField = array_shift( $fieldInfo );
			$keyField = $fieldInfo['batchKey'] ?? null;
			unset( $fieldInfo['batchKey'] );

			if ( isset( $fieldInfo['actorId'] ) && isset( $fieldInfo['actorStage'] ) &&
				!$this->stageNeedsUser( $fieldInfo['actorStage'] )
			) {
				continue;
			}
			unset( $fieldInfo['actorId'], $fieldInfo['actorStage'] );

			if (
				$db instanceof IMaintainableDatabase &&
				(
					!$db->tableExists( $tableName, __METHOD__ ) ||
					!$db->fieldExists( $tableName, $idField, __METHOD__ )
				)
			) {
				continue;
			}

			if ( $db->trxLevel() || $keyField === null ) {
				// Can't batch/wait when in a transaction or when no batch key is given
				$db->newUpdateQueryBuilder()
					->update( $tableName )
					->set( [ $idField => $this->newUser->getId() ]
						+ array_fill_keys( $fieldInfo, $this->newUser->getName() ) )
					->where( [ $idField => $this->oldUser->getId() ] )
					->options( $options )
					->caller( __METHOD__ )
					->execute();
			} else {
				$limit = 200;
				do {
					$checkSince = microtime( true );
					// Note that UPDATE with ORDER BY + LIMIT is not well supported.
					// Grab a batch of values on a mostly unique column for this user ID.
					$res = $db->newSelectQueryBuilder()
						->select( $keyField )
						->from( $tableName )
						->where( [ $idField => $this->oldUser->getId() ] )
						->limit( $limit )
						->caller( __METHOD__ )
						->fetchResultSet();
					$keyValues = [];
					foreach ( $res as $row ) {
						$keyValues[] = $row->$keyField;
					}
					// Update only those rows with the given column values
					if ( count( $keyValues ) ) {
						$db->newUpdateQueryBuilder()
							->update( $tableName )
							->set( [ $idField => $this->newUser->getId() ]
								+ array_fill_keys( $fieldInfo, $this->newUser->getName() ) )
							->where( [ $idField => $this->oldUser->getId(), $keyField => $keyValues ] )
							->options( $options )
							->caller( __METHOD__ )
							->execute();
					}
					// Wait for replication to catch up
					$opts = [ 'ifWritesSince' => $checkSince ];
					$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket, $opts );
				} while ( count( $keyValues ) >= $limit );
			}
		}

		if ( $this->oldUser->getActorId() ) {
			$oldActorId = $this->oldUser->getActorId();
			$newActorId = MediaWikiServices::getInstance()
				->getActorNormalization()
				->acquireActorId( $this->newUser, $dbw );

			foreach ( $updateFields as $fieldInfo ) {
				if ( empty( $fieldInfo['actorId'] ) || empty( $fieldInfo['actorStage'] ) ||
					!$this->stageNeedsActor( $fieldInfo['actorStage'] )
				) {
					continue;
				}

				$options = $fieldInfo['options'] ?? [];
				$db = $fieldInfo['db'] ?? $dbw;
				$tableName = array_shift( $fieldInfo );
				$idField = $fieldInfo['actorId'];
				$keyField = $fieldInfo['batchKey'] ?? null;

				if (
					$db instanceof IMaintainableDatabase &&
					(
						!$db->tableExists( $tableName, __METHOD__ ) ||
						!$db->fieldExists( $tableName, $idField, __METHOD__ )
					)
				) {
					continue;
				}

				if ( $db->trxLevel() || $keyField === null ) {
					// Can't batch/wait when in a transaction or when no batch key is given
					$db->newUpdateQueryBuilder()
						->update( $tableName )
						->set( [ $idField => $newActorId ] )
						->where( [ $idField => $oldActorId ] )
						->options( $options )
						->caller( __METHOD__ )
						->execute();
				} else {
					$limit = 200;
					do {
						$checkSince = microtime( true );
						// Note that UPDATE with ORDER BY + LIMIT is not well supported.
						// Grab a batch of values on a mostly unique column for this user ID.
						$res = $db->newSelectQueryBuilder()
							->select( $keyField )
							->from( $tableName )
							->where( [ $idField => $oldActorId ] )
							->limit( $limit )
							->caller( __METHOD__ )
							->fetchResultSet();
						$keyValues = [];
						foreach ( $res as $row ) {
							$keyValues[] = $row->$keyField;
						}
						// Update only those rows with the given column values
						if ( count( $keyValues ) ) {
							$db->newUpdateQueryBuilder()
								->update( $tableName )
								->set( [ $idField => $newActorId ] )
								->where( [ $idField => $oldActorId, $keyField => $keyValues ] )
								->options( $options )
								->caller( __METHOD__ )
								->execute();
						}
						// Wait for replication to catch up
						$opts = [ 'ifWritesSince' => $checkSince ];
						$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket, $opts );
					} while ( count( $keyValues ) >= $limit );
				}
			}
		}

		$dbw->newDeleteQueryBuilder()
			->deleteFrom( 'user_newtalk' )
			->where( [ 'user_id' => $this->oldUser->getId() ] )
			->caller( __METHOD__ )
			->execute();
		$this->oldUser->clearInstanceCache();
		$this->newUser->clearInstanceCache();

		$hookRunner->onMergeAccountFromTo( $this->oldUser, $this->newUser );
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
		$res = $dbw->newSelectQueryBuilder()
			->select( [ 'wl_namespace', 'wl_title' ] )
			->from( 'watchlist' )
			->where( [ 'wl_user' => $this->oldUser->getId() ] )
			->forUpdate()
			->caller( __METHOD__ )
			->fetchResultSet();
		foreach ( $res as $row ) {
			$titlesToDelete[$row->wl_namespace . "|" . $row->wl_title] = false;
		}
		$res = $dbw->newSelectQueryBuilder()
			->select( [ 'wl_namespace', 'wl_title' ] )
			->from( 'watchlist' )
			->where( [ 'wl_user' => $this->newUser->getId() ] )
			->forUpdate()
			->caller( __METHOD__ )
			->fetchResultSet();
		foreach ( $res as $row ) {
			$key = $row->wl_namespace . "|" . $row->wl_title;
			if ( isset( $titlesToDelete[$key] ) ) {
				$titlesToDelete[$key] = true;
			}
		}
		$titlesToDelete = array_filter( $titlesToDelete );

		$conds = [];
		foreach ( array_keys( $titlesToDelete ) as $tuple ) {
			[ $ns, $dbKey ] = explode( "|", $tuple, 2 );
			$conds[] = $dbw->andExpr( [
				'wl_user' => $this->oldUser->getId(),
				'wl_namespace' => $ns,
				'wl_title' => $dbKey
			] );
		}

		if ( count( $conds ) ) {
			# Perform a multi-row delete
			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'watchlist' )
				->where( $dbw->orExpr( $conds ) )
				->caller( __METHOD__ )
				->execute();
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
	 *
	 * @param User $performer
	 * @param callable $msg Function that returns a Message object
	 * @return array Array of old name (string) => new name (Title) where the move failed
	 */
	private function movePages( User $performer, $msg ) {
		$contLang = MediaWikiServices::getInstance()->getContentLanguage();

		$oldusername = trim( str_replace( '_', ' ', $this->oldUser->getName() ) );
		$oldusername = Title::makeTitle( NS_USER, $oldusername );
		$newusername = Title::makeTitleSafe( NS_USER, $contLang->ucfirst( $this->newUser->getName() ) );

		# select all user pages and sub-pages
		$dbr = MediaWikiServices::getInstance()
			->getConnectionProvider()
			->getReplicaDatabase();
		$pages = $dbr->newSelectQueryBuilder()
			->select( [ 'page_namespace', 'page_title' ] )
			->from( 'page' )
			->where( [
				'page_namespace' => [ NS_USER, NS_USER_TALK ],
				$dbr->expr( 'page_title', IExpression::LIKE,
					new LikeValue( $oldusername->getDBkey() . '/', $dbr->anyString() )
				)->or( 'page_title', '=', $oldusername->getDBkey() ),
			] )
			->caller( __METHOD__ )
			->fetchResultSet();

		$failedMoves = [];
		foreach ( $pages as $row ) {
			$oldPage = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			$newPage = Title::makeTitleSafe( $row->page_namespace,
				preg_replace( '!^[^/]+!', $newusername->getDBkey(), $row->page_title ) );

			if ( $this->newUser->getName() === 'Anonymous' ) {
				# delete ALL old pages
				if ( $oldPage->exists() ) {
					$this->deletePage( $msg, $performer, $oldPage );
				}
			} elseif ( $newPage->exists()
				&& !MediaWikiServices::getInstance()
					->getMovePageFactory()
					->newMovePage( $oldPage, $newPage )
					->isValidMove()
					->isOk()
				&& $newPage->getLength() > 0
			) {
				# delete old pages that can't be moved
				$this->deletePage( $msg, $performer, $oldPage );
			} else {
				# move content to new page
				# delete target page if it exists and is blank
				if ( $newPage->exists() ) {
					$this->deletePage( $msg, $performer, $newPage );
				}

				# move to target location
				$status = MediaWikiServices::getInstance()
					->getMovePageFactory()
					->newMovePage( $oldPage, $newPage )
					->move(
						$performer,
						$msg(
							'usermerge-move-log',
							$oldusername->getText(),
							$newusername->getText() )->inContentLanguage()->text()
					);
				if ( !$status->isOk() ) {
					$failedMoves[$oldPage->getPrefixedText()] = $newPage;
				}

				# check if any pages link here
				$res = $oldPage->getLinksTo( [ 'limit' => 1 ] );
				if ( !$res ) {
					# nothing links here, so delete unmoved page/redirect
					$this->deletePage( $msg, $performer, $oldPage );
				}
			}
		}

		return $failedMoves;
	}

	/**
	 * Helper to delete pages
	 *
	 * @param callable $msg
	 * @param User $user
	 * @param Title $title
	 */
	private function deletePage( $msg, User $user, Title $title ) {
		$wikipage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
		$reason = $msg( 'usermerge-autopagedelete' )->inContentLanguage()->text();
		$error = '';
		$wikipage->doDeleteArticleReal(
			$reason,
			$user,
			false,
			// Unused
			null,
			$error,
			// Unused
			null,
			[],
			'delete',
			true
		);
	}

	/**
	 * Function to delete users following a successful mergeUser call.
	 *
	 * Removes rows from the user, user_groups, user_properties
	 * and user_former_groups tables.
	 */
	private function deleteUser() {
		$dbw = MediaWikiServices::getInstance()
			->getConnectionProvider()
			->getPrimaryDatabase();

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

		$hookRunner = new HookRunner( MediaWikiServices::getInstance()->getHookContainer() );
		$hookRunner->onUserMergeAccountDeleteTables( $tablesToDelete );

		// Make sure these are always set, and set last
		$tablesToDelete['actor'] = 'actor_user';
		$tablesToDelete['user'] = 'user_id';

		foreach ( $tablesToDelete as $table => $field ) {
			// Check if a different database object was passed (Echo or Flow)
			if ( is_array( $field ) ) {
				$db = $field['db'] ?? $dbw;
				$field = $field[0];
			} else {
				$db = $dbw;
			}
			$db->newDeleteQueryBuilder()
				->deleteFrom( $table )
				->where( [ $field => $this->oldUser->getId() ] )
				->caller( __METHOD__ )
				->execute();
		}

		$hookRunner->onDeleteAccount( $this->oldUser );

		DeferredUpdates::addUpdate( SiteStatsUpdate::factory( [ 'users' => -1 ] ) );
	}
}
