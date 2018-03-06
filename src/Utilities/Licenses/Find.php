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
	 * @param string $sSiteUrl
	 * @return \EDD_SL_License
	 */
	public function withActiveSite( $sSiteUrl ) {
		$oRetriever = ( new Retrieve() )
			->setEddCustomer( $this->getEddCustomer() )
			->setEddDownload( $this->getEddDownload() );

		$oTheLicense = null;
		foreach ( $oRetriever->retrieve() as $oLicense ) {
			if ( in_array( $sSiteUrl, $oLicense->sites ) ) {
				$oTheLicense = $oLicense;
				break;
			}
		}
		return $oTheLicense;
	}
}