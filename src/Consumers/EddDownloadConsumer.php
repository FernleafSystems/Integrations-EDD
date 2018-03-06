<?php

namespace FernleafSystems\Integrations\Edd\Consumers;

/**
 * Trait EddDownloadConsumer
 * @package FernleafSystems\Integrations\Edd\Consumers
 */
trait EddDownloadConsumer {

	/**
	 * @var \EDD_Download
	 */
	private $oEddDownload;

	/**
	 * @return \EDD_Download
	 */
	public function getEddCustomer() {
		return $this->oEddDownload;
	}

	/**
	 * @param \EDD_Download $oEddDownload
	 * @return $this
	 */
	public function setEddCustomer( $oEddDownload ) {
		$this->oEddDownload = $oEddDownload;
		return $this;
	}
}