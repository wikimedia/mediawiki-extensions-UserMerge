<?php

/**
 * Contains the actual database backend logic for merging users
 *
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

	public function __construct(
		User $oldUser,
		User $newUser,
		IUserMergeLogger $logger
	) {
		$this->newUser = $newUser;
		$this->oldUser = $oldUser;
		$this->logger = $logger;
	}

	public function merge( User $performer ) {
		$this->mergeEditcount();
		$this->mergeDatabaseTables();
		$this->logger->addMergeEntry( $performer, $this->oldUser, $this->newUser );
	}

	/**
	 * @param User $performer
	 * @param callable $msg something that returns a Message object
	 *
	 * @return array Array of failed page moves, see MergeUser::movePages
	 */
	public function delete( User $performer, /* callable */ $msg  ) {
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

		$totalEdits = $dbw->selectField(
			'user',
			'SUM(user_editcount)',
			array( 'user_id' => array( $this->newUser->getId(), $this->oldUser->getId() ) ),
			__METHOD__
		);

		$totalEdits = intval( $totalEdits );

		# don't run queries if neither user has any edits
		if ( $totalEdits > 0 ) {
			# update new user with total edits
			$dbw->update( 'user',
				array( 'user_editcount' => $totalEdits ),
				array( 'user_id' => $this->newUser->getId() ),
				__METHOD__
			);

			# clear old user's edits
			$dbw->update( 'user',
				array( 'user_editcount' => 0 ),
				array( 'user_id' => $this->oldUser->getId() ),
				__METHOD__
			);
		}
	}

	/**
	 * Function to merge database references from one user to another user
	 *
	 * Merges database references from one user ID or username to another user ID or username
	 * to preserve referential integrity.
	 */
	private function mergeDatabaseTables() {
		// Fields to update with the format:
		// array( tableName, idField, textField, 'options' => array() )
		// textField and options are optional
		$updateFields = array(
			array( 'archive', 'ar_user', 'ar_user_text' ),
			array( 'revision', 'rev_user', 'rev_user_text' ),
			array( 'filearchive', 'fa_user', 'fa_user_text' ),
			array( 'image', 'img_user', 'img_user_text' ),
			array( 'oldimage', 'oi_user', 'oi_user_text' ),
			array( 'recentchanges', 'rc_user', 'rc_user_text' ),
			array( 'logging', 'log_user' ),
			array( 'ipblocks', 'ipb_user', 'ipb_address' ),
			array( 'ipblocks', 'ipb_by', 'ipb_by_text' ),
			array( 'watchlist', 'wl_user' ),
			array( 'user_groups', 'ug_user', 'options' => array( 'IGNORE' ) ),
			array( 'user_properties', 'up_user', 'options' => array( 'IGNORE' ) ),
			array( 'user_former_groups', 'ufg_user', 'options' => array( 'IGNORE' ) ),
		);

		wfRunHooks( 'UserMergeAccountFields', array( &$updateFields ) );

		$dbw = wfGetDB( DB_MASTER );

		$this->deduplicateWatchlistEntries();

		foreach ( $updateFields as $fieldInfo ) {
			$options = isset( $fieldInfo['options'] ) ? $fieldInfo['options'] : array();
			unset( $fieldInfo['options'] );
			$tableName = array_shift( $fieldInfo );
			$idField = array_shift( $fieldInfo );
			$dbw->update(
				$tableName,
				array( $idField => $this->newUser->getId() ) + array_fill_keys( $fieldInfo, $this->newUser->getName() ),
				array( $idField => $this->oldUser->getId() ),
				__METHOD__,
				$options
			);
		}

		$dbw->delete( 'user_newtalk', array( 'user_id' => $this->oldUser->getId() ) );

		wfRunHooks( 'MergeAccountFromTo', array( &$this->oldUser, &$this->newUser ) );
	}

	/**
	 * Deduplicate watchlist entries
	 * which old (merge-from) and new (merge-to) users are watching
	 */
	private function deduplicateWatchlistEntries() {

		$dbw = wfGetDB( DB_MASTER );
		$dbw->begin( __METHOD__ );

		$res = $dbw->select(
			array(
				'w1' => 'watchlist',
				'w2' => 'watchlist'
			),
			array(
				'w2.wl_namespace',
				'w2.wl_title'
			),
			array(
				'w1.wl_user' => $this->newUser->getID(),
				'w2.wl_user' => $this->oldUser->getID()
			),
			__METHOD__,
			array( 'FOR UPDATE' ),
			array(
				'w2' => array(
					'INNER JOIN',
					array(
						'w1.wl_namespace = w2.wl_namespace',
						'w1.wl_title = w2.wl_title'
					),
				)
			)
		);

		# Construct an array to delete all watched pages of the old user
		# which the new user already watches
		$conds = array();

		foreach ( $res as $result ) {
			$conds[] = $dbw->makeList(
				array(
					'wl_user' => $this->oldUser->getID(),
					'wl_namespace' => $result->wl_namespace,
					'wl_title' => $result->wl_title
				),
				LIST_AND
			);
		}

		if ( empty( $conds ) ) {
			$dbw->commit( __METHOD__ );
			return;
		}

		# Perform a multi-row delete
		$dbw->delete(
			'watchlist',
			$dbw->makeList( $conds, LIST_OR ),
			__METHOD__
		);

		$dbw->commit( __METHOD__ );
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
	private function movePages( User $performer, /* callable */ $msg ) {
		global $wgContLang, $wgUser;

		$oldusername = trim( str_replace( '_', ' ', $this->oldUser->getName() ) );
		$oldusername = Title::makeTitle( NS_USER, $oldusername );
		$newusername = Title::makeTitleSafe( NS_USER, $wgContLang->ucfirst( $this->newUser->getName() ) );

		# select all user pages and sub-pages
		$dbr = wfGetDB( DB_SLAVE );
		$pages = $dbr->select( 'page',
			array( 'page_namespace', 'page_title' ),
			array(
				'page_namespace' => array( NS_USER, NS_USER_TALK ),
				$dbr->makeList( array(
						'page_title' => $dbr->buildLike( $oldusername->getDBkey() . '/', $dbr->anyString() ),
						'page_title' => $oldusername->getDBkey()
					),
					LIST_OR
				)
			)
		);

		// Need to set $wgUser to attribute log properly.
		$oldUser = $wgUser;
		$wgUser = $performer;

		$failedMoves = array();
		foreach ( $pages as $row ) {

			$oldPage = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			$newPage = Title::makeTitleSafe( $row->page_namespace,
				preg_replace( '!^[^/]+!', $newusername->getDBkey(), $row->page_title ) );

			if ( $this->newUser->getName() === "Anonymous" ) { # delete ALL old pages
				if ( $oldPage->exists() ) {
					$oldPageArticle = new Article( $oldPage, 0 );
					$oldPageArticle->doDeleteArticle( $msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );
				}
			} elseif ( $newPage->exists()
				&& !$oldPage->isValidMoveTarget( $newPage )
				&& $newPage->getLength() > 0 ) { # delete old pages that can't be moved

				$oldPageArticle = new Article( $oldPage, 0 );
				$oldPageArticle->doDeleteArticle( $msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );

			} else { # move content to new page
				# delete target page if it exists and is blank
				if ( $newPage->exists() ) {
					$newPageArticle = new Article( $newPage, 0 );
					$newPageArticle->doDeleteArticle( $msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );
				}

				# move to target location
				$errors = $oldPage->moveTo(
					$newPage,
					false,
					$msg(
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
					array( 'pl_title' => $this->oldUser->getName() ),
					__METHOD__
				);
				if ( !$dbr->numRows( $res ) ) {
					# nothing links here, so delete unmoved page/redirect
					$oldPageArticle = new Article( $oldPage, 0 );
					$oldPageArticle->doDeleteArticle( $msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );
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

		$tablesToDelete = array(
			'user_groups' => 'ug_user',
			'user_properties' => 'up_user',
			'user_former_groups' => 'ufg_user',
		);

		wfRunHooks( 'UserMergeAccountDeleteTables', array( &$tablesToDelete ) );

		$tablesToDelete['user'] = 'user_id'; // Make sure this always set and last

		foreach ( $tablesToDelete as $table => $field ) {
			$dbw->delete(
				$table,
				array( $field => $this->oldUser->getId() )
			);
		}

		wfRunHooks( 'DeleteAccount', array( &$this->oldUser ) );

		DeferredUpdates::addUpdate( SiteStatsUpdate::factory( array( 'users' => -1 ) ) );
	}


}
