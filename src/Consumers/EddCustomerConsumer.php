<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddCustomerConsumer {

	private ?\EDD_Customer $eddCustomer = null;

	public function getEddCustomer() :?\EDD_Customer {
		return $this->eddCustomer;
	}

	public function setEddCustomer( ?\EDD_Customer $customer ) :self {
		$this->eddCustomer = $customer;
		return $this;
	}
}