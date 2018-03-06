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
		$aLicenses = array_map(
			function ( $nLicenseId ) {
				return new \EDD_SL_License( $nLicenseId );
			},
			$this->runQuery( $aQueryParams, $aMetaQuery )
		);

		if ( !empty( $this->getEddDownload() ) ) {
			$aLicenses = $this->filterForDownload( $aLicenses );
		}

		return $aLicenses;
	}

	/**
	 * @param \EDD_SL_License[] $aLicenses
	 * @return \EDD_SL_License[]
	 */
	protected function filterForDownload( $aLicenses ) {
		$oDownload = $this->getEddDownload();
		return array_values( array_filter(
			$aLicenses,
			function ( $oLicense ) use ( $oDownload ) {
				/** @var \EDD_SL_License $oLicense */
				return ( $oLicense->download_id === $oDownload->ID );
			}
		) );
	}

	/**
	 * @param array $aQueryParams
	 * @param array $aMetaQuery
	 * @return int[] - license record IDs
	 */
	public function runQuery( $aQueryParams = array(), $aMetaQuery = array() ) {

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
		return ( new \WP_Query() )->query( $aParams );
	}
}