<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

class Find extends Retrieve {

	public function withActivationSlot() :?\EDD_SL_License {
		$theLicense = null;
		foreach ( $this->retrieve() as $lic ) {
			if ( !$lic->is_expired() ) {
				if ( $lic->activation_limit <= 0 || $lic->activation_limit > $lic->activation_count ) {
					$theLicense = $lic;
					break;
				}
			}
		}
		return $theLicense;
	}

	public function withActiveSite( string $url, bool $includeExpired = false ) :?\EDD_SL_License {
		$theLicense = null;

		$licensesBySite = $this->retrieve( [ 'site_name' => $url ] );
		if ( !empty( $licensesBySite ) ) {
			foreach ( $licensesBySite as $lic ) {
				if ( ( $includeExpired || !$lic->is_expired() ) && in_array( $url, $lic->sites ) ) {
					$theLicense = $lic;
					break;
				}
			}
		}

		return $theLicense;
	}
}