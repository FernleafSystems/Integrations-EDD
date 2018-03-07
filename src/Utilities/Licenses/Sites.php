<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;

/**
 * Class Sites
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Sites {

	use EddCustomerConsumer,
		EddDownloadConsumer;

	/**
	 * @var array
	 */
	private $aLastResults;

	/**
	 * @return $this
	 */
	public function runLookup() {
		$oRetriever = ( new Retrieve() )
			->setEddCustomer( $this->getEddCustomer() )
			->setEddDownload( $this->getEddDownload() );

		$aAssignedSites = array();
		$aAssignedSitesExpired = array();

		foreach ( $oRetriever->retrieve() as $oLicense ) {
			if ( in_array( $oLicense->status, array( 'active', 'inactive' ) ) ) {
				$aAssignedSites = array_merge( $aAssignedSites, $oLicense->sites );
			}
			else {
				$aAssignedSitesExpired = array_merge( $aAssignedSitesExpired, $oLicense->sites );
			}
		}

		$this->aLastResults = array(
			'sites'         => $aAssignedSites,
			'sites_expired' => $aAssignedSitesExpired,
		);
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
		if ( empty( $this->aLastResults ) || !is_array( $this->aLastResults ) ) {
			$this->aLastResults = array(
				'sites'         => array(),
				'sites_expired' => array(),
			);
		}
		return $this->aLastResults;
	}

	/**
	 * @return $this
	 */
	public function reset() {
		$this->aLastResults = array();
		return $this;
	}
}