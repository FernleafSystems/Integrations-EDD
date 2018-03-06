<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;

/**
 * Class Retrieve
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Retrieve {

	use EddCustomerConsumer;

	/**
	 * @param int $nDownloadId
	 * @return \EDD_SL_License[]
	 */
	public function allForDownload( $nDownloadId ) {
		return array_values( array_filter(
			$this->retrieve(),
			function ( $oLicense ) use ( $nDownloadId ) {
				/** @var \EDD_SL_License $oLicense */
				return ( $oLicense->download_id === $nDownloadId );
			}
		) );
	}

	/**
	 * @param array $aQueryParams
	 * @param array $aMetaQuery
	 * @return \EDD_SL_License[]
	 */
	public function retrieve( $aQueryParams = array(), $aMetaQuery = array() ) {

		$oCustomer = $this->getEddCustomer();
		if ( !empty( $oCustomer ) ) {
			$aMetaQuery[] = array(
				'key'     => '_edd_sl_user_id',
				'value'   => $oCustomer->id,
				'compare' => '='
			);
		}

		$aParams = array_merge(
			array(
				'post_type'   => 'edd_license',
				'post_parent' => 0,
				'fields'      => 'ids',
				'nopaging'    => true,
				'meta_query'  => $aMetaQuery,
			),
			$aQueryParams
		);

		return array_map(
			function ( $nLicenseId ) {
				return new \EDD_SL_License( $nLicenseId );
			},
			( new \WP_Query() )->query( $aParams )
		);
	}
}