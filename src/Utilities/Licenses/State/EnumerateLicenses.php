<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

/**
 * @property \EDD_SL_License[] $licenses
 */
class EnumerateLicenses extends BaseState {

	protected function run() {
		$licenses = [];
		foreach ( $this->getLicenseIterator() as $lic ) {
			$licenses[] = $lic;
		}
		$this->licenses = $licenses;
	}
}