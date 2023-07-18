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
		if ( empty( $item ) ) {
			$period = 'year';
			$charge->item_name = 'Placeholder item name';
			$charge->item_quantity = 1;
			$charge->item_subtotal = $charge->amount_gross;
			$charge->item_taxrate = 0;
			$charge->local_payment_id = 0;
		}
		else {
			$sub = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $gatewayChargeID );
			if ( empty( $sub->period ) ) {
				error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $gatewayChargeID ) );
				$period = 'year';
			}
			else {
				$period = $sub->period;

				// Price Sanity Check
				$sane = false;
				foreach ( [ $item->getPreTaxSubtotal(), $item->price ] as $price ) {
					if ( \bccomp( (string)$price, (string)$charge->amount_gross )
						 || (float)$price === (float)$charge->amount_gross
					) {
						$sane = true;
						break;
					}
				}
				if ( !$sane ) {
					throw new \Exception( sprintf( 'Item cart total does not equal Stripe charge total for txn %s', $gatewayChargeID ) );
				}

				$charge->amount_discount = $item->discount ?? 0;

				$charge->item_name = $this->getCartItemName( $item );
				$charge->item_quantity = $item->quantity;
				$charge->item_subtotal = $item->getPreTaxPerItemSubtotal();
				$charge->item_taxrate = $item->getTaxRate();
				$charge->local_payment_id = $this->getEddPaymentFromCharge( $charge )->ID;
				$this->setupChargeEcStatus( $charge );
			}
		}

		$charge->item_type = $period;

		return $charge;
	}
}