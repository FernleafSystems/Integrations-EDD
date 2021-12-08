<?php

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddCustomerConsumer {

	/**
	 * @var \EDD_Customer
	 */
	private $oEddCustomer;

	public function getEddCustomer() :?\EDD_Customer {
		return $this->oEddCustomer;
	}

	public function setEddCustomer( \EDD_Customer $oEddCustomer ) :self {
		$this->oEddCustomer = $oEddCustomer;
		return $this;
	}
}