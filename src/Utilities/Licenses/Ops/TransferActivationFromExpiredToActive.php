<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\Retrieve;

/**
 * Class TransferActivationFromExpiredToActive
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops
 */
class TransferActivationFromExpiredToActive {

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
	public function transfer( $sUrl ) :?\EDD_SL_License {
		$oNewLicHome = null;

		$aActive = [];
		$aExpired = [];
		foreach ( ( new Retrieve() )->forUrl( $sUrl ) as $oAct ) {
			$oLic = new \EDD_SL_License( $oAct->license_id );
			if ( $oLic->customer_id !== $this->getEddCustomer()->id ) {
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

		if ( empty( $aActive ) && !empty( $aExpired ) ) {
			$oNewLicHome = ( new FindLicenseWithAvailableSlot() )
				->setEddDownload( $this->getEddDownload() )
				->setEddCustomer( $this->getEddCustomer() )
				->find();
			if ( $oNewLicHome instanceof \EDD_SL_License && $oNewLicHome->add_site( $sUrl ) ) {
				$oNewLicHome->status = 'active';
				foreach ( $aExpired as $oExpiredLic ) {
					$oExpiredLic->remove_site( $sUrl );
				}
			}
		}

		return $oNewLicHome;
	}
}