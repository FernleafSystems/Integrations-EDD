<?php

namespace FernleafSystems\WordPress\Integrations\Edd\Utilities;

use FernleafSystems\WordPress\Integrations\Edd\Utilities\Entities\CartItemVo;

/**
 * Class GetCartItemsFromTransactionId
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities
 */
class GetCartItemsFromTransactionId {

	/**
	 * You receive an array of Cart Items, but actually it should only be 1 item if all you have
	 * are subscriptions.
	 * @param string $sTransactionId
	 * @return CartItemVo[]
	 * @throws \Exception
	 */
	public function retrieve( $sTransactionId ) {

		$aCartItems = array();

		$nPaymentId = edd_get_purchase_id_by_transaction_id( $sTransactionId );
		if ( !empty( $nPaymentId ) ) { // must be the first purchase of a subscription.
			$aCartItems = ( new \EDD_Payment( $nPaymentId ) )->cart_details;
		}
		else { // extract the particular cart item subscription
			$oSub = ( new GetSubscriptionsFromTransactionId() )->retrieve( $sTransactionId );
			if ( is_null( $oSub ) ) {
				throw new \Exception( sprintf( 'Could not find either Payment or Subscription with Txn ID "%s"', $sTransactionId ) );
			}

			$oPayment = new \EDD_Payment( $oSub->get_original_payment_id() );
			foreach ( $oPayment->cart_details as $aCartItem ) {
				if ( $aCartItem[ 'id' ] == $oSub->product_id ) {
					$aCartItems[] = $aCartItem;
				}
			}
		}

		return array_map(
			function ( $aItem ) {
				return ( new CartItemVo() )->applyFromArray( $aItem );
			},
			$aCartItems
		);
	}
}