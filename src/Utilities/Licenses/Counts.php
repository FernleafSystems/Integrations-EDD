<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;

/**
 * Ensure that you use reset() if anything changes.
 * Class Counts
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Counts {

	use EddCustomerConsumer,
		EddDownloadConsumer;

	/**
	 * @var array
	 */
	private $aLastResults;

	/**
	 * @return $this
	 */
	public function runCount() {
		$oRetriever = ( new Retrieve() )
			->setEddCustomer( $this->getEddCustomer() )
			->setEddDownload( $this->getEddDownload() );

		$nTotalActivationLimit = 0;
		$nTotalActivationsNonExpired = 0;
		$nTotalActivationsExpired = 0;
		$nTotalActivationLimitExpired = 0;
		$bUnlimited = false;

		foreach ( $oRetriever->retrieve() as $oLicense ) {

			if ( $oLicense->is_expired() ) {
				$nTotalActivationsExpired += $oLicense->activation_count;
				$nTotalActivationLimitExpired += $oLicense->license_limit();
			}
			else {
				if ( $oLicense->activation_limit <= 0  ) {
					$bUnlimited = true;
					break;
				}

				$nTotalActivationsNonExpired += $oLicense->activation_count;
				$nTotalActivationLimit += $oLicense->license_limit();
			}
		}
		$nTotalActivationsUnused = $nTotalActivationLimit - $nTotalActivationsNonExpired;

		$this->aLastResults = array(
			'unlimited'         => $bUnlimited,
			'available'         => $nTotalActivationLimit,
			'assigned'          => $nTotalActivationsNonExpired,
			'unassigned'        => $nTotalActivationsUnused,
			'available_expired' => $nTotalActivationLimitExpired,
			'assigned_expired'  => $nTotalActivationsExpired,
		);
		return $this;
	}

	/**
	 * @return int
	 */
	public function getAssigned() {
		return $this->getLastResults()[ 'assigned' ];
	}

	/**
	 * @return int
	 */
	public function getAvailable() {
		return $this->getLastResults()[ 'available' ];
	}

	/**
	 * @return int
	 */
	public function getExpiredAssigned() {
		return $this->getLastResults()[ 'assigned_expired' ];
	}

	/**
	 * @return int
	 */
	public function getExpiredAvailable() {
		return $this->getLastResults()[ 'available_expired' ];
	}

	/**
	 * @return int
	 */
	public function getUnassigned() {
		return $this->getLastResults()[ 'unassigned' ];
	}

	/**
	 * @return bool
	 */
	public function isUnlimited() {
		return $this->getLastResults()[ 'unlimited' ];
	}

	/**
	 * @return int[]
	 */
	public function getLastResults() {
		return is_array( $this->aLastResults ) ? $this->aLastResults : array();
	}

	/**
	 * @return $this
	 */
	public function reset() {
		$this->aLastResults = array();
		return $this;
	}
}