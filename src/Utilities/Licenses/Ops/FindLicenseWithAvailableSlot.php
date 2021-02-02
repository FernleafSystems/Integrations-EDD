<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Utilities\Licenses\BaseLicenses;

/**
 * Class FindLicenseWithEmptySlot
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops
 */
class FindLicenseWithAvailableSlot extends BaseLicenses {

	/**
	 * @return \EDD_SL_License|null
	 */
	public function find() :?\EDD_SL_License {

		$possible = [];
		foreach ( $this->getLicIterator() as $lic ) {
			if ( !$lic->is_expired() &&
				 $lic->get_download()->get_ID() == $this->getEddDownload()->get_ID() &&
				 $lic->activation_count < $lic->activation_limit ) {
				$possible[] = $lic;
			}
		}

		$theLicense = null;
		if ( !empty( $possible ) ) {
			$theLicense = array_pop( $possible );
			foreach ( $possible as $maybeLic ) {
				if ( $maybeLic->expiration > $theLicense->expiration ) {
					$theLicense = $maybeLic;
				}
			}
		}

		return $theLicense;
	}
}