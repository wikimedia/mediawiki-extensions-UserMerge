<?php

/**
 * @todo this should use the Database group
 */
class MergeUserTest extends MediaWikiTestCase {

	private $counter = 0;

	private function getNewTestUser() {
		$this->counter++;
		$name = __CLASS__ . (string)$this->counter;
		$user = User::newFromName( $name );
		if ( $user->getId() ) { // Already exists, try again.
			return $this->getNewTestUser();
		}
		$user->setName( $name );
		$user->addToDatabase();
		SiteStatsUpdate::factory( [ 'users' => 1 ] )->doUpdate();
		return $user;
	}

	/**
	 * Clear all instance caches
	 *
	 * @param User $u
	 */
	private function reallyClearInstanceCache( User &$u ) {
		$u = User::newFromName( $u->getName() );
	}

	/**
	 * @covers MergeUser::merge
	 */
	public function testBasicMerge() {
		$user1 = $this->getNewTestUser();
		$user1->addToDatabase();
		$user1->setOption( 'foo', 'baz' );
		$user1->saveSettings();
		$user2 = $this->getNewTestUser();
		$user2->addToDatabase();

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );
		$this->assertNull( $user1->getOption( 'foo' ) );
		$this->assertEquals( 'baz', $user2->getOption( 'foo' ) );
	}

	/**
	 * @covers MergeUser::merge
	 */
	public function testMergeOfUserGroups() {
		$user1 = $this->getNewTestUser();
		$user1->addGroup( 'group1' );
		$user1->addGroup( 'group2' );
		$user2 = $this->getNewTestUser();
		$user2->addGroup( 'group2' );

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );

		$this->assertArrayEquals( [ 'group2' ], $user1->getGroups() );
		$this->assertArrayEquals( [ 'group1', 'group2' ], $user2->getGroups() );
	}

	/**
	 * @covers MergeUser::delete
	 */
	public function testDeleteUser() {
		$user1 = $this->getNewTestUser();
		$user2 = $this->getNewTestUser();

		$this->reallyClearInstanceCache( $user1 );
		$this->assertGreaterThan( 0, $user1->getId() );

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->delete( $this->createMock( User::class ), 'wfMessage' );

		$this->reallyClearInstanceCache( $user1 );
		$this->assertSame( 0, $user1->getId() );
	}

	/**
	 * @covers MergeUser::mergeEditcount
	 */
	public function testMergeEditcount() {
		$user1 = $this->getNewTestUser();
		$user2 = $this->getNewTestUser();
		$count = 0;
		$user1->incEditCount();
		while ( $count < 10 ) {
			$user1->incEditCount();
			$user2->incEditCount();
			$count++;
		}

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );
		$this->assertSame( 0, $user1->getEditCount() );
		$this->assertEquals( 21, $user2->getEditCount() );
	}
}
