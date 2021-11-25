<?php
/**
 * @todo this should better use the Database group
 * @group Database
 */
class MergeUserTest extends MediaWikiIntegrationTestCase {

	/** @var int */
	private $counter = 0;

	private function getNewTestUser() {
		$this->counter++;
		$name = __CLASS__ . (string)$this->counter;
		$user = User::newFromName( $name );
		if ( $user->getId() ) {
			// Already exists, try again.
			return $this->getNewTestUser();
		}
		$user->setName( $name );
		$user->addToDatabase();
		SiteStatsUpdate::factory( [ 'users' => 1 ] )->doUpdate();
		return $user;
	}

	public function setUp(): void {
		parent::setUp();

		$this->tablesUsed[] = 'page';
		$this->tablesUsed[] = 'revision';
		$this->tablesUsed[] = 'comment';
		$this->tablesUsed[] = 'user';
	}

	/**
	 * Clear all instance caches
	 *
	 * @param User &$u
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
		$userOptionsManager = $this->getServiceContainer()->getUserOptionsManager();
		$userOptionsManager->setOption( $user1, 'foo', 'baz' );
		$userOptionsManager->saveOptions( $user1 );
		$user2 = $this->getNewTestUser();
		$user2->addToDatabase();

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );
		$this->assertNull( $userOptionsManager->getOption( $user1, 'foo' ) );
		$this->assertEquals( 'baz', $userOptionsManager->getOption( $user2, 'foo' ) );
	}

	/**
	 * @covers MergeUser::merge
	 */
	public function testMergeOfUserGroups() {
		$user1 = $this->getNewTestUser();
		$userGroupManager = $this->getServiceContainer()->getUserGroupManager();
		$userGroupManager->addUserToGroup( $user1, 'group1' );
		$userGroupManager->addUserToGroup( $user1, 'group2' );
		$user2 = $this->getNewTestUser();
		$userGroupManager->addUserToGroup( $user2, 'group2' );

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->merge( $this->createMock( User::class ) );

		$this->reallyClearInstanceCache( $user1 );
		$this->reallyClearInstanceCache( $user2 );

		$this->assertArrayEquals( [ 'group2' ], $userGroupManager->getUserGroups( $user1 ) );
		$this->assertArrayEquals( [ 'group1', 'group2' ], $userGroupManager->getUserGroups( $user2 ) );
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

	/**
	 * @covers MergeUser::movePages
	 */
	public function testMovePages() {
		$user1 = $this->getNewTestUser();
		$user1->addToDatabase();
		$user2 = $this->getNewTestUser();
		$user2->addToDatabase();

		$userpage1 = $user1->getUserPage();
		$this->getExistingTestPage( $userpage1 );

		$userpage2 = $user2->getUserPage();
		$this->assertFalse( $userpage2->exists( Title::READ_LATEST ) );

		$mu = new MergeUser( $user1, $user2, $this->createMock( UserMergeLogger::class ) );
		$mu->delete( $this->getTestSysop()->getUser(), 'wfMessage' );

		$this->assertTrue( $userpage2->exists( Title::READ_LATEST ) );
	}
}
