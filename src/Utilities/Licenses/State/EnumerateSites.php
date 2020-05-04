<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

/**
 * Class EnumerateSites
 * @package FernleafSystems\Wordpress\Plugin\EddKeyless\Module\Keyless\Lib\Licenses
 * @property string[] $sites
 * @property string[] $sites_expired
 */
class EnumerateSites extends BaseState {

	protected function run() {

		$aAssignedSites = [];
		$aAssignedSitesExpired = [];
		foreach ( $this->getLicIterator() as $oLicense ) {
			if ( $oLicense->is_expired() == 'expired' ) {
				$aAssignedSitesExpired = array_merge( $aAssignedSitesExpired, $oLicense->sites );
			}
			elseif ( in_array( $oLicense->status, [ 'active', 'inactive' ] ) ) {
				$aAssignedSites = array_merge( $aAssignedSites, $oLicense->sites );
			}
		}

		$this->sites = $aAssignedSites;
		$this->sites_expired = $aAssignedSitesExpired;
	}
}