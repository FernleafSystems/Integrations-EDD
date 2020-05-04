<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
 * Class Sites
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 * @deprecated - use EnumerateSites
 */
class Sites extends Retrieve {

	/**
	 * @return $this
	 */
	public function runLookup() {
		$aAssignedSites = [];
		$aAssignedSitesExpired = [];

		foreach ( $this->retrieve() as $oLicense ) {
			if ( in_array( $oLicense->status, [ 'active', 'inactive' ] ) ) {
				$aAssignedSites = array_merge( $aAssignedSites, $oLicense->sites );
			}
			else if ( $oLicense->status == 'expired' ) {
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
				'sites'         => [],
				'sites_expired' => [],
			] );
		}
		return parent::getLastResults();
	}
}