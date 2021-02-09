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

		foreach ( $this->getLicIterator() as $lic ) {

			if ( empty( $lic ) || $lic->status === 'disabled' ) {
				continue;
			}
			elseif ( $lic->is_expired() ) {

				$this->assigned_expired += $lic->activation_count;
				$this->limit_expired += $lic->license_limit();
			}
			else {
				$this->assigned += $lic->activation_count;
				if ( $lic->activation_limit <= 0 ) {
					$this->unlimited = true;
				}
				else {
					$this->limit += $lic->license_limit();
				}
			}
		}
	}
}