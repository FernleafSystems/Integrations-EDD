<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;

/**
 * Class Find
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Find {

	use EddCustomerConsumer,
		EddDownloadConsumer;

	/**
	 * @return \EDD_SL_License|null
	 */
	public function withActivationSlot() {
		$oRetriever = ( new Retrieve() )
			->setEddCustomer( $this->getEddCustomer() )
			->setEddDownload( $this->getEddDownload() );

		$oTheLicense = null;
		foreach ( $oRetriever->retrieve() as $oLicense ) {
			if ( in_array( $oLicense->status, array( 'active', 'inactive' ) ) ) {
				if ( $oLicense->license_limit() > $oLicense->activation_count ) {
					$oTheLicense = $oLicense;
					break;
				}
			}
		}
		return $oTheLicense;
	}

	/**
	 * @param string $sUrl
	 * @param bool   $bIncludeExpired
	 * @return \EDD_SL_License
	 */
	public function withActiveSite( $sUrl, $bIncludeExpired = false ) {
		$oTheLicense = null;

		$aLicensesByMeta = $this->withActiveSiteUsingMetaQuery( $sUrl );
		if ( !empty( $aLicensesByMeta ) ) {
			foreach ( $aLicensesByMeta as $oLic ) {
				if ( ( $bIncludeExpired || !$oLic->is_expired() ) && in_array( $sUrl, $oLic->sites ) ) {
					$oTheLicense = $oLic;
					break;
				}
			}
		}

		if ( empty( $oTheLicense ) ) {
			$oRetriever = ( new Retrieve() )
				->setEddCustomer( $this->getEddCustomer() )
				->setEddDownload( $this->getEddDownload() );

			foreach ( $oRetriever->retrieve() as $oLic ) {
				if ( ( $bIncludeExpired || !$oLic->is_expired() ) && in_array( $sUrl, $oLic->sites ) ) {
					$oTheLicense = $oLic;
					break;
				}
			}
		}

		return $oTheLicense;
	}

	/**
	 * Assumes the site url passed here is clean and ready to go
	 * @param string $sUrl
	 * @return \EDD_SL_License[]
	 */
	public function withActiveSiteUsingMetaQuery( $sUrl ) {
		$aMeta = array(
			'key'     => '_edd_sl_sites',
			'value'   => '%:"'.$sUrl.'";%',
			'compare' => 'LIKE'
		);
		return ( new Retrieve() )
			->setEddCustomer( $this->getEddCustomer() )
			->setEddDownload( $this->getEddDownload() )
			->retrieve( array(), $aMeta );
	}
}