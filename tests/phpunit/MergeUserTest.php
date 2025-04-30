<?php

use MediaWiki\Deferred\SiteStatsUpdate;
use MediaWiki\Extension\UserMerge\MergeUser;
use MediaWiki\Extension\UserMerge\UserMergeLogger;
use MediaWiki\User\User;
use Wikimedia\Rdbms\IDBAccessObject;

/**
 * @todo this should better use the Database group
 * @group Database
 */
class MergeUserTest extends MediaWikiIntegrationTestCase {

	private int $counter = 0;

	private function getNewTestUser(): User {
		$this->counter++;
		$name = __CLASS__ . (string)$this->counter;
		$user = $this->getServiceContainer()->getUserFactory()->newFromName( $name );
		if ( $user->getId() ) {
			// Already exists, try again.
			return $this->getNewTestUser();
		}
		$user->setName( $name );
		$user->addToDatabase();
		SiteStatsUpdate::factory( [ 'users' => 1 ] )->doUpdate();
		return $user;
	}

	/**
	 * Clear all instance caches
	 */
	private function reallyClearInstanceCache( User &$u ): void {
		$u = $this->getServiceContainer()->getUserFactory()->newFromName( $u->getName() );
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\MergeUser::merge
	 */
	public function testBasicMerge(): void {
		$user1 = $this->getNewTestUser();
		$user1->addToDatabase();
		$userOptionsManager = $this->getServiceContainer()->getUserOptionsManager();
		$userOptionsManager->setOption( $user1, 'foo', 'baz' );
		$userOptionsManager->saveOptions( $user1 );
		$user2 = $this->getNewTestUser();
		$user2->addToDatabase();

		$mu = new MergeUser( $user1, $user2,
			$this->createMock( UserMergeLogger::class ),
			$this->getServiceContainer()->getDatabaseBlockStore()
		);
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );
		$this->assertNull( $userOptionsManager->getOption( $user1, 'foo' ) );
		$this->assertEquals( 'baz', $userOptionsManager->getOption( $user2, 'foo' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\MergeUser::merge
	 */
	public function testMergeOfUserGroups(): void {
		// Postgres does not support UPDATE IGNORE, resulting in duplicate keys here
		$this->markTestSkippedIfDbType( 'postgres' );

		$user1 = $this->getNewTestUser();
		$userGroupManager = $this->getServiceContainer()->getUserGroupManager();
		$userGroupManager->addUserToGroup( $user1, 'group1' );
		$userGroupManager->addUserToGroup( $user1, 'group2' );
		$user2 = $this->getNewTestUser();
		$userGroupManager->addUserToGroup( $user2, 'group2' );

		$mu = new MergeUser( $user1, $user2,
			$this->createMock( UserMergeLogger::class ),
			$this->getServiceContainer()->getDatabaseBlockStore() );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );

		$this->assertArrayEquals( [ 'group2' ], $userGroupManager->getUserGroups( $user1 ) );
		$this->assertArrayEquals( [ 'group1', 'group2' ], $userGroupManager->getUserGroups( $user2 ) );
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\MergeUser::delete
	 */
	public function testDeleteUser(): void {
		$user1 = $this->getNewTestUser();
		$user2 = $this->getNewTestUser();

		$this->reallyClearInstanceCache( $user1 );
		$this->assertGreaterThan( 0, $user1->getId() );

		$mu = new MergeUser( $user1, $user2,
			$this->createMock( UserMergeLogger::class ),
			$this->getServiceContainer()->getDatabaseBlockStore() );
		$mu->delete( $this->createMock( User::class ), 'wfMessage' );

		$this->reallyClearInstanceCache( $user1 );
		$this->assertSame( 0, $user1->getId() );
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\MergeUser::mergeEditcount
	 */
	public function testMergeEditcount(): void {
		$user1 = $this->getNewTestUser();
		$user2 = $this->getNewTestUser();
		$count = 0;
		$userEditTracker = $this->getServiceContainer()->getUserEditTracker();
		$userEditTracker->incrementUserEditCount( $user1 );
		while ( $count < 10 ) {
			$userEditTracker->incrementUserEditCount( $user1 );
			$userEditTracker->incrementUserEditCount( $user2 );
			$count++;
		}

		$mu = new MergeUser( $user1, $user2,
			$this->createMock( UserMergeLogger::class ),
			$this->getServiceContainer()->getDatabaseBlockStore() );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );
		$this->assertSame( 0, $user1->getEditCount() );
		$this->assertEquals( 21, $user2->getEditCount() );
	}

	/**
	 * @covers \MediaWiki\Extension\UserMerge\MergeUser::movePages
	 */
	public function testMovePages(): void {
		$user1 = $this->getNewTestUser();
		$user1->addToDatabase();
		$user2 = $this->getNewTestUser();
		$user2->addToDatabase();

		$userpage1 = $user1->getUserPage();
		$this->getExistingTestPage( $userpage1 );

		$userpage2 = $user2->getUserPage();
		$this->assertFalse( $userpage2->exists( IDBAccessObject::READ_LATEST ) );

		$mu = new MergeUser( $user1, $user2,
			$this->createMock( UserMergeLogger::class ),
			$this->getServiceContainer()->getDatabaseBlockStore() );
		$mu->delete( $this->getTestSysop()->getUser(), 'wfMessage' );

		$this->assertTrue( $userpage2->exists( IDBAccessObject::READ_LATEST ) );
	}
}
