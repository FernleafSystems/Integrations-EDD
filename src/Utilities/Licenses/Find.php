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
	 * @return \EDD_SL_License
	 */
	public function withActiveSite( $sUrl, $bIncludeExpired = false ) {
		$oTheLicense = null;

		$aLicensesByMeta = $this->withActiveSiteUsingMetaQuery( $sUrl );
		if ( !empty( $aLicensesByMeta ) ) {
			foreach ( $aLicensesByMeta as $oLic ) {
				if ( ( $bIncludeExpired || !$oLic->is_expired() ) && in_array( $sUrl, $oLic->sites ) ) {
					$oTheLicense = $oLic;
					break;
				}
			}
		}

		if ( empty( $oTheLicense ) ) {
			foreach ( $this->retrieve() as $oLic ) {
				if ( ( $bIncludeExpired || !$oLic->is_expired() ) && in_array( $sUrl, $oLic->sites ) ) {
					$oTheLicense = $oLic;
					break;
				}
			}
		}

		return $oTheLicense;
	}

	/**
	 * Assumes the site url passed here is clean and ready to go
	 * @param string $sUrl
	 * @return \EDD_SL_License[]
	 */
	public function withActiveSiteUsingMetaQuery( $sUrl ) {
		$aMeta = array(
			'key'     => '_edd_sl_sites',
			'value'   => '%:"'.$sUrl.'";%',
			'compare' => 'LIKE'
		);
		return $this->retrieve( array(), $aMeta );
	}
}