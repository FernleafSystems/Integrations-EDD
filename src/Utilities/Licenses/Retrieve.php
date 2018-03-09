<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;

/**
 * Class Retrieve
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Retrieve {

	use EddCustomerConsumer,
		EddDownloadConsumer;

	/**
	 * @param array $aQueryParams
	 * @param array $aMetaQuery
	 * @return \EDD_SL_License[]
	 */
	public function retrieve( $aQueryParams = array(), $aMetaQuery = array() ) {
		return array_map(
			function ( $nLicenseId ) {
				return new \EDD_SL_License( $nLicenseId );
			},
			$this->runQuery( $aQueryParams, $aMetaQuery )
		);
	}

	/**
	 * @param array $aQueryParams
	 * @param array $aMetaQuery
	 * @return int[] - license record IDs
	 */
	public function runQuery( $aQueryParams = array(), $aMetaQuery = array() ) {

		if ( !is_array( $aMetaQuery ) ) {
			$aMetaQuery = [];
		}

		if ( !empty( $this->getEddCustomer() ) ) {
			$aMetaQuery[] = array(
				'key'     => '_edd_sl_user_id',
				'value'   => $this->getEddCustomer()->user_id,
				'compare' => '='
			);
		}

		if ( !empty( $this->getEddDownload() ) ) {
			$aMetaQuery[] = array(
				'key'     => '_edd_sl_download_id',
				'value'   => $this->getEddDownload()->get_ID(),
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
		return ( new \WP_Query() )->query( $aParams );
	}
}