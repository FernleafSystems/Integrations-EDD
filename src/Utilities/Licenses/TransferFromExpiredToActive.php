<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
 * @deprecated
 */
class TransferFromExpiredToActive extends Retrieve {

	/**
	 * If we're processing this, we must assume that an valid, active license was not found.
	 *
	 * Will attempt to locate a URL attached to an expired license and then try to
	 * add this site to an available active license.
	 * @param string $sUrl
	 * @return \EDD_SL_License|null
	 */
	public function transferSite( $sUrl ) {
		$oAvailableLicense = null;

		$oFinder = ( new Find() )->setEddDownload( $this->getEddDownload() );

		// 1. Check whether the URL is attached to an expired license
		$oExpiredLicense = $oFinder->withActiveSite( $sUrl, true );
		if ( !empty( $oExpiredLicense ) && $oExpiredLicense->is_expired() ) {

			$oCustomer = new \EDD_Customer( $oExpiredLicense->customer_id );
			$oAvailableLicense = $oFinder->setEddCustomer( $oCustomer )
										 ->withActivationSlot();

			if ( !empty( $oAvailableLicense ) && $oAvailableLicense->add_site( $sUrl ) ) {
				$oAvailableLicense->status = 'active';
				$oExpiredLicense->remove_site( $sUrl );
			}
		}

		return $oAvailableLicense;
	}
}