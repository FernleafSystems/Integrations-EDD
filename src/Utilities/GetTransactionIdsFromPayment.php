<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

/**
 * Class GetTransactionIdsFromPayment
 * @package FernleafSystems\WordPress\Integrations\Edd\Utilities
 */
class GetTransactionIdsFromPayment {

	/**
	 * @param \EDD_Payment $oPayment
	 * @return string[]
	 */
	public function retrieve( $oPayment ) {

		$sTxnId = $oPayment->transaction_id;
		if ( empty( $sTxnId ) || ( $sTxnId == $oPayment->ID ) ) {
			$aIds = array_map(
				function ( $oSub ) {
					/** @var \EDD_Subscription $oSub */
					return $oSub->get_transaction_id();
				},
				( new GetSubscriptionsFromPaymentId() )->retrieve( $oPayment->ID )
			);
		}
		else {
			$aIds[] = $sTxnId;
		}

		return $aIds;
	}
}