<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * @property int    $site_id
 * @property string $site_name
 * @property int    $license_id
 * @property int    $activated
 * @property int    $is_local
 * @property bool   $lic_expired - dynamic
 */
class EddActivationVO extends DynPropertiesClass {

	public function __get( string $key ) {

		$value = parent::__get( $key );

		switch ( $key ) {

			case 'lic_expired':
				if ( is_null( $value ) ) {
					$value = ( new \EDD_SL_License( $this->license_id ) )->is_expired();
					$this->lic_expired = $value;
				}
				break;

			default:
				break;
		}

		return $value;
	}
}