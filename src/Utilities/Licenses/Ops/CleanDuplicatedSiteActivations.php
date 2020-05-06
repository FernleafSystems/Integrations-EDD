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
		foreach ( $this->getActivationsSortedBySite() as $sURL => $aActs ) {

			/** @var EddActivationVO[] $aActiveActs */
			$aActiveActs = [];
			/** @var EddActivationVO[] $aExpiredActs */
			$aExpiredActs = [];
			foreach ( $aActs as $oAct ) {
				$oAct->lic_expired ? ( $aExpiredActs[] = $oAct ) : ( $aActiveActs[] = $oAct );
			}

			if ( count( $aActiveActs ) > 0 ) {

				// Since there is an active license with this URL, we remove the URL from all expired licenses.
				if ( !empty( $aExpiredActs ) ) {
					foreach ( $aExpiredActs as $oExpiredAct ) {
						$oLic = $oEDDSL->get_license( $oExpiredAct->license_id );
						if ( $oLic instanceof \EDD_SL_License ) {
							error_log( sprintf( 'Unnecessary expired- remove %s from %s', $sURL, $oExpiredAct->license_id ) );
							$oLic->remove_site( $sURL );
						}
					}
				}

				/**
				 * There are multiple licenses with the same URL activated for it.
				 * We keep the one that will expire last and remove all the rest.
				 */
				if ( count( $aActiveActs ) > 1 ) {
					// leave only the most recent active
					$oCurrent = $oEDDSL->get_license( array_pop( $aActiveActs )->license_id );
					foreach ( $aActiveActs as $oActiveAct ) {
						$oLic = $oEDDSL->get_license( $oActiveAct->license_id );
						if ( $oLic instanceof \EDD_SL_License && $oLic->expiration > 0 ) {
							if ( $oLic->expiration > $oCurrent->expiration ) {
								error_log( sprintf( 'Has Active, remove expired- remove %s from %s', $sURL, $oCurrent->ID ) );
								$oCurrent->remove_site( $sURL );
								$oCurrent = $oLic;
							}
							else {
								error_log( sprintf( 'Has Active, remove expired- remove %s from %s', $sURL, $oLic->ID ) );
								$oLic->remove_site( $sURL );
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

		$oLicIT = new LicensesIterator();
		$oLicIT->filterByCustomer( $this->getEddCustomer()->id );

		/** @var EddActivationVO[][] $aActsByUrl */
		$aActsByUrl = [];
		foreach ( $oLicIT as $oLic ) {
			foreach ( ( new Retrieve() )->forLicense( $oLic ) as $oAct ) {
				if ( !isset( $aActsByUrl[ $oAct->site_name ] ) ) {
					$aActsByUrl[ $oAct->site_name ] = [];
				}
				$aActsByUrl[ $oAct->site_name ][] = $oAct;
			}
		}

		// Keep only the URLs where their activation count is greater 1
		return array_filter(
			$aActsByUrl,
			fn( $aActs ) => count( $aActs ) > 1
		);
	}
}