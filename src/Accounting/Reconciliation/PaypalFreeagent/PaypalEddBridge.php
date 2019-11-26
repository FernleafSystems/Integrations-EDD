<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\PaypalFreeagent;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Edd\Utilities;
use FernleafSystems\Integrations\Freeagent\DataWrapper;
use FernleafSystems\Integrations\Freeagent\Service\PayPal\PaypalBridge;

class PaypalEddBridge extends PaypalBridge {

	use CommonEddBridge;
	const PAYMENTMETA_EXT_BANK_TXN_ID = 'icwpeddpaypalbridge_ext_bank_tx_id';
	const PAYMENTMETA_EXT_BILL_ID = 'icwpeddpaypalbridge_ext_bill_id';

	/**
	 * @param string $sTxnID a stripe txn ID
	 * @return DataWrapper\ChargeVO
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( $sTxnID ) {
		$oCharge = parent::buildChargeFromTransaction( $sTxnID );

		$oItem = $this->getCartItemDetailsFromGatewayTxn( $sTxnID );

		$oSub = ( new Utilities\GetSubscriptionsFromGatewayTxnId() )->retrieve( $sTxnID );
		if ( empty( $oSub->period ) ) {
//			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
			error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $sTxnID ) );
			$sPeriod = 'year';
		}
		else {
			$sPeriod = $oSub->period;
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
	 * @param DataWrapper\PayoutVO $oPayout
	 * @return int|null
	 */
	public function getExternalBankTxnId( $oPayout ) {
		return ( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->id )
			->get_meta( static::PAYMENTMETA_EXT_BANK_TXN_ID );
	}

	/**
	 * @param DataWrapper\PayoutVO $oPayout
	 * @return int|null
	 */
	public function getExternalBillId( $oPayout ) {
		return ( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->id )
			->get_meta( static::PAYMENTMETA_EXT_BILL_ID );
	}

	/**
	 * @param DataWrapper\PayoutVO                        $oPayout
	 * @param Entities\BankTransactions\BankTransactionVO $oBankTxn
	 * @return $this
	 */
	public function storeExternalBankTxnId( $oPayout, $oBankTxn ) {
		( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->id )
			->update_meta( static::PAYMENTMETA_EXT_BANK_TXN_ID, $oBankTxn->getId() );
		return $this;
	}

	/**
	 * @param DataWrapper\PayoutVO  $oPayout
	 * @param Entities\Bills\BillVO $oBill
	 * @return $this
	 */
	public function storeExternalBillId( $oPayout, $oBill ) {
		( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->id )
			->update_meta( static::PAYMENTMETA_EXT_BILL_ID, $oBill->getId() );
		return $this;
	}
}