<?php

namespace FernleafSystems\WordPress\Integrations\Edd\Utilities\Entities;

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
	public function getItemPrice() {
		return $this->getParam( 'item_price' );
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
	public function getDiscount() {
		return $this->getParam( 'discount' );
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
}