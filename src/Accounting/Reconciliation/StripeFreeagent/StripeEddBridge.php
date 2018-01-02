<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\StripeFreeagent;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Edd\Utilities\GetSubscriptionsFromGatewayTxnId;
use FernleafSystems\Integrations\Freeagent\DataWrapper;
use FernleafSystems\Integrations\Stripe_Freeagent\Reconciliation\Bridge\StripeBridge;

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
		$sPeriod = ucfirst( strtolower( $sPeriod.'s' ) ); // e.g. year -> Years

		// Sanity
		if ( $oItem->getTotal() != $oCharge->getAmount_Gross() ) {
			throw new \Exception( 'Item cart total does not equal Stripe charge total' );
		}

		return $oCharge->setItemName( $oItem->getName() )
					   ->setItemPeriodType( $sPeriod )
					   ->setItemQuantity( $oItem->getQuantity() )
					   ->setItemSubtotal( $oItem->getSubtotal() )
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