<?php

class UserMergeLogger implements IUserMergeLogger {

	/**
	 * Adds a merge log entry
	 *
	 * @param User $performer
	 * @param User $oldUser
	 * @param User $newUser
	 */
	public function addMergeEntry( User $performer, User $oldUser, User $newUser ) {
		$logEntry = new ManualLogEntry( 'usermerge', 'mergeuser' );
		$logEntry->setPerformer( $performer );
		$logEntry->setTarget( $newUser->getUserPage() );
		$logEntry->setParameters( [
			'oldName' => $oldUser->getName(),
			'oldId' => $oldUser->getId(),
			'newName' => $newUser->getName(),
			'newId' => $newUser->getId(),
		] );
		$logEntry->setRelations( [ 'oldname' => $oldUser->getName() ] );
		$logEntry->insert();
	}

	/**
	 * Adds a user deletion log entry
	 *
	 * @param User $performer
	 * @param User $oldUser
	 */
	public function addDeleteEntry( User $performer, User $oldUser ) {
		$logEntry = new ManualLogEntry( 'usermerge', 'deleteuser' );
		$logEntry->setPerformer( $performer );
		$logEntry->setTarget( $oldUser->getUserPage() );
		$logEntry->setParameters( [
			'oldName' => $oldUser->getName(),
			'oldId' => $oldUser->getId(),
		] );
		$logEntry->insert();
	}
}
