<?php
/** \file
 * \brief Contains code for the UserMerge Class (extends SpecialPage).
 */

/**
 * Special page class for the User Merge and Delete extension
 * allows sysops to merge references from one user to another user.
 * It also supports deleting users following merge.
 *
 * @ingroup Extensions
 * @author Tim Laqua <t.laqua@gmail.com>
 * @author Thomas Gries <mail@tgries.de>
 * @author Matthew April <Matthew.April@tbs-sct.gc.ca>
 *
 */

class SpecialUserMerge extends FormSpecialPage {
	function __construct() {
		parent::__construct( 'UserMerge', 'usermerge' );
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		$us = $this;
		return array(
			'olduser' => array(
				'type' => 'text',
				'label-message' => 'usermerge-olduser',
				'required' => true,
				'validation-callback' => function( $val ) use ( $us ) {
					$key = $us->validateOldUser( $val );
					if ( is_string( $key ) ) {
						return $us->msg( $key )->escaped();
					}
					return true;
				},
			),
			'newuser' => array(
				'type' => 'text',
				'required' => true,
				'label-message' => 'usermerge-newuser',
				'validation-callback' => function( $val ) use ( $us ) {
					$key = $us->validateNewUser( $val );
					if ( is_string( $key ) ) {
						return $us->msg( $key )->escaped();
					}
					return true;
				},
			),
			'delete' => array(
				'type' => 'check',
				'label-message' => 'usermerge-deleteolduser',
			),
		);
	}

	/**
	 * @param $val user's input for username
	 * @return bool|string true if valid, a string of the error's message key if validation failed
	 */
	public function validateOldUser( $val ) {
		global $wgUserMergeProtectedGroups;
		$oldUser = User::newFromName( $val );
		if ( !$oldUser || $oldUser->getId() === 0 ) {
			return 'usermerge-badolduser';
		}
		if ( $this->getUser()->getId() === $oldUser->getId() ) {
			return 'usermerge-noselfdelete';
		}
		if ( count( array_intersect( $oldUser->getGroups(), $wgUserMergeProtectedGroups ) ) ) {
			return 'usermerge-protectedgroup';
		}

		return true;
	}

	/**
	 * @param $val user's input for username
	 * @return bool|string true if valid, a string of the error's message key if validation failed
	 */
	public function validateNewUser( $val ) {
		global $wgUserMergeEnableDelete;
		if ( $wgUserMergeEnableDelete && $val === 'Anonymous' ) {
			return true; // Special case
		}
		$newUser = User::newFromName( $val );
		if ( !$newUser || $newUser->getId() === 0 ) {
			return 'usermerge-badnewuser';
		}

		return true;
	}

	/**
	 * @param HTMLForm $form
	 */
	protected function alterForm( HTMLForm $form ) {
		$form->setSubmitTextMsg( 'usermerge-submit' );
		$form->setWrapperLegendMsg( 'usermerge-fieldset' );
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmit( array $data ) {
		global $wgUserMergeEnableDelete;
		// Most of the data has been validated using callbacks
		// still need to check if the users are different
		$newUser = User::newFromName( $data['newuser'] );
		// Handle "Anonymous" as a special case for user deletion
		if ( $wgUserMergeEnableDelete && $data['newuser'] === 'Anonymous' ) {
			$newUser->mId = 0;
		}

		$oldUser = User::newFromName( $data['olduser'] );
		if ( $newUser->getName() === $oldUser->getName() ) {
			return Status::newFatal( 'usermerge-same-old-and-new-user' );
		}

		$this->mergeEditcount( $newUser->getId(), $oldUser->getId() );
		$this->mergeUser(
			$newUser, $newUser->getName(), $newUser->getId(),
			$oldUser, $oldUser->getName(), $oldUser->getId()
		);

		if ( $data['delete'] ) {
			$this->movePages( $newUser->getName(), $oldUser->getName() );
			$this->deleteUser( $oldUser, $oldUser->getId(), $oldUser->getName() );
		}

		return Status::newGood();
	}

	/**
	 * Function to delete users following a successful mergeUser call
	 *
	 * Removes user entries from the user table and the user_groups table
	 *
	 * @param $objOldUser User
	 * @param $olduserID int ID of user to delete
	 * @param $olduser_text string Username of user to delete
	 *
	 * @return bool Always returns true - throws exceptions on failure.
	 */
	private function deleteUser( $objOldUser, $olduserID, $olduser_text ) {
		$dbw = wfGetDB( DB_MASTER );
		$dbw->delete(
			'user_groups',
			array( 'ug_user' => $olduserID )
		);
		$dbw->delete(
			'user',
			array( 'user_id' => $olduserID )
		);
		$this->getOutput()->addHTML(
			$this->msg( 'usermerge-userdeleted', $olduser_text, $olduserID )->escaped() .
			Html::element( 'br' ) . "\n"
		);

		$log = new LogPage( 'usermerge' );
		$log->addEntry( 'deleteuser', $this->getUser()->getUserPage(), '', array( $olduser_text, $olduserID ) );

		wfRunHooks( 'DeleteAccount', array( &$objOldUser ) );

		DeferredUpdates::addUpdate( SiteStatsUpdate::factory( array( 'users' => -1 ) ) );

		return true;
	}

	/**
	 * Deduplicate watchlist entries
	 * which old (merge-from) and new (merge-to) users are watching
	 *
	 * @param $oldUser User
	 * @param $newUser User
	 *
	 * @return bool
	 */
	private function deduplicateWatchlistEntries( $oldUser, $newUser ) {

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
				'w1.wl_user' => $newUser->getID(),
				'w2.wl_user' => $oldUser->getID()
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
					'wl_user' => $oldUser->getID(),
					'wl_namespace' => $result->wl_namespace,
					'wl_title' => $result->wl_title
				),
				LIST_AND
			);
		}

		if ( empty( $conds ) ) {
			$dbw->commit( __METHOD__ );
			return true;
		}

		# Perform a multi-row delete

		# requires
		# MediaWiki database function with fixed https://bugzilla.wikimedia.org/50078
		# i.e. MediaWiki core after 505dbb331e16a03d87cb4511ee86df12ea295c40 (20130625)
		$dbw->delete(
			'watchlist',
			$dbw->makeList( $conds, LIST_OR ),
			__METHOD__
		);

		$dbw->commit( __METHOD__ );

		return true;
	}


	/**
	 * Function to merge database references from one user to another user
	 *
	 * Merges database references from one user ID or username to another user ID or username
	 * to preserve referential integrity.
	 *
	 * @param $objNewUser User
	 * @param $newuser_text string Username to merge references TO
	 * @param $newuserID int ID of user to merge references TO
	 * @param $objOldUser User
	 * @param $olduser_text string Username of user to remove references FROM
	 * @param $olduserID int ID of user to remove references FROM
	 *
	 * @return bool Always returns true - throws exceptions on failure.
	 */
	private function mergeUser( $objNewUser, $newuser_text, $newuserID, $objOldUser, $olduser_text, $olduserID ) {
		// Fields to update with the format:
		// array( tableName, idField, textField )
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
		);

		wfRunHooks( 'UserMergeAccountFields', array( &$updateFields ) );

		$dbw = wfGetDB( DB_MASTER );
		$out = $this->getOutput();

		$this->deduplicateWatchlistEntries( $objOldUser, $objNewUser );

		foreach ( $updateFields as $fieldInfo ) {
			$tableName = array_shift( $fieldInfo );
			$idField = array_shift( $fieldInfo );

			$dbw->update(
				$tableName,
				array( $idField => $newuserID ) + array_fill_keys( $fieldInfo, $newuser_text ),
				array( $idField => $olduserID ),
				__METHOD__
			);

			$out->addHTML(
				$this->msg(
					'usermerge-updating',
					$tableName,
					$olduserID,
					$newuserID
				)->escaped() .
				Html::element( 'br' ) . "\n"
			);

			foreach ( $fieldInfo as $textField ) {
				$out->addHTML(
					$this->msg(
						'usermerge-updating',
						$tableName,
						$olduser_text,
						$newuser_text
					)->escaped() .
					Html::element( 'br' ) . "\n"
				);
			}
		}

		$dbw->delete( 'user_newtalk', array( 'user_id' => $olduserID ) );

		$out->addHTML(
			Html::element( 'hr' ) . "\n" .
			$this->msg( 'usermerge-success', $olduser_text, $olduserID, $newuser_text, $newuserID )->escaped() .
			Html::element( 'br' ) . "\n"
		);

		$log = new LogPage( 'usermerge' );
		$log->addEntry(
			'mergeuser',
			$this->getUser()->getUserPage(),
			'',
			array( $olduser_text, $olduserID, $newuser_text, $newuserID )
		);

		wfRunHooks( 'MergeAccountFromTo', array( &$objOldUser, &$objNewUser ) );

		return true;
	}


	/**
	 * Adds edit count of both users
	 *
	 * @param $newuserID int ID of user we're merging into
	 * @param $olduserID int ID of user we're merging from
	 *
	 * @return bool
	 */
	private function mergeEditcount( $newuserID, $olduserID ) {
		$dbw = wfGetDB( DB_MASTER );

		$totalEdits = $dbw->selectField(
			'user',
			'SUM(user_editcount)',
			array( 'user_id' => array( $newuserID, $olduserID ) ),
			__METHOD__
		);

		$totalEdits = intval( $totalEdits );

		# don't run queries if neither user has any edits
		if ( $totalEdits > 0 ) {
			# update new user with total edits
			$dbw->update( 'user',
				array( 'user_editcount' => $totalEdits ),
				array( 'user_id' => $newuserID ),
				__METHOD__
			);

			# clear old user's edits
			$dbw->update( 'user',
				array( 'user_editcount' => 0 ),
				array( 'user_id' => $olduserID ),
				__METHOD__
			);
		}

		$this->getOutput()->addHTML(
			$this->msg(
				'usermerge-editcount-merge-success2',
				$olduserID, $newuserID, Message::numParam( $totalEdits )
			)->escaped() .
			Html::element( 'br' ) . "\n"
		);

		return true;
	}

	/**
	 * Function to merge user pages
	 *
	 * Deletes all pages when merging to Anon
	 * Moves user page when the target user page does not exist or is empty
	 * Deletes redirect if nothing links to old page
	 * Deletes the old user page when the target user page exists
	 *
	 * @param $newuser_text string Username to merge pages TO
	 * @param $olduser_text string Username of user to remove pages FROM
	 *
	 * @return bool True on completion
	 *
	 * @author Matthew April <Matthew.April@tbs-sct.gc.ca>
	 */
	private function movePages( $newuser_text, $olduser_text ) {
		global $wgContLang;

		$oldusername = trim( str_replace( '_', ' ', $olduser_text ) );
		$oldusername = Title::makeTitle( NS_USER, $oldusername );
		$newusername = Title::makeTitleSafe( NS_USER, $wgContLang->ucfirst( $newuser_text ) );

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

		$output = '';

		foreach ( $pages as $row ) {

			$oldPage = Title::makeTitleSafe( $row->page_namespace, $row->page_title );
			$newPage = Title::makeTitleSafe( $row->page_namespace,
				preg_replace( '!^[^/]+!', $newusername->getDBkey(), $row->page_title ) );

			if ( $newuser_text === "Anonymous" ) { # delete ALL old pages
				if ( $oldPage->exists() ) {
					$oldPageArticle = new Article( $oldPage, 0 );
					$oldPageArticle->doDeleteArticle( $this->msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );

					$oldLink = Linker::linkKnown( $oldPage );
					$output .= Html::rawElement( 'li',
						array( 'class' => 'mw-renameuser-pe' ),
						$this->msg( 'usermerge-page-deleted' )->rawParams( $oldLink )->escaped()
					);

				}
			} elseif ( $newPage->exists()
				&& !$oldPage->isValidMoveTarget( $newPage )
				&& $newPage->getLength() > 0 ) { # delete old pages that can't be moved

				$oldPageArticle = new Article( $oldPage, 0 );
				$oldPageArticle->doDeleteArticle( $this->msg( 'usermerge-autopagedelete' )->text() );

				$link = Linker::linkKnown( $oldPage );
				$output .= Html::rawElement( 'li',
					array( 'class' => 'mw-renameuser-pe' ),
					$this->msg( 'usermerge-page-deleted' )->rawParams( $link )->escaped()
				);

			} else { # move content to new page
				# delete target page if it exists and is blank
				if ( $newPage->exists() ) {
					$newPageArticle = new Article( $newPage, 0 );
					$newPageArticle->doDeleteArticle( $this->msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );
				}

				# move to target location
				$success = $oldPage->moveTo(
					$newPage,
					false,
					$this->msg(
						'usermerge-move-log',
						$oldusername->getText(),
						$newusername->getText() )->inContentLanguage()->text()
				);

				if ( $success === true ) {
					$oldLink = Linker::linkKnown(
						$oldPage,
						null,
						array(),
						array( 'redirect' => 'no' )
					);
					$newLink = Linker::linkKnown( $newPage );
					$output .= Html::rawElement( 'li',
						array( 'class' => 'mw-renameuser-pm' ),
						$this->msg( 'usermerge-page-moved' )->rawParams( $oldLink, $newLink )->escaped()
					);
				} else {
					$oldLink = Linker::linkKnown( $oldPage );
					$newLink = Linker::linkKnown( $newPage );
					$output .= Html::rawElement( 'li',
						array( 'class' => 'mw-renameuser-pu' ),
						$this->msg( 'usermerge-page-unmoved' )->rawParams( $oldLink, $newLink )->escaped()
					);
				}

				# check if any pages link here
				$res = $dbr->selectField( 'pagelinks',
					'pl_title',
					array( 'pl_title' => $olduser_text ),
					__METHOD__
				);
				if ( !$dbr->numRows( $res ) ) {
					# nothing links here, so delete unmoved page/redirect
					$oldPageArticle = new Article( $oldPage, 0 );
					$oldPageArticle->doDeleteArticle( $this->msg( 'usermerge-autopagedelete' )->inContentLanguage()->text() );
				}
			}

		}

		if ( $output ) {
			$this->getOutput()->addHTML(
				Html::rawElement( 'ul',
					array(),
					$output
				)
			);
		}

		return true;
	}

}

/**
 * Former class name, for backwards compatability
 * @deprecated
 */
class UserMerge extends SpecialUserMerge {}
