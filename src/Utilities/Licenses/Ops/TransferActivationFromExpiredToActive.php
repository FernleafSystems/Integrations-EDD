<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Wordpress\Services\Utilities\Licenses\EddActions;

/**
 * Class TransferActivationFromExpiredToActive
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops
 */
class TransferActivationFromExpiredToActive {

	use Consumers\EddDownloadConsumer;
	use Consumers\EddCustomerConsumer;

	/**
	 * @param \EDD_SL_License $oExpiredLicense
	 * @param string          $sUrl
	 * @return \EDD_SL_License|null
	 * @throws \LogicException
	 */
	public function transfer( \EDD_SL_License $oExpiredLicense, string $sUrl ) :?\EDD_SL_License {
		$oNewLicHome = null;

		if ( !$oExpiredLicense->is_expired() ) {
			throw new \LogicException( 'Attempting to transfer license from a non-expired license' );
		}

		$sUrl = EddActions::CleanUrl( $sUrl );

		$oMaybeLic = ( new FindLicenseWithAvailableSlot() )
			->setEddDownload( $this->getEddDownload() )
			->setEddCustomer( $this->getEddCustomer() )
			->find();
		if ( $oMaybeLic instanceof \EDD_SL_License && $oMaybeLic->add_site( $sUrl ) ) {
			$oMaybeLic->status = 'active';
			$oNewLicHome = $oMaybeLic;
			$oExpiredLicense->remove_site( $sUrl );
		}

		return $oNewLicHome;
	}
}