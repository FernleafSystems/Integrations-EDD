<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
 * Class RetrieveAll
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Retrieve {

	/**
	 * @param int $nCustomerId
	 * @return \EDD_SL_License[]
	 */
	public function allForCustomer( $nCustomerId ) {

		$aParams = array(
			'post_type'   => 'edd_license',
			'post_parent' => 0,
			'fields'      => 'ids',
			'nopaging'    => true,
			'meta_query'  => array(
				array(
					'key'     => '_edd_sl_user_id',
					'value'   => $nCustomerId,
					'compare' => '='
				)
			)
		);

		return array_map(
			function ( $nLicenseId ) {
				return new \EDD_SL_License( $nLicenseId );
			},
			( new \WP_Query() )->query( $aParams )
		);
	}
}