<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
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
			elseif ( $oLicense->status == 'expired' ) {
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
	public function getSites() :array {
		return $this->getLastResults()[ 'sites' ];
	}

	/**
	 * @return string[]
	 */
	public function getExpiredSites() :array {
		return $this->getLastResults()[ 'sites_expired' ];
	}

	/**
	 * @return string[][]
	 */
	public function getLastResults() :array {
		$results = parent::getLastResults();
		if ( empty( $results ) ) {
			$this->setLastResults( [
				'sites'         => [],
				'sites_expired' => [],
			] );
		}
		return parent::getLastResults();
	}
}