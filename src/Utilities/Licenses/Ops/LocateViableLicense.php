<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\Retrieve;

class LocateViableLicense {

	use Consumers\EddDownloadConsumer;
	use Consumers\EddCustomerConsumer;

	/**
	 * If we're processing this, we must assume that an valid, active license was not found.
	 *
	 * Will attempt to locate a URL attached to an expired license and then try to
	 * add this site to an available active license.
	 * @param string $url
	 * @return \EDD_SL_License|null
	 */
	public function locate( string $url ) :?\EDD_SL_License {
		$licHome = null;

		$oC = $this->getEddCustomer();

		$aActive = [];
		$aExpired = [];
		foreach ( ( new Retrieve() )->forUrl( $url ) as $oAct ) {
			$lic = new \EDD_SL_License( $oAct->license_id );
			if ( !empty( $oC ) && $lic->customer_id !== $oC->id ) {
				continue;
			}
			if ( $lic->get_download()->get_ID() !== $this->getEddDownload()->get_ID() ) {
				continue;
			}

			if ( $lic->status === 'disabled' || $lic->is_expired() ) {
				$aExpired[] = $lic;
			}
			else {
				$aActive[] = $lic;
			}
		}

		$bRunLicenseClean = false;
		if ( !empty( $aActive ) ) {
			$licHome = array_pop( $aActive );
			if ( empty( $oC ) ) {
				$oC = new \EDD_Customer( $licHome->customer_id );
			}
			$bRunLicenseClean = $oC instanceof \EDD_Customer && !empty( $aActive ); // multiple active for site.
		}
		elseif ( empty( $aActive ) && !empty( $aExpired ) ) {
			/** @var \EDD_SL_License $oExpiredLic */
			$oExpiredLic = reset( $aExpired );
			if ( empty( $oC ) ) {
				$oC = new \EDD_Customer( $oExpiredLic->customer_id );
			}
			if ( $oC instanceof \EDD_Customer && $oC->id > 0 ) {
				$licHome = ( new TransferActivationFromExpiredToActive() )
					->setEddCustomer( $oC )
					->setEddDownload( $this->getEddDownload() )
					->transfer( $oExpiredLic, $url );
				$bRunLicenseClean = true;
			}
		}

		if ( $bRunLicenseClean && $licHome instanceof \EDD_SL_License ) {
			if ( $oC instanceof \EDD_Customer ) {
				( new CleanDuplicatedSiteActivations() )
					->setEddCustomer( $oC )
					->setEddDownload( $this->getEddDownload() )
					->clean();
			}
		}

		return $licHome;
	}
}