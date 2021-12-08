<?php

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddDownloadConsumer {

	/**
	 * @var \EDD_Download
	 */
	private $oEddDownload;

	/**
	 * @return \EDD_Download
	 */
	public function getEddDownload() {
		return $this->oEddDownload;
	}

	/**
	 * @param \EDD_Download $oEddDownload
	 * @return $this
	 */
	public function setEddDownload( $oEddDownload ) {
		$this->oEddDownload = $oEddDownload;
		return $this;
	}
}