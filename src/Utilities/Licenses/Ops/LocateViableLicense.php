<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\Retrieve;

/**
 * Class LocateViableLicense
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops
 */
class LocateViableLicense {

	use Consumers\EddDownloadConsumer;
	use Consumers\EddCustomerConsumer;

	/**
	 * If we're processing this, we must assume that an valid, active license was not found.
	 *
	 * Will attempt to locate a URL attached to an expired license and then try to
	 * add this site to an available active license.
	 * @param string $sUrl
	 * @return \EDD_SL_License|null
	 */
	public function locate( $sUrl ) :?\EDD_SL_License {
		$oLicHome = null;

		$oC = $this->getEddCustomer();

		$aActive = [];
		$aExpired = [];
		foreach ( ( new Retrieve() )->forUrl( $sUrl ) as $oAct ) {
			$oLic = new \EDD_SL_License( $oAct->license_id );
			if ( !empty( $oC ) && $oLic->customer_id !== $oC->id ) {
				continue;
			}
			if ( $oLic->get_download()->get_ID() !== $this->getEddDownload()->get_ID() ) {
				continue;
			}

			if ( $oLic->is_expired() ) {
				$aExpired[] = $oLic;
			}
			else {
				$aActive[] = $oLic;
			}
		}

		$bRunLicenseClean = false;
		if ( !empty( $aActive ) ) {
			$oLicHome = array_pop( $aActive );
			if ( empty( $oC ) ) {
				$oC = new \EDD_Customer( $oLicHome->customer_id );
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
				$oLicHome = ( new TransferActivationFromExpiredToActive() )
					->setEddCustomer( $oC )
					->setEddDownload( $this->getEddDownload() )
					->transfer( $oExpiredLic, $sUrl );
				$bRunLicenseClean = true;
			}
		}

		if ( $bRunLicenseClean && $oLicHome instanceof \EDD_SL_License ) {
			if ( $oC instanceof \EDD_Customer ) {
				( new CleanDuplicatedSiteActivations() )
					->setEddCustomer( $oC )
					->setEddDownload( $this->getEddDownload() )
					->clean();
			}
		}

		return $oLicHome;
	}
}