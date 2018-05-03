<?php

/**
 * Class ApiUserMerge
 *
 * Implements action=usermerge to provide user account merge via the API.
 *
 * @ingroup API
 * @ingroup Extensions
 */
class ApiUserMerge extends ApiBase {

	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}

	public function execute() {
		if ( !$this->getUser()->isAllowed( 'usermerge' ) ) {
			$this->dieUsage( 'You don\'t have permission to merge users', 'permissiondenied' );
		}

		$params = $this->extractRequestParams();

		// API requires that boolean params be default false, so invert the 'nodelete'.
		$params['delete'] = !$params['nodelete'];
		unset( $params['nodelete'] );

		$special = new SpecialUserMerge();
		$special->setContext( $this );
		$oldValidate = $special->validateOldUser( $params['olduser'] );
		$newValidate = $special->validateNewUser( $params['newuser'] );
		if ( is_string( $oldValidate ) ) {
			$this->dieStatus( Status::newFatal( $oldValidate ) );
		}
		if ( is_string( $newValidate ) ) {
			$this->dieStatus( Status::newFatal( $newValidate ) );
		}

		// Does a little more validation, and all the processing
		$status = $special->onSubmit( $params );
		if ( !$status->isGood() ) {
			$this->dieStatus( $status );
		}

		$this->getResult()->addValue(
			null,
			$this->getModuleName(),
			array( 'result' => 'success' )
		);
	}

	public function getDescription() {
		return 'Merge a user into another user';
	}

	public function getAllowedParams() {
		return array(
			'olduser' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'newuser' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'nodelete' => array(
				ApiBase::PARAM_TYPE => 'boolean',
				ApiBase::PARAM_DFLT => false,
			),
			'token' => null,
		);
	}

	public function getParamDescription() {
		return array(
			'olduser' => 'Old username to merge from or delete',
			'newuser' => 'Username to merge into, or "Anonymous" to delete the old account',
			'nodelete' => 'Whether not to delete the old account after merging',
			'token' => 'An edit token from action=tokens'
		);
	}

	public function mustBePosted() {
		return true;
	}

	public function needsToken() {
		return 'csrf';
	}

	public function getTokenSalt() {
		return '';
	}

	public function isWriteMode() {
		return true;
	}

	public function getPossibleErrors() {
		return array_merge(
			parent::getPossibleErrors(),
			array(
				array( 'permissiondenied' )
			)
		);
	}

	/**
	* @see ApiBase::getExamplesMessages()
	*/
	public function getExamplesMessages() {
		return array(
			'api.php?action=usermerge&olduser=Jimbo&newuser=Jimbo%20Wales&token=TOKEN'
			=> 'Merge the user "Jimbo" into "Jimbo Wales" and delete the old account'
		);
	}

	public function getHelpUrls() {
		return array( 'https://www.mediawiki.org/wiki/Extension:UserMerge#API' );
	}

}
