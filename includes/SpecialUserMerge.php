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

use MediaWiki\Block\DatabaseBlockStore;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\FormSpecialPage;
use MediaWiki\Status\Status;
use MediaWiki\Title\Title;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;

class SpecialUserMerge extends FormSpecialPage {

	private UserFactory $userFactory;
	private UserGroupManager $userGroupManager;
	private DatabaseBlockStore $blockStore;

	public function __construct(
		UserFactory $userFactory,
		UserGroupManager $userGroupManager,
		DatabaseBlockStore $blockStore
	) {
		parent::__construct( 'UserMerge', 'usermerge' );
		$this->userFactory = $userFactory;
		$this->userGroupManager = $userGroupManager;
		$this->blockStore = $blockStore;
	}

	/**
	 * @return array
	 */
	protected function getFormFields() {
		return [
			'olduser' => [
				'type' => 'user',
				'exists' => true,
				'label-message' => 'usermerge-olduser',
				'required' => true,
				'validation-callback' => function ( $val ) {
					$key = $this->validateOldUser( $val );
					if ( is_array( $key ) ) {
						return $this->msg( $key )->escaped();
					}
					return true;
				},
			],
			'newuser' => [
				'type' => 'user',
				'required' => true,
				'label-message' => 'usermerge-newuser',
				'validation-callback' => function ( $val ) {
					// only pass strings to UserFactory::newFromName
					if ( !is_string( $val ) ) {
						return true;
					}

					$key = $this->validateNewUser( $val );
					if ( is_string( $key ) ) {
						return $this->msg( $key )->escaped();
					}
					return true;
				},
			],
			'delete' => [
				'type' => 'check',
				'label-message' => 'usermerge-deleteolduser',
			],
		];
	}

	/**
	 * @param string $val user's input for username
	 * @return true|string[] true if valid, a string[] of the error's message key and params
	 *   if validation failed
	 */
	public function validateOldUser( $val ) {
		$oldUser = $this->userFactory->newFromName( $val );
		if ( !$oldUser ) {
			return [ 'usermerge-badolduser' ];
		}
		if ( $this->getUser()->getId() === $oldUser->getId() ) {
			return [ 'usermerge-noselfdelete', $this->getUser()->getName() ];
		}
		$protectedGroups = $this->getConfig()->get( 'UserMergeProtectedGroups' );
		if ( array_intersect( $this->userGroupManager->getUserGroups( $oldUser ), $protectedGroups ) !== [] ) {
			return [ 'usermerge-protectedgroup', $oldUser->getName() ];
		}

		return true;
	}

	/**
	 * @param string $val user's input for username
	 * @return true|string true if valid, a string of the error's message key if validation failed
	 */
	public function validateNewUser( $val ) {
		$enableDelete = $this->getConfig()->get( 'UserMergeEnableDelete' );
		if ( $enableDelete && $val === 'Anonymous' ) {
			// Special case
			return true;
		}
		$newUser = $this->userFactory->newFromName( $val );
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
	}

	/**
	 * @param array $data
	 * @return Status
	 */
	public function onSubmit( array $data ) {
		$enableDelete = $this->getConfig()->get( 'UserMergeEnableDelete' );
		// Most of the data has been validated using callbacks
		// still need to check if the users are different
		$newUser = $this->userFactory->newFromName( $data['newuser'] );
		if ( !$newUser ) {
			return Status::newFatal( 'usermerge-badnewuser' );
		}
		// Handle "Anonymous" as a special case for user deletion
		if ( $enableDelete && $data['newuser'] === 'Anonymous' ) {
			$newUser->mId = 0;
		}

		$oldUser = $this->userFactory->newFromName( $data['olduser'] );
		if ( !$oldUser ) {
			return Status::newFatal( 'usermerge-badolduser' );
		}
		if ( $newUser->getName() === $oldUser->getName() ) {
			return Status::newFatal( 'usermerge-same-old-and-new-user' );
		}

		// Validation passed, let's merge the user now.
		$um = new MergeUser( $oldUser, $newUser, new UserMergeLogger(), $this->blockStore );
		$um->merge( $this->getUser(), __METHOD__ );

		$out = $this->getOutput();

		$out->addWikiMsg(
			'usermerge-success',
			$oldUser->getName(), $oldUser->getId(),
			$newUser->getName(), $newUser->getId()
		);

		if ( $data['delete'] ) {
			$failed = $um->delete( $this->getUser(), [ $this, 'msg' ] );
			$out->addWikiMsg(
				'usermerge-userdeleted', $oldUser->getName(), $oldUser->getId()
			);

			if ( $failed ) {
				// Output an error message for failed moves
				$out->addHTML( Html::openElement( 'ul' ) );
				$linkRenderer = $this->getLinkRenderer();
				foreach ( $failed as $oldTitleText => $newTitle ) {
					$oldTitle = Title::newFromText( $oldTitleText );
					$out->addHTML(
						Html::rawElement( 'li', [],
							$this->msg( 'usermerge-page-unmoved' )->rawParams(
								$linkRenderer->makeLink( $oldTitle ),
								$linkRenderer->makeLink( $newTitle )
							)->escaped()
						)
					);
				}
				$out->addHTML( Html::closeElement( 'ul' ) );
			}
		}

		return Status::newGood();
	}

	/**
	 * @inheritDoc
	 */
	protected function getDisplayFormat() {
		return 'ooui';
	}

	/**
	 * @inheritDoc
	 */
	protected function getGroupName() {
		return 'users';
	}

	/**
	 * @inheritDoc
	 */
	public function doesWrites() {
		return true;
	}
}
