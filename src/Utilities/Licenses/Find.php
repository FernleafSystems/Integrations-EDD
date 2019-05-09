<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

/**
 * Class Find
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Find extends Retrieve {

	/**
	 * @return \EDD_SL_License|null
	 */
	public function withActivationSlot() {
		$oTheLicense = null;
		foreach ( $this->retrieve() as $oLicense ) {
			if ( !$oLicense->is_expired() ) {
				$nLimit = $oLicense->activation_limit; // 0 == unlimited
				if ( $nLimit <= 0 || $nLimit > $oLicense->activation_count ) {
					$oTheLicense = $oLicense;
					break;
				}
			}
		}
		return $oTheLicense;
	}

	/**
	 * @param string $sUrl
	 * @param bool   $bIncludeExpired
	 * @return \EDD_SL_License|null
	 */
	public function withActiveSite( $sUrl, $bIncludeExpired = false ) {
		$oTheLicense = null;

		$aLicensesBySite = $this->retrieve( [ 'site_name' => $sUrl ] );
		if ( !empty( $aLicensesBySite ) ) {
			foreach ( $aLicensesBySite as $oLic ) {
				if ( ( $bIncludeExpired || !$oLic->is_expired() ) && in_array( $sUrl, $oLic->sites ) ) {
					$oTheLicense = $oLic;
					break;
				}
			}
		}

//		// Do we need this at all? Also doesn't page results
//		if ( empty( $oTheLicense ) ) {
//			foreach ( $this->retrieve() as $oLic ) {
//				if ( ( $bIncludeExpired || !$oLic->is_expired() ) && in_array( $sUrl, $oLic->sites ) ) {
//					$oTheLicense = $oLic;
//					break;
//				}
//			}
//		}

		return $oTheLicense;
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