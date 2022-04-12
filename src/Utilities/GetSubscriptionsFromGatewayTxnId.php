<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class GetSubscriptionsFromGatewayTxnId {

	/**
	 * TODO: This is still problematic if you have multiple subscriptions in a single purchase
	 * @param string $txnId
	 * @return \EDD_Subscription
	 */
	public function retrieve( $txnId ) {
		$subs = ( new \EDD_Subscriptions_DB() )
			->get_subscriptions( [
				'transaction_id' => $txnId
			] );

		/**
		 * For renewals, there is no obvious link between the TxnID and the Sub. So we need to
		 * first grab the payment associated with the Txn, then if it has a parent, get this
		 * and its associated subscription.
		 */
		if ( empty( $subs ) ) {
			$pID = edd_get_purchase_id_by_transaction_id( $txnId );
			if ( !empty( $pID ) ) {
				$p = new \EDD_Payment( $pID );
				if ( !empty( $p->parent_payment ) ) {
					$pID = $p->parent_payment;
				}
				$subs = ( new GetSubscriptionsFromPaymentId() )->retrieve( $pID );
			}
		}
		return ( count( $subs ) > 0 ) ? array_shift( $subs ) : null;
	}
}