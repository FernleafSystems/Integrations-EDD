<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * @property int    $site_id
 * @property string $site_name
 * @property int    $license_id
 * @property int    $activated
 * @property int    $is_local
 * @property bool   $lic_expired - dynamic
 */
class EddActivationVO {

	use StdClassAdapter {
		__get as __adapterGet;
	}

	/**
	 * @param string $sProperty
	 * @return mixed
	 */
	public function __get( $sProperty ) {

		$mVal = $this->__adapterGet( $sProperty );

		switch ( $sProperty ) {

			case 'lic_expired':
				if ( is_null( $mVal ) ) {
					$mVal = ( new \EDD_SL_License( $this->license_id ) )->is_expired();
					$this->lic_expired = $mVal;
				}
				break;

			default:
				break;
		}

		return $mVal;
	}
}