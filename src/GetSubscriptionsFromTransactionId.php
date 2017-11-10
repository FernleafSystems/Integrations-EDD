<?php

namespace FernleafSystems\WordPress\Integrations\Edd\Utilities;

/**
 * Class GetSubscriptionsFromTransactionId
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities
 */
class GetSubscriptionsFromTransactionId {

	/**
	 * @param string $sTransactionId
	 * @return \EDD_Subscription
	 */
	public function retrieve( $sTransactionId ) {
		$aSubs = ( new \EDD_Subscriptions_DB() )
			->get_subscriptions(
				array(
					'transaction_id' => $sTransactionId
				)
			);
		return ( count( $aSubs ) == 1 ) ? $aSubs[ 0 ] : null;
	}
}