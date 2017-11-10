<?php

namespace FernleafSystems\WordPress\Integrations\Edd\Utilities;

use FernleafSystems\WordPress\Integrations\Edd\Utilities\Entities\CartItemVo;

/**
 * Class GetTransactionIdFromCartItem
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities
 */
class GetTransactionIdFromCartItem {

	/**
	 * @param CartItemVo $oCartItem
	 * @return string
	 */
	public function retrieve( $oCartItem ) {
		$sTransactionId = null;

		$oPayment = new \EDD_Payment( $oCartItem->getParentPaymentId() );

		if ( !empty( $oPayment->transaction_id ) ) {
			$sTransactionId = $oPayment->transaction_id;
		}
		else {
			$aSubs = ( new GetSubscriptionsFromPaymentId() )->retrieve( $oPayment->ID );
			foreach ( $aSubs as $oSub ) {
				if ( $oSub->product_id == $oCartItem->getDownloadId() ) {
					$sTransactionId = $oSub->get_transaction_id();
					break;
				}
			}
		}
		return $sTransactionId;
	}
}