<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
 * Ensure that you use reset() if anything changes.
 * @deprecated - use ActivationCounts
 */
class Counts extends Retrieve {

	/**
	 * @return $this
	 */
	public function runCount() {
		$nTotalActivationLimit = 0;
		$nTotalActivationsNonExpired = 0;
		$nTotalActivationsExpired = 0;
		$nTotalActivationLimitExpired = 0;
		$bUnlimited = false;

		foreach ( $this->retrieve() as $lic ) {

			if ( $lic->is_expired() ) {
				$nTotalActivationsExpired += $lic->activation_count;
				$nTotalActivationLimitExpired += $lic->license_limit();
			}
			else {
				$nTotalActivationsNonExpired += $lic->activation_count;
				if ( $lic->activation_limit <= 0 ) {
					$bUnlimited = true;
				}
				else {
					$nTotalActivationLimit += $lic->license_limit();
				}
			}
		}

		$this->setLastResults( [
			'unlimited'        => $bUnlimited,
			'limit'            => $nTotalActivationLimit,
			'assigned'         => $nTotalActivationsNonExpired,
			'limit_expired'    => $nTotalActivationLimitExpired,
			'assigned_expired' => $nTotalActivationsExpired,
		] );
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
	public function getActivationLimit() {
		return $this->getLastResults()[ 'limit' ];
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
	public function getExpiredLimit() {
		return $this->getLastResults()[ 'limit_expired' ];
	}

	/**
	 * @return int - PHP_INT_MAX if unlimited
	 */
	public function getUnassigned() {
		return $this->isUnlimited() ? PHP_INT_MAX : ( $this->getActivationLimit() - $this->getAssigned() );
	}

	public function hasAvailable() :bool {
		return $this->isUnlimited() || $this->getUnassigned() > 0;
	}

	/**
	 * @return bool
	 */
	public function isUnlimited() {
		return $this->getLastResults()[ 'unlimited' ];
	}
}