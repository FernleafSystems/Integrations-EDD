<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class GetTransactionIdsFromPayment {

	/**
	 * @return string[]
	 */
	public function retrieve( \EDD_Payment $payment ) :array {

		$txnId = $payment->transaction_id;
		if ( empty( $txnId ) || ( $txnId == $payment->ID ) ) {
			$IDs = array_map(
				fn( $sub ) => $sub->get_transaction_id(),
				( new GetSubscriptionsFromPaymentId() )->retrieve( $payment->ID )
			);
		}
		else {
			$IDs = [ $txnId ];
		}

		return $IDs;
	}
}