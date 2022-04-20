<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\EddActivationVO;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\Retrieve;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\LicensesIterator;

class CleanDuplicatedSiteActivations {

	use Consumers\EddDownloadConsumer;
	use Consumers\EddCustomerConsumer;

	public function clean() {
		$eddSL = edd_software_licensing();

		// For each URL, iterate over the activations to determine what state we're in
		// i.e. how many activations do we have for the same URL for active licenses, and expired licenses.
		foreach ( $this->getActivationsSortedBySite() as $url => $activations ) {

			/** @var EddActivationVO[] $activeActs */
			$activeActs = [];
			/** @var EddActivationVO[] $expiredActs */
			$expiredActs = [];
			foreach ( $activations as $activ ) {
				$activ->lic_expired ? ( $expiredActs[] = $activ ) : ( $activeActs[] = $activ );
			}

			if ( count( $activeActs ) > 0 ) {

				// Since there is an active license with this URL, we remove the URL from all expired licenses.
				if ( !empty( $expiredActs ) ) {
					foreach ( $expiredActs as $oExpiredAct ) {
						$lic = $eddSL->get_license( $oExpiredAct->license_id );
						if ( $lic instanceof \EDD_SL_License ) {
							error_log( sprintf( 'Unnecessary expired- remove %s from %s', $url, $oExpiredAct->license_id ) );
							$lic->remove_site( $url );
						}
					}
				}

				/**
				 * There are multiple licenses with the same URL activated for it.
				 * We keep the one that will expire last and remove all the rest.
				 */
				if ( count( $activeActs ) > 1 ) {
					// leave only the most recent active
					$current = $eddSL->get_license( array_pop( $activeActs )->license_id );
					foreach ( $activeActs as $oActiveAct ) {
						$lic = $eddSL->get_license( $oActiveAct->license_id );
						if ( $lic instanceof \EDD_SL_License && $lic->expiration > 0 ) {
							if ( $lic->expiration > $current->expiration ) {
								error_log( sprintf( 'Has Active, remove expired- remove %s from %s', $url, $current->ID ) );
								$current->remove_site( $url );
								$current = $lic;
							}
							else {
								error_log( sprintf( 'Has Active, remove expired- remove %s from %s', $url, $lic->ID ) );
								$lic->remove_site( $url );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * @return EddActivationVO[][]
	 */
	private function getActivationsSortedBySite() :array {

		$licIT = new LicensesIterator();
		$licIT->filterByCustomer( $this->getEddCustomer()->id );

		/** @var EddActivationVO[][] $byURLs */
		$byURLs = [];
		foreach ( $licIT as $lic ) {
			if ( !empty( $lic ) ) {
				foreach ( ( new Retrieve() )->forLicense( $lic ) as $activation ) {
					if ( !isset( $byURLs[ $activation->site_name ] ) ) {
						$byURLs[ $activation->site_name ] = [];
					}
					$byURLs[ $activation->site_name ][] = $activation;
				}
			}
		}

		// Keep only the URLs where their activation count is greater 1
		return array_filter( $byURLs, fn( $activations ) => count( $activations ) > 1 );
	}
}