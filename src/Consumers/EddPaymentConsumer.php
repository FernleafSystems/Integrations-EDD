<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddPaymentConsumer {

	private ?\EDD_Payment $eddPayment = null;

	public function getEddPayment() :?\EDD_Payment {
		return $this->eddPayment;
	}

	public function setEddPayment( ?\EDD_Payment $payment ) :self {
		$this->eddPayment = $payment;
		return $this;
	}
}