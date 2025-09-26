<?php

namespace MediaWiki\Extension\UserMerge\Hooks;

use MediaWiki\HookContainer\HookContainer;
use MediaWiki\User\User;

/**
 * This is a hook runner class, see docs/Hooks.md in core.
 * @internal
 */
class HookRunner implements
	AccountFieldsHook,
	MergeAccountFromToHook,
	AccountDeleteTablesHook,
	DeleteAccountHook
{
	public function __construct(
		private readonly HookContainer $hookContainer,
	) {
	}

	/**
	 * @inheritDoc
	 */
	public function onUserMergeAccountFields( array &$updateFields ) {
		return $this->hookContainer->run(
			'UserMergeAccountFields',
			[ &$updateFields ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onMergeAccountFromTo( User &$oldUser, User &$newUser ) {
		return $this->hookContainer->run(
			'MergeAccountFromTo',
			[ &$oldUser, &$newUser ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onUserMergeAccountDeleteTables( array &$tablesToDelete ) {
		return $this->hookContainer->run(
			'UserMergeAccountDeleteTables',
			[ &$tablesToDelete ]
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onDeleteAccount( User &$oldUser ) {
		return $this->hookContainer->run(
			'DeleteAccount',
			[ &$oldUser ]
		);
	}

}
