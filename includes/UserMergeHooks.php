<?php

use MediaWiki\Config\ConfigFactory;
use MediaWiki\Hook\ContributionsToolLinksHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\Hook\UserGetReservedNamesHook;
use MediaWiki\User\UserFactory;
use MediaWiki\User\UserGroupManager;

class UserMergeHooks implements
	UserGetReservedNamesHook,
	ContributionsToolLinksHook
{
	private ConfigFactory $configFactory;
	private UserFactory $userFactory;
	private UserGroupManager $userGroupManager;

	public function __construct(
		ConfigFactory $configFactory,
		UserFactory $userFactory,
		UserGroupManager $userGroupManager
	) {
		$this->configFactory = $configFactory;
		$this->userFactory = $userFactory;
		$this->userGroupManager = $userGroupManager;
	}

	/**
	 * UserGetReservedNames hook handler
	 *
	 * @param string[] &$reservedUsernames Already registered reserved names
	 */
	public function onUserGetReservedNames( &$reservedUsernames ) {
		$deleteEnabled = $this->configFactory
			->makeConfig( 'usermerge' )
			->get( 'UserMergeEnableDelete' );

		if ( $deleteEnabled ) {
			$reservedUsernames[] = 'Anonymous';
		}
	}

	/**
	 * Handler for ContributionsToolLinks hook
	 *
	 * @param int $id
	 * @param Title $nt
	 * @param array &$tools
	 * @param SpecialPage $sp for context
	 */
	public function onContributionsToolLinks(
		$id, Title $nt, array &$tools, SpecialPage $sp
	) {
		if ( $id === 0 || $id === $sp->getUser()->getId() ) {
			return;
		}
		if ( !$sp->getAuthority()->isAllowed( 'usermerge' ) ) {
			return;
		}
		$targetUser = $this->userFactory->newFromId( $id );
		if ( array_intersect(
			$this->userGroupManager->getUserGroups( $targetUser ),
			$sp->getConfig()->get( 'UserMergeProtectedGroups' )
		) ) {
			return;
		}

		$username = $nt->getText();
		$linkRenderer = $sp->getLinkRenderer();
		$tools['usermerge-merge'] = $linkRenderer->makeKnownLink(
			SpecialPage::getTitleFor( 'UserMerge' ),
			$sp->msg( 'usermerge-merge-linkoncontribs', $username )->text(),
			[],
			[ 'wpolduser' => $username ]
		);
		if ( $sp->getConfig()->get( 'UserMergeEnableDelete' ) ) {
			$tools['usermerge-delete'] = $linkRenderer->makeKnownLink(
				SpecialPage::getTitleFor( 'UserMerge' ),
				$sp->msg( 'usermerge-delete-linkoncontribs', $username )->text(),
				[],
				[ 'wpolduser' => $username, 'wpdelete' => '1' ]
			);
		}
	}

}
