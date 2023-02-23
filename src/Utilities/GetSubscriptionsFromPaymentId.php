<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class GetSubscriptionsFromPaymentId {

	/**
	 * @return \EDD_Subscription[]
	 */
	public function retrieve( int $paymentID ) :array {
		return ( new \EDD_Subscriptions_DB() )->get_subscriptions(
			[
				'parent_payment_id' => $paymentID
			]
		);
	}
}