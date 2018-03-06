<?php

namespace FernleafSystems\Integrations\Edd\Consumers;

/**
 * Trait EddCustomerConsumer
 * @package FernleafSystems\Integrations\Edd\Consumers
 */
trait EddCustomerConsumer {

	/**
	 * @var \EDD_Customer
	 */
	private $oEddCustomer;

	/**
	 * @return \EDD_Customer
	 */
	public function getEddCustomer() {
		return $this->oEddCustomer;
	}

	/**
	 * @param \EDD_Customer $oEddCustomer
	 * @return $this
	 */
	public function setEddCustomer( $oEddCustomer ) {
		$this->oEddCustomer = $oEddCustomer;
		return $this;
	}
}