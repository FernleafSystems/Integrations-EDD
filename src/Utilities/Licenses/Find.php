<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

class Find extends Retrieve {

	/**
	 * @return \EDD_SL_License|null
	 */
	public function withActivationSlot() {
		$oTheLicense = null;
		foreach ( $this->retrieve() as $lic ) {
			if ( !$lic->is_expired() ) {
				$nLimit = $lic->activation_limit; // 0 == unlimited
				if ( $nLimit <= 0 || $nLimit > $lic->activation_count ) {
					$oTheLicense = $lic;
					break;
				}
			}
		}
		return $oTheLicense;
	}

	/**
	 * @param string $url
	 * @param bool   $includeExpired
	 * @return \EDD_SL_License|null
	 */
	public function withActiveSite( $url, $includeExpired = false ) {
		$theLicense = null;

		$aLicensesBySite = $this->retrieve( [ 'site_name' => $url ] );
		if ( !empty( $aLicensesBySite ) ) {
			foreach ( $aLicensesBySite as $lic ) {
				if ( ( $includeExpired || !$lic->is_expired() ) && in_array( $url, $lic->sites ) ) {
					$theLicense = $lic;
					break;
				}
			}
		}

		return $theLicense;
	}

	/**
	 * @param string $sUrl
	 * @return \EDD_SL_License[]
	 * @deprecated unused
	 */
	public function withActiveSiteUsingMetaQuery( $sUrl ) {
		return $this->retrieve( [ 'site_name' => $sUrl ] );
	}
}