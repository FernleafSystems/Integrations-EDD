<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\StripeFreeagent;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Edd\Utilities\GetSubscriptionsFromGatewayTxnId;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;
use FernleafSystems\Integrations\Freeagent\Service\Stripe\StripeBridge;

class StripeEddBridge extends StripeBridge {

	use CommonEddBridge;

	public function buildChargeFromTransaction( string $gatewayChargeID ) :ChargeVO {
		$charge = parent::buildChargeFromTransaction( $gatewayChargeID );
		$item = $this->getCartItemDetailsFromGatewayTxn( $gatewayChargeID );
		$charge->amount_discount = $item->discount ?? 0;

		$period = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $gatewayChargeID )->period;
		if ( empty( $period ) ) {
//			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
			error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $gatewayChargeID ) );
			$period = 'year';
		}
		$period = ucfirst( strtolower( $period.'s' ) ); // e.g. year -> Years

		// Sanity
		$sane = false;
		foreach ( [ $item->getPreTaxSubtotal(), $item->price ] as $price ) {
			if ( bccomp( (string)$price, (string)$charge->amount_gross )
				 || (float)$price === (float)$charge->amount_gross ) {
				$sane = true;
				break;
			}
		}
		if ( !$sane ) {
			throw new \Exception( sprintf( 'Item cart total does not equal Stripe charge total for txn %s', $gatewayChargeID ) );
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
}