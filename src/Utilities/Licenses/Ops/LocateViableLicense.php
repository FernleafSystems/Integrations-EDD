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
	 */
	public function locate( string $url ) :?\EDD_SL_License {
		$licHome = null;

		$c = $this->getEddCustomer();

		$activeLicenses = [];
		$expired = [];
		foreach ( ( new Retrieve() )->forUrl( $url ) as $oAct ) {
			$lic = new \EDD_SL_License( $oAct->license_id );
			if ( !empty( $c ) && $lic->customer_id !== $c->id ) {
				continue;
			}
			if ( $lic->get_download()->get_ID() !== $this->getEddDownload()->get_ID() ) {
				continue;
			}

			if ( $lic->status === 'disabled' || $lic->is_expired() ) {
				$expired[] = $lic;
			}
			else {
				$activeLicenses[] = $lic;
			}
		}

		$runLicenseClean = false;
		if ( !empty( $activeLicenses ) ) {
			$licHome = array_pop( $activeLicenses );
			if ( empty( $c ) ) {
				$c = new \EDD_Customer( $licHome->customer_id );
			}
			$runLicenseClean = $c instanceof \EDD_Customer && !empty( $activeLicenses ); // multiple active for site.
		}
		elseif ( !empty( $expired ) ) {
			/** @var \EDD_SL_License $expiredLic */
			$expiredLic = reset( $expired );
			if ( empty( $c ) ) {
				$c = new \EDD_Customer( $expiredLic->customer_id );
			}
			if ( $c instanceof \EDD_Customer && $c->id > 0 ) {
				$licHome = ( new TransferActivationFromExpiredToActive() )
					->setEddCustomer( $c )
					->setEddDownload( $this->getEddDownload() )
					->transfer( $expiredLic, $url );
				$runLicenseClean = true;
			}
		}

		if ( $runLicenseClean && $licHome instanceof \EDD_SL_License && $c instanceof \EDD_Customer ) {
			( new CleanDuplicatedSiteActivations() )
				->setEddCustomer( $c )
				->setEddDownload( $this->getEddDownload() )
				->clean();
		}

		return $licHome;
	}
}