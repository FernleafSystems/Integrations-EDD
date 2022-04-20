<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class FixTaxOnRecurringInvoicePayment {

	/**
	 * @param \EDD_Payment $pym
	 */
	public function fix( $pym ) {
		if ( $pym->tax == 0 && $pym->status == 'edd_subscription'
			 && count( $pym->cart_details ) == 1 ) { // It's an EDD Renewal (not first charge)

			$sub = new \EDD_Subscription( $pym->get_meta( 'subscription_id' ) );
			$originalTaxRate = ( new \EDD_Payment( $sub->get_original_payment_id() ) )->tax_rate;

			$cartItem = array_pop( $pym->cart_details );

			if ( $originalTaxRate > 0 && $pym->tax == 0 ) {

				$newItemPrice = $cartItem[ 'item_price' ]/( 1 + $originalTaxRate );

				$pym->remove_download( $cartItem[ 'id' ] );
				$pym->add_download(
					$cartItem[ 'id' ],
					[
						'item_price' => $newItemPrice,
						'price_id'   => $cartItem[ 'item_number' ][ 'options' ][ 'price_id' ] ?? false,
						'tax'        => $cartItem[ 'item_price' ] - $newItemPrice,
					]
				);

				$pym->tax = $originalTaxRate;
				$pym->save();
			}
		}
	}
}