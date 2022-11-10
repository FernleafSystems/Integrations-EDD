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
		foreach ( $this->getLicIterator() as $licesnse ) {
			if ( !empty( $licesnse ) ) {
				if ( $licesnse->is_expired() == 'expired' ) {
					$assignedSitesExpired = array_merge( $assignedSitesExpired, $licesnse->sites );
				}
				elseif ( in_array( $licesnse->status, [ 'active', 'inactive' ] ) ) {
					$assignedSites = array_merge( $assignedSites, $licesnse->sites );
				}
			}
		}

		$this->sites = $assignedSites;
		$this->sites_expired = $assignedSitesExpired;
	}
}