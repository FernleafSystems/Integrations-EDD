<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\PaypalFreeagent;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Edd\Utilities\GetEddPaymentFromGatewayTxnId;
use FernleafSystems\Integrations\Edd\Utilities\GetSubscriptionsFromGatewayTxnId;
use FernleafSystems\Integrations\Freeagent\DataWrapper;
use FernleafSystems\Integrations\Paypal_Freeagent\Reconciliation\Bridge\PaypalBridge;

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

		$oSub = ( new GetSubscriptionsFromGatewayTxnId() )->retrieve( $sTxnID );
		if ( empty( $oSub->period ) ) {
//			var_dump( $sTxnID );
//			var_dump( $oSub );
			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
		}

		$sPeriod = ucfirst( strtolower( $oSub->period.'s' ) ); // e.g. year -> Years

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
	 * @param DataWrapper\PayoutVO $oPayout
	 * @return int|null
	 */
	public function getExternalBankTxnId( $oPayout ) {
		return ( new GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->getId() )
			->get_meta( static::PAYMENTMETA_EXT_BANK_TXN_ID );
	}

	/**
	 * @param DataWrapper\PayoutVO $oPayout
	 * @return int|null
	 */
	public function getExternalBillId( $oPayout ) {
		return ( new GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->getId() )
			->get_meta( static::PAYMENTMETA_EXT_BILL_ID );
	}

	/**
	 * @param DataWrapper\PayoutVO                        $oPayout
	 * @param Entities\BankTransactions\BankTransactionVO $oBankTxn
	 * @return $this
	 */
	public function storeExternalBankTxnId( $oPayout, $oBankTxn ) {
		( new GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->getId() )
			->update_meta( static::PAYMENTMETA_EXT_BANK_TXN_ID, $oBankTxn->getId() );
		return $this;
	}

	/**
	 * @param DataWrapper\PayoutVO  $oPayout
	 * @param Entities\Bills\BillVO $oBill
	 * @return $this
	 */
	public function storeExternalBillId( $oPayout, $oBill ) {
		( new GetEddPaymentFromGatewayTxnId() )
			->retrieve( $oPayout->getId() )
			->update_meta( static::PAYMENTMETA_EXT_BILL_ID, $oBill->getId() );
		return $this;
	}
}