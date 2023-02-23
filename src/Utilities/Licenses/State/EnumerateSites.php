<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

/**
 * @property string[] $sites
 * @property string[] $sites_expired
 */
class EnumerateSites extends BaseState {

	protected function run() {

		$assignedSites = [];
		$assignedSitesExpired = [];
		foreach ( $this->getLicenseIterator() as $lic ) {
			if ( !empty( $lic ) ) {
				if ( $lic->is_expired() == 'expired' ) {
					$assignedSitesExpired = array_merge( $assignedSitesExpired, $lic->sites );
				}
				elseif ( in_array( $lic->status, [ 'active', 'inactive' ] ) ) {
					$assignedSites = array_merge( $assignedSites, $lic->sites );
				}
			}
		}

		$this->sites = $assignedSites;
		$this->sites_expired = $assignedSitesExpired;
	}
}