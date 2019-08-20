<?php

interface IUserMergeLogger {

	/**
	 * Adds a merge log entry
	 *
	 * @param User $performer
	 * @param User $oldUser
	 * @param User $newUser
	 */
	public function addMergeEntry( User $performer, User $oldUser, User $newUser );

	/**
	 * Adds a user deletion log entry
	 *
	 * @param User $performer
	 * @param User $oldUser
	 */
	public function addDeleteEntry( User $performer, User $oldUser );
}
