<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

/**
 * Class GetSubscriptionsFromGatewayTxnId
 * @package FernleafSystems\Integrations\Edd\Utilities
 */
class GetSubscriptionsFromGatewayTxnId {

	/**
	 * TODO: This is still problematic if you have multiple subscriptions in a single purchase
	 * @param string $sTxnId
	 * @return \EDD_Subscription
	 */
	public function retrieve( $sTxnId ) {
		$aSubs = ( new \EDD_Subscriptions_DB() )
			->get_subscriptions(
				array(
					'transaction_id' => $sTxnId
				)
			);

		/**
		 * For renewals, there is no obvious link between the TxnID and the Sub. So we need to
		 * first grab the payment associated with the Txn, then if it has a parent, get this
		 * and its associated subscription.
		 */
		if ( empty( $aSubs ) ) {
			$nPaymentId = edd_get_purchase_id_by_transaction_id( $sTxnId );
			if ( !empty( $nPaymentId ) ) {
				$oP = new \EDD_Payment( $nPaymentId );
				if ( !empty( $oP->parent_payment ) ) {
					$nPaymentId = $oP->parent_payment;
				}
				$aSubs = ( new GetSubscriptionsFromPaymentId() )->retrieve( $nPaymentId );
			}
		}
		return ( count( $aSubs ) > 0 ) ? array_shift( $aSubs ) : null;
	}
}