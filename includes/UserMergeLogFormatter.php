<?php

class UserMergeLogFormatter extends LogFormatter {

	private static $mapLegacy = [
		'oldName' => 0,
		'oldId' => 1,
		'newName' => 2,
		'newId' => 3,
	];

	/**
	 * @return string
	 */
	protected function getMessageKey() {
		if ( $this->entry->getSubtype() === 'deleteuser' ) {
			return 'logentry-usermerge-deleteuser';
		} else {
			return 'logentry-usermerge-mergeuser';
		}
	}

	/**
	 * @param string $param
	 * @return mixed
	 */
	private function getParameter( $param ) {
		$parameters = $this->entry->getParameters();
		if ( $this->entry->isLegacy() ) {
			$param = self::$mapLegacy[$param];
		}
		return $parameters[$param];
	}

	/**
	 * @return array
	 */
	protected function extractParameters() {
		$params = [];
		// 0-2 are set in LogFormatter::getMessageParameters()
		$params[3] = $this->getParameter( 'oldName' );
		$params[4] = $this->getParameter( 'oldId' );
		if ( $this->entry->getSubtype() !== 'deleteuser' ) {
			$params[5] = $this->formatParameterValue(
				'user-link', $this->getParameter( 'newName' ) );
			$params[6] = $this->getParameter( 'newId' );
			$params[7] = $this->formatParameterValue(
				'user', $this->getParameter( 'newName' ) );
		}
		return $params;
	}

}
