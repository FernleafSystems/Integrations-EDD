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

		if ( empty( $aSubs ) ) {
			$aSubs = ( new GetSubscriptionsFromPaymentId() )
				->retrieve( edd_get_purchase_id_by_transaction_id( $sTxnId ) );
		}
		return ( count( $aSubs ) == 1 ) ? array_shift( $aSubs ) : null;
	}
}