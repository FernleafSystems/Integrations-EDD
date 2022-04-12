<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

use FernleafSystems\Integrations\Edd\Entities\CartItemVo;

class GetCartItemsFrom {

	/**
	 * @param int $paymentId
	 * @return CartItemVo[]
	 */
	public function paymentId( $paymentId ) {
		return $this->convertToCartItemVo( $paymentId );
	}

	/**
	 * @param string $gatewayTxnId
	 * @return CartItemVo[]
	 */
	public function transactionId( $gatewayTxnId ) :array {
		$items = [];

		$pID = edd_get_purchase_id_by_transaction_id( $gatewayTxnId );
		if ( empty( $pID ) ) {
			var_dump($gatewayTxnId);
			$sub = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $gatewayTxnId );
			if ( !empty( $sub ) ) {
				$item = $this->subscription( $sub );
				if ( !empty( $item ) ) {
					$items[] = $item;
				}
			}
		}
		else { // must be the first purchase of a subscription.
			$items = $this->paymentId( $pID );
		}
		return $items;
	}

	/**
	 * @param \EDD_Subscription $sub
	 * @return CartItemVo|null
	 */
	public function subscription( $sub ) {
		$item = null;

		$items = $this->convertToCartItemVo( $sub->get_original_payment_id(), $sub->product_id );
		if ( !empty( $items ) ) {
			$item = array_pop( $items )->setParentSubscriptionId( $sub->id );
		}
		return $item;
	}

	/**
	 * @param int      $paymentID
	 * @param int|null $nProductId - filter cart items for a given product ID
	 * @return CartItemVo[]
	 */
	protected function convertToCartItemVo( $paymentID, $nProductId = null ) :array {
		$items = [];

		foreach ( edd_get_payment( $paymentID )->cart_details as $item ) {
			if ( empty( $nProductId ) || $item[ 'id' ] == $nProductId ) {
				$vo = ( new CartItemVo() )->applyFromArray( $item );
				$vo->parent_payment_id = $paymentID;
				$items[] = $vo;
			}
		}
		return $items;
	}
}