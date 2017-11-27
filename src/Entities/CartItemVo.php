<?php

namespace FernleafSystems\Integrations\Edd\Entities;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class CartItemVo
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities\Entities
 */
class CartItemVo {

	use StdClassAdapter;

	/**
	 * @return int
	 */
	public function getDownloadId() {
		return $this->getParam( 'id' );
	}

	/**
	 * @return float
	 */
	public function getDiscount() {
		return $this->getParam( 'discount' );
	}

	/**
	 * @return float
	 */
	public function getItemPrice() {
		return $this->getParam( 'item_price' );
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->getStringParam( 'name' );
	}

	/**
	 * @return float
	 */
	public function getPrice() {
		return $this->getParam( 'price' );
	}

	/**
	 * @return float
	 */
	public function getPriceInOrderCurrency() {
		return $this->getParam( 'price_order_currency' );
	}

	/**
	 * @return int
	 */
	public function getQuantity() {
		return $this->getParam( 'quantity' );
	}

	/**
	 * @return float
	 */
	public function getSubtotal() {
		return $this->getParam( 'subtotal' );
	}

	/**
	 * @return float
	 */
	public function getTax() {
		return $this->getParam( 'tax' );
	}

	/**
	 * To get the Percentage out of 100, multiply by 100
	 * @return float a decimal value.
	 */
	public function getTaxRate() {
		$nTaxRate = ( $this->getTax() > 0 ) ? ( $this->getTax()/$this->getSubtotal() ) : 0;
		return ( $nTaxRate == 0 ) ? $nTaxRate : round( $nTaxRate, 3 );
	}

	/**
	 * @return int
	 */
	public function getParentPaymentId() {
		return $this->getParam( 'parent_payment_id' );
	}

	/**
	 * @return int
	 */
	public function getParentSubscriptionId() {
		return $this->getParam( 'parent_subscription_id' );
	}

	/**
	 * @param int $nId
	 * @return $this
	 */
	public function setParentPaymentId( $nId ) {
		return $this->setParam( 'parent_payment_id', $nId );
	}

	/**
	 * @param int $nId
	 * @return $this
	 */
	public function setParentSubscriptionId( $nId ) {
		return $this->setParam( 'parent_subscription_id', $nId );
	}
}