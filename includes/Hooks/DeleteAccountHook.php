<?php

namespace MediaWiki\Extension\UserMerge\Hooks;

use MediaWiki\User\User;

/**
 * This is a hook handler interface, see docs/Hooks.md in core.
 * Use the hook name "DeleteAccount" to register handlers implementing this interface.
 *
 * @stable to implement
 * @ingroup Hooks
 */
interface DeleteAccountHook {
	/**
	 * Delete user following a successful mergeUser call.
	 *
	 * @param User &$oldUser
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onDeleteAccount( User &$oldUser );
}
