<?php

class UserMergeHooksTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers UserMergeHooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteEnabled() {
		$this->overrideConfigValue( 'UserMergeEnableDelete', true );

		$usernames = [];
		( new UserMergeHooks )->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [ 'Anonymous' ], $usernames );
	}

	/**
	 * @covers UserMergeHooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteDisabled() {
		$this->overrideConfigValue( 'UserMergeEnableDelete', false );

		$usernames = [];
		( new UserMergeHooks )->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [], $usernames );
	}
}
