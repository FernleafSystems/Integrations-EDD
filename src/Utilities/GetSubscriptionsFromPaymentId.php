<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class GetSubscriptionsFromPaymentId {

	/**
	 * @param int $nPaymentId
	 * @return \EDD_Subscription[]
	 */
	public function retrieve( $nPaymentId ) {
		return ( new \EDD_Subscriptions_DB() )
			->get_subscriptions(
				[
					'parent_payment_id' => $nPaymentId
				]
			);
	}
}