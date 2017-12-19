<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

/**
 * Class GetEddPaymentFromGatewayTxnId
 * @package FernleafSystems\Integrations\Edd\Utilities
 */
class GetEddPaymentFromGatewayTxnId {

	/**
	 * @param string $sTransactionId
	 * @return \EDD_Payment|null
	 */
	public function retrieve( $sTransactionId ) {
		$oPayment = null;

		$nPaymentId = edd_get_purchase_id_by_transaction_id( $sTransactionId );
		if ( !empty( $nPaymentId ) ) { // must be the first purchase of a subscription.
			$oPayment = new \EDD_Payment( $nPaymentId );
		}
		else {
			$oSub = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $sTransactionId );
			if ( !is_null( $oSub ) ) {
				$oPayment = new \EDD_Payment( $oSub->get_original_payment_id() );
			}
		}
		return $oPayment;
	}
}