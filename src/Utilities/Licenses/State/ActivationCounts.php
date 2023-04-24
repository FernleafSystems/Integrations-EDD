<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

/**
 * @property bool $unlimited
 * @property int  $limit
 * @property int  $assigned
 * @property int  $limit_expired
 * @property int  $assigned_expired
 */
class ActivationCounts extends BaseState {

	public function __get( string $key ) {
		$value = parent::__get( $key );
		switch ( $key ) {
			case 'assigned':
			case 'assigned_expired':
			case 'limit':
			case 'limit_expired':
				$value = (int)$value;
				break;
			default:
				break;
		}
		return $value;
	}

	protected function run() {

		foreach ( $this->getLicenseIterator() as $lic ) {

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