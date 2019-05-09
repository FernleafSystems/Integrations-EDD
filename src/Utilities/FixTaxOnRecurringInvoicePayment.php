<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

/**
 * Class FixTaxOnRecurringInvoice
 * @package FernleafSystems\Integrations\Edd\Utilities
 */
class FixTaxOnRecurringInvoicePayment {

	/**
	 * @param \EDD_Payment $oPayment
	 */
	public function fix( $oPayment ) {
		if ( $oPayment->tax == 0 && $oPayment->status == 'edd_subscription'
			 && count( $oPayment->cart_details ) == 1 ) { // It's an EDD Renewal (not first charge)

			$oSub = new \EDD_Subscription( $oPayment->get_meta( 'subscription_id' ) );
			$nOriginalPaymentTaxRate = ( new \EDD_Payment( $oSub->get_original_payment_id() ) )->tax_rate;

			$aCartItem = array_pop( $oPayment->cart_details );

			if ( $nOriginalPaymentTaxRate > 0 && $oPayment->tax == 0 ) {

				$nNewItemPrice = $aCartItem[ 'item_price' ]/( 1 + $nOriginalPaymentTaxRate );
				$nNewTax = $aCartItem[ 'item_price' ] - $nNewItemPrice;
				$nPriceId = isset( $aCartItem[ 'item_number' ][ 'options' ][ 'price_id' ] ) ? $aCartItem[ 'item_number' ][ 'options' ][ 'price_id' ] : false;

				$oPayment->remove_download( $aCartItem[ 'id' ] );
				$oPayment->add_download(
					$aCartItem[ 'id' ],
					[
						'item_price' => $nNewItemPrice,
						'price_id'   => $nPriceId,
						'tax'        => $nNewTax,
					]
				);

				$oPayment->tax = $nOriginalPaymentTaxRate;
				$oPayment->save();
			}
		}
	}
}