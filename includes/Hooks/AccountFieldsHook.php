<?php

namespace MediaWiki\Extension\UserMerge\Hooks;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "UserMergeAccountFields" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface AccountFieldsHook {
	/**
	 * Get fields to merge database references from one user to another user.
	 *
	 * @param array[] &$updateFields
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onUserMergeAccountFields( array &$updateFields );
}
