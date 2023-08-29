<?php

use MediaWiki\MediaWikiServices;

class UserMergeHooks {
	/**
	 * UserGetReservedNames hook handler
	 *
	 * @param string[] &$reservedUsernames Already registered reserved names
	 */
	public static function onUserGetReservedNames( array &$reservedUsernames ) {
		$deleteEnabled = MediaWikiServices::getInstance()
			->getConfigFactory()
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
	public static function onContributionsToolLinks(
		int $id, Title $nt, array &$tools, SpecialPage $sp
	) {
		if ( $id === 0 || $id === $sp->getUser()->getId() ) {
			return;
		}
		if ( !$sp->getAuthority()->isAllowed( 'usermerge' ) ) {
			return;
		}
		$targetUser = User::newFromId( $id );
		if ( array_intersect(
			MediaWikiServices::getInstance()->getUserGroupManager()->getUserGroups( $targetUser ),
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
