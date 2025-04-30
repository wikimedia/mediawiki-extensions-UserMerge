<?php

use MediaWiki\Extension\UserMerge\Hooks;

class UserMergeHooksTest extends MediaWikiIntegrationTestCase {
	private function newUserMergeHooks(): Hooks {
		$services = $this->getServiceContainer();
		return new Hooks(
			$services->getConfigFactory(),
			$services->getUserFactory(),
			$services->getUserGroupManager()
		);
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\Hooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteEnabled(): void {
		$this->overrideConfigValue( 'UserMergeEnableDelete', true );

		$usernames = [];
		$this->newUserMergeHooks()->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [ 'Anonymous' ], $usernames );
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\Hooks::onUserGetReservedNames
	 */
	public function testReservedUsernameWithDeleteDisabled(): void {
		$this->overrideConfigValue( 'UserMergeEnableDelete', false );

		$usernames = [];
		$this->newUserMergeHooks()->onUserGetReservedNames( $usernames );

		$this->assertArrayEquals( [], $usernames );
	}
}
