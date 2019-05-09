<?php

namespace FernleafSystems\Integrations\Edd\Entities;

use FernleafSystems\Utilities\Data\Adapter\StdClassAdapter;

/**
 * Class CartItemVo
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities\Entities
 * @property int    $id                   - download ID
 * @property float  $discount
 * @property float  $item_price
 * @property string $name
 * @property float  $price
 * @property float  $price_order_currency - price in the order currency
 * @property int    $quantity
 * @property float  $subtotal
 * @property float  $tax
 * @property int    $parent_payment_id
 * @property int    $parent_subscription_id
 */
class CartItemVo {

	use StdClassAdapter;

	/**
	 * To get the Percentage out of 100, multiply by 100
	 * @return float a decimal value.
	 */
	public function getTaxRate() {
		$nTaxRate = ( $this->tax > 0 ) ? ( $this->tax/$this->subtotal ) : 0;
		return ( $nTaxRate == 0 ) ? $nTaxRate : round( $nTaxRate, 3 );
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getDownloadId() {
		return $this->id;
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public function getDiscount() {
		return $this->discount;
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public function getItemPrice() {
		return $this->item_price;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public function getPrice() {
		return $this->price;
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public function getPriceInOrderCurrency() {
		return $this->price_order_currency;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getQuantity() {
		return $this->quantity;
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public function getSubtotal() {
		return $this->subtotal;
	}

	/**
	 * @return float
	 * @deprecated
	 */
	public function getTax() {
		return $this->tax;
	}

	/**
	 * @return string
	 * @deprecated
	 */
	public function getTotal() {
		return $this->price;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getParentPaymentId() {
		return $this->parent_payment_id;
	}

	/**
	 * @return int
	 * @deprecated
	 */
	public function getParentSubscriptionId() {
		return $this->parent_subscription_id;
	}

	/**
	 * @param int $nId
	 * @return $this
	 */
	public function setParentPaymentId( $nId ) {
		$this->parent_payment_id = $nId;
		return $this;
	}

	/**
	 * @param int $nId
	 * @return $this
	 */
	public function setParentSubscriptionId( $nId ) {
		$this->parent_subscription_id = $nId;
		return $this;
	}
}