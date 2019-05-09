<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

use FernleafSystems\Integrations\Edd\Entities\CartItemVo;

/**
 * Class GetCartItemsFromEddSubscription
 * @package FernleafSystems\Integrations\Edd\Utilities
 */
class GetCartItemsFrom {

	/**
	 * @param int $nPaymentId
	 * @return CartItemVo[]
	 */
	public function paymentId( $nPaymentId ) {
		return $this->convertToCartItemVo( $nPaymentId );
	}

	/**
	 * @param string $sGatewayTxnId
	 * @return CartItemVo[]
	 */
	public function transactionId( $sGatewayTxnId ) {
		$aCartItems = [];

		$nPaymentId = edd_get_purchase_id_by_transaction_id( $sGatewayTxnId );
		if ( empty( $nPaymentId ) ) {
			$oSub = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $sGatewayTxnId );
			$oItem = $this->subscription( $oSub );
			if ( !empty( $oItem ) ) {
				$aCartItems[] = $oItem;
			}
		}
		else { // must be the first purchase of a subscription.
			$aCartItems = $this->paymentId( $nPaymentId );
		}
		return $aCartItems;
	}

	/**
	 * @param \EDD_Subscription $oSub
	 * @return CartItemVo|null
	 */
	public function subscription( $oSub ) {
		$oItem = null;

		$aItems = $this->convertToCartItemVo( $oSub->get_original_payment_id(), $oSub->product_id );
		if ( !empty( $aItems ) ) {
			$oItem = array_pop( $aItems )->setParentSubscriptionId( $oSub->id );
		}
		return $oItem;
	}

	/**
	 * @param int      $nPaymentId
	 * @param int|null $nProductId - filter cart items for a given product ID
	 * @return CartItemVo[]
	 */
	protected function convertToCartItemVo( $nPaymentId, $nProductId = null ) {
		$aItems = [];

		foreach ( ( new \EDD_Payment( $nPaymentId ) )->cart_details as $aCartItem ) {
			if ( empty( $nProductId ) || $aCartItem[ 'id' ] == $nProductId ) {
				$aItems[] = ( new CartItemVo() )
					->applyFromArray( $aCartItem )
					->setParentPaymentId( $nPaymentId );
			}
		}
		return $aItems;
	}
}