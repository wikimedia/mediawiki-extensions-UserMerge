<?php

class UserMergeHooksTest extends MediaWikiIntegrationTestCase {
	private function newUserMergeHooks(): UserMergeHooks {
		$services = $this->getServiceContainer();
		return new UserMergeHooks(
			$services->getConfigFactory(),
			$services->getUserGroupManager()
		);
	}

	/**
	 * @covers UserMergeHooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteEnabled() {
		$this->setMwGlobals( [
			'wgUserMergeEnableDelete' => true
		] );

		$usernames = [];
		$this->newUserMergeHooks()->onUserGetReservedNames( $usernames );

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
		$this->newUserMergeHooks()->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [], $usernames );
	}
}
