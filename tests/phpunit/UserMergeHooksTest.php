<?php

class UserMergeHooksTest extends MediaWikiIntegrationTestCase {
	/**
	 * @covers UserMergeHooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteEnabled() {
		$this->setMwGlobals( [
			'wgUserMergeEnableDelete' => true
		] );

		$usernames = [];
		( new UserMergeHooks )->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [ 'Anonymous' ], $usernames );
	}

	/**
	 * @covers UserMergeHooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteDisabled() {
		$this->setMwGlobals( [
			'wgUserMergeEnableDelete' => false
		] );

		$usernames = [];
		( new UserMergeHooks )->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [], $usernames );
	}
}
