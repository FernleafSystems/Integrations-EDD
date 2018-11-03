<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
 * Class Sites
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Sites extends Retrieve {

	/**
	 * @return $this
	 */
	public function runLookup() {
		$aAssignedSites = array();
		$aAssignedSitesExpired = array();

		foreach ( $this->retrieve() as $oLicense ) {
			if ( in_array( $oLicense->status, array( 'active', 'inactive' ) ) ) {
				$aAssignedSites = array_merge( $aAssignedSites, $oLicense->sites );
			}
			else {
				$aAssignedSitesExpired = array_merge( $aAssignedSitesExpired, $oLicense->sites );
			}
		}

		$this->setLastResults( [
			'sites'         => $aAssignedSites,
			'sites_expired' => $aAssignedSitesExpired,
		] );
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getSites() {
		return $this->getLastResults()[ 'sites' ];
	}

	/**
	 * @return string[]
	 */
	public function getExpiredSites() {
		return $this->getLastResults()[ 'sites_expired' ];
	}

	/**
	 * @return string[][]
	 */
	public function getLastResults() {
		$aRes = parent::getLastResults();
		if ( empty( $aRes ) ) {
			$this->setLastResults( [
				'sites'         => array(),
				'sites_expired' => array(),
			] );
		}
		return parent::getLastResults();
	}
}