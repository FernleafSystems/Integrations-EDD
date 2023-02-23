<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddDownloadConsumer {

	private ?\EDD_Download $eddDownload = null;

	public function getEddDownload() :?\EDD_Download {
		return $this->eddDownload;
	}

	public function setEddDownload( ?\EDD_Download $eddDownload ) :self {
		$this->eddDownload = $eddDownload;
		return $this;
	}
}