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
		$charge->amount_discount = $item->discount ?? 0;

		$period = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $txnID )->period;
		if ( empty( $period ) ) {
//			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
			error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $txnID ) );
			$period = 'year';
		}
		$period = ucfirst( strtolower( $period.'s' ) ); // e.g. year -> Years

		// Sanity
		$sane = false;
		foreach ( [ $item->getPreTaxSubtotal(), $item->price ] as $price ) {
			if ( (float)$price === (float)$charge->amount_gross ) {
				$sane = true;
				break;
			}
		}
		if ( !$sane ) {
			throw new \Exception( 'Item cart total does not equal Stripe charge total' );
		}

		$charge->item_name = $this->getCartItemName( $item );
		$charge->item_quantity = $item->quantity;
		$charge->item_subtotal = $item->getPreTaxPerItemSubtotal();
		$charge->item_taxrate = $item->getTaxRate();
		$charge->local_payment_id = $this->getEddPaymentFromCharge( $charge )->ID;
		$charge->setItemPeriodType( $period );
		$this->setupChargeEcStatus( $charge );

		return $charge;
	}

	/**
	 * @param string $sPayoutId
	 * @return DataWrapper\PayoutVO
	 */
	public function buildPayoutFromId( $sPayoutId ) {
		return parent::buildPayoutFromId( $sPayoutId );
	}
}