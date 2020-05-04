<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

/**
 * Class Counts
 * @package FernleafSystems\Wordpress\Plugin\EddKeyless\Module\Keyless\Lib\Licenses
 * @property bool $unlimited
 * @property int  $limit
 * @property int  $assigned
 * @property int  $limit_expired
 * @property int  $assigned_expired
 */
class ActivationCounts extends BaseState {

	protected function run() {

		foreach ( $this->getLicIterator() as $oLicense ) {

			if ( $oLicense->is_expired() ) {

				$this->assigned_expired += $oLicense->activation_count;
				$this->limit_expired += $oLicense->license_limit();
			}
			else {
				$this->assigned += $oLicense->activation_count;
				if ( $oLicense->activation_limit <= 0 ) {
					$this->unlimited = true;
				}
				else {
					$this->limit += $oLicense->license_limit();
				}
			}
		}
	}
}