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

		$aPossible = [];
		foreach ( $this->getLicIterator() as $oLic ) {
			if ( !$oLic->is_expired() &&
				 $oLic->get_download()->get_ID() == $this->getEddDownload()->get_ID() &&
				 $oLic->activation_count < $oLic->activation_limit ) {
				$aPossible[] = $oLic;
			}
		}

		$oTheLic = null;
		if ( !empty( $aPossible ) ) {
			$oTheLic = array_pop( $aPossible );
			foreach ( $aPossible as $oMaybeLic ) {
				if ( $oMaybeLic->expiration > $oTheLic->expiration ) {
					$oTheLic = $oMaybeLic;
				}
			}
		}

		return $oTheLic;
	}
}