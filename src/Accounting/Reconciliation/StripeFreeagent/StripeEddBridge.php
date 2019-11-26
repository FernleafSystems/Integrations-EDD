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
	 * @param string $sTxnID a stripe txn ID
	 * @return DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $sTxnID ) {
		$oCharge = parent::buildChargeFromTransaction( $sTxnID );

		$oItem = $this->getCartItemDetailsFromGatewayTxn( $sTxnID );

		$sPeriod = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $sTxnID )->period;
		if ( empty( $sPeriod ) ) {
//			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
			error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $sTxnID ) );
			$sPeriod = 'year';
		}
		$sPeriod = ucfirst( strtolower( $sPeriod.'s' ) ); // e.g. year -> Years

		// Sanity
		if ( $oItem->price != $oCharge->getAmount_Gross() ) {
			throw new \Exception( 'Item cart total does not equal Stripe charge total' );
		}

		return $oCharge->setItemName( $oItem->name )
					   ->setItemPeriodType( $sPeriod )
					   ->setItemQuantity( $oItem->quantity )
					   ->setItemSubtotal( $oItem->subtotal )
					   ->setItemTaxRate( $oItem->getTaxRate() )
					   ->setIsEuVatMoss( $this->isPaymentEuVatMossRegion( $oCharge ) )
					   ->setLocalPaymentId( $this->getEddPaymentFromCharge( $oCharge )->ID );
	}

	/**
	 * @param string $sPayoutId
	 * @return DataWrapper\PayoutVO
	 */
	public function buildPayoutFromId( $sPayoutId ) {
		return parent::buildPayoutFromId( $sPayoutId );
	}
}