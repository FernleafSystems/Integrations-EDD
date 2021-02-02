<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\EddActivationVO;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations\Retrieve;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\LicensesIterator;

/**
 * Class CleanDuplicatedSiteActivations
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops
 */
class CleanDuplicatedSiteActivations {

	use Consumers\EddDownloadConsumer;
	use Consumers\EddCustomerConsumer;

	public function clean() {
		$oEDDSL = edd_software_licensing();

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
						$lic = $oEDDSL->get_license( $oExpiredAct->license_id );
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
					$oCurrent = $oEDDSL->get_license( array_pop( $activeActs )->license_id );
					foreach ( $activeActs as $oActiveAct ) {
						$lic = $oEDDSL->get_license( $oActiveAct->license_id );
						if ( $lic instanceof \EDD_SL_License && $lic->expiration > 0 ) {
							if ( $lic->expiration > $oCurrent->expiration ) {
								error_log( sprintf( 'Has Active, remove expired- remove %s from %s', $url, $oCurrent->ID ) );
								$oCurrent->remove_site( $url );
								$oCurrent = $lic;
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
	private function getActivationsSortedBySite() {

		$licIT = new LicensesIterator();
		$licIT->filterByCustomer( $this->getEddCustomer()->id );

		/** @var EddActivationVO[][] $activByURL */
		$activByURL = [];
		foreach ( $licIT as $oLic ) {
			foreach ( ( new Retrieve() )->forLicense( $oLic ) as $oAct ) {
				if ( !isset( $activByURL[ $oAct->site_name ] ) ) {
					$activByURL[ $oAct->site_name ] = [];
				}
				$activByURL[ $oAct->site_name ][] = $oAct;
			}
		}

		// Keep only the URLs where their activation count is greater 1
		return array_filter(
			$activByURL,
			fn( $aActs ) => count( $aActs ) > 1
		);
	}
}