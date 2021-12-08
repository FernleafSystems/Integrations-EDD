<?php

namespace FernleafSystems\Integrations\Edd\Consumers;

trait EddPaymentConsumer {

	/**
	 * @var \EDD_Payment
	 */
	private $oEddPayment;

	/**
	 * @return \EDD_Payment
	 */
	public function getEddPayment() {
		return $this->oEddPayment;
	}

	/**
	 * @param \EDD_Payment $oEddPayment
	 * @return $this
	 */
	public function setEddPayment( $oEddPayment ) {
		$this->oEddPayment = $oEddPayment;
		return $this;
	}
}