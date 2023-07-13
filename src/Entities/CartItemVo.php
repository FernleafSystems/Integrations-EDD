<?php

namespace FernleafSystems\Integrations\Edd\Entities;

use FernleafSystems\Utilities\Data\Adapter\DynPropertiesClass;

/**
 * @property int    $id                   - download ID
 * @property float  $discount
 * @property array  $item_number          - cart item specifics
 * @property float  $item_price
 * @property string $name
 * @property string $name_price_id
 * @property float  $price                - the "price you pay" at checkout
 * @property float  $price_order_currency - price in the order currency
 * @property int    $quantity
 * @property float  $subtotal             - UNUSABLE as it doesn't factor in discounts getPreTaxTotal()
 * @property float  $tax
 * @property int    $parent_payment_id
 * @property int    $parent_subscription_id
 */
class CartItemVo extends DynPropertiesClass {

	public function getPreTaxPerItemSubtotal() :float {
		return \round( $this->getPreTaxSubtotal()/$this->quantity, 2 );
	}

	public function getPreTaxSubtotal() :float {
		return $this->quantity*$this->item_price - $this->discount;
	}

	/**
	 * To get the Percentage out of 100, multiply by 100
	 * @return float a decimal value.
	 */
	public function getTaxRate() {
		$rate = $this->tax > 0 ? $this->tax/$this->getPreTaxSubtotal() : 0;
		return $rate == 0 ? $rate : round( $rate, 3 );
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