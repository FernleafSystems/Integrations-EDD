<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddSlLicenseConsumer {

	private ?\EDD_SL_License $eddSlLicense = null;

	public function getEddSlLicense() :?\EDD_SL_License {
		return $this->eddSlLicense;
	}

	public function setEddSlLicense( ?\EDD_SL_License $eddSlLicense ) :self {
		$this->eddSlLicense = $eddSlLicense;
		return $this;
	}
}