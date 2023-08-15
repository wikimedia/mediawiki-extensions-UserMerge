<?php

namespace MediaWiki\Extension\UserMerge\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "UserMergeAccountDeleteTables" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface AccountDeleteTablesHook {
	/**
	 * Delete tables to delete users from following a successful mergeUser call.
	 *
	 * @param array &$tablesToDelete
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onUserMergeAccountDeleteTables( array &$tablesToDelete );
}
