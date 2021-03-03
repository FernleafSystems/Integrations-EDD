<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\StripeFreeagent;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Edd\Utilities\GetSubscriptionsFromGatewayTxnId;
use FernleafSystems\Integrations\Freeagent\DataWrapper;
use FernleafSystems\Integrations\Freeagent\Service\Stripe\StripeBridge;

class StripeEddBridge extends StripeBridge {

	use CommonEddBridge;

	/**
	 * @param string $txnID a stripe txn ID
	 * @return DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $txnID ) {
		$charge = parent::buildChargeFromTransaction( $txnID );

		$item = $this->getCartItemDetailsFromGatewayTxn( $txnID );

		$period = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $txnID )->period;
		if ( empty( $period ) ) {
//			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
			error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $txnID ) );
			$period = 'year';
		}
		$period = ucfirst( strtolower( $period.'s' ) ); // e.g. year -> Years

		// Sanity
		if ( $item->price != $charge->getAmount_Gross() ) {
			throw new \Exception( 'Item cart total does not equal Stripe charge total' );
		}

		return $charge->setItemName( $this->getCartItemName( $item ) )
					  ->setItemPeriodType( $period )
					  ->setItemQuantity( $item->quantity )
					  ->setItemSubtotal( $item->getPreTaxPerItemSubtotal() )
					  ->setItemTaxRate( $item->getTaxRate() )
					  ->setIsEuVatMoss( $this->isPaymentEuVatMossRegion( $charge ) )
					  ->setLocalPaymentId( $this->getEddPaymentFromCharge( $charge )->ID );
	}

	/**
	 * @param string $sPayoutId
	 * @return DataWrapper\PayoutVO
	 */
	public function buildPayoutFromId( $sPayoutId ) {
		return parent::buildPayoutFromId( $sPayoutId );
	}
}