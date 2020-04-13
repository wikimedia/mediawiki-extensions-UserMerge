<?php

class UserMergeHooksTest extends MediaWikiTestCase {
	/**
	 * @covers UserMergeHooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteEnabled() {
		$this->setMwGlobals( [
			'wgUserMergeEnableDelete' => true
		] );

		$usernames = [];
		UserMergeHooks::onUserGetReservedNames( $usernames );

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
		UserMergeHooks::onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [], $usernames );
	}
}
