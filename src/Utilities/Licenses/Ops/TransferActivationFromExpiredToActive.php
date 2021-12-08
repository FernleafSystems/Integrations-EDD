<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Wordpress\Services\Utilities\Licenses\EddActions;

class TransferActivationFromExpiredToActive {

	use Consumers\EddDownloadConsumer;
	use Consumers\EddCustomerConsumer;

	/**
	 * @param \EDD_SL_License $oldLicense
	 * @param string          $url
	 * @return \EDD_SL_License|null
	 * @throws \LogicException
	 */
	public function transfer( \EDD_SL_License $oldLicense, string $url ) :?\EDD_SL_License {
		$newLicense = null;

		if ( !$oldLicense->is_expired() && $oldLicense->status !== 'disabled' ) {
			throw new \LogicException( 'Attempting to transfer license from a non-expired license' );
		}

		$url = EddActions::CleanUrl( $url );

		$maybeLic = ( new FindLicenseWithAvailableSlot() )
			->setEddDownload( $this->getEddDownload() )
			->setEddCustomer( $this->getEddCustomer() )
			->find();
		if ( $maybeLic instanceof \EDD_SL_License && $maybeLic->add_site( $url ) ) {
			$maybeLic->status = 'active';
			$newLicense = $maybeLic;
			$oldLicense->remove_site( $url );
		}

		return $newLicense;
	}
}