<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

/**
 * @property \EDD_SL_License[] $licenses
 */
class EnumerateLicenses extends BaseState {

	protected function run() {
		$aLicenses = [];
		foreach ( $this->getLicIterator() as $oLicense ) {
			$aLicenses[] = $oLicense;
		}
		$this->licenses = $aLicenses;
	}
}