<?php

namespace FernleafSystems\Integrations\Edd\Consumers;

/**
 * Trait EddSlLicenseConsumer
 * @package FernleafSystems\Integrations\Edd\Consumers
 */
trait EddSlLicenseConsumer {

	/**
	 * @var \EDD_SL_License
	 */
	private $oEddSlLicense;

	/**
	 * @return \EDD_SL_License
	 */
	public function getEddSlLicense() {
		return $this->oEddSlLicense;
	}

	/**
	 * @param \EDD_SL_License $oEddSlLicense
	 * @return $this
	 */
	public function setEddSlLicense( $oEddSlLicense ) {
		$this->oEddSlLicense = $oEddSlLicense;
		return $this;
	}
}