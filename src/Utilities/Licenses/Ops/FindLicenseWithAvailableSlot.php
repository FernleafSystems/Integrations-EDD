<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Ops;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;
use FernleafSystems\Integrations\Edd\Utilities\Licenses\LicensesIterator;

class FindLicenseWithAvailableSlot {

	use EddCustomerConsumer;
	use EddDownloadConsumer;

	public function find() :?\EDD_SL_License {
		$possible = [];

		$licIT = new LicensesIterator();
		if ( !empty( $this->getEddCustomer() ) ) {
			$licIT->filterByCustomer( $this->getEddCustomer()->id );
		}

		foreach ( $licIT as $lic ) {
			if ( !$lic->is_expired() &&
				 $lic->status !== 'disabled' &&
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