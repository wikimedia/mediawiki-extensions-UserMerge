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
}
