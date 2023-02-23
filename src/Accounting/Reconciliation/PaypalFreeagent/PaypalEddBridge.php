<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\PaypalFreeagent;

use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\ApiWrappers\Freeagent\Entities\BankTransactions\BankTransactionVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Bills\BillVO;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\CommonEddBridge;
use FernleafSystems\Integrations\Edd\Utilities;
use FernleafSystems\Integrations\Freeagent\DataWrapper\{
	ChargeVO,
	PayoutVO
};
use FernleafSystems\Integrations\Freeagent\Service\PayPal\{
	PaypalBridge,
	TransactionVO
};

class PaypalEddBridge extends PaypalBridge {

	use CommonEddBridge;

	public const PAYMENTMETA_EXT_BANK_TXN_ID = 'icwpeddpaypalbridge_ext_bank_tx_id';
	public const PAYMENTMETA_EXT_BILL_ID = 'icwpeddpaypalbridge_ext_bill_id';

	/**
	 * @throws \Exception
	 */
	public function buildChargeFromTransaction( string $gatewayChargeID ) :ChargeVO {
		$charge = parent::buildChargeFromTransaction( $gatewayChargeID );

		$item = $this->getCartItemDetailsFromGatewayTxn( $gatewayChargeID );

		$sub = ( new Utilities\GetSubscriptionsFromGatewayTxnId() )->retrieve( $gatewayChargeID );
		if ( empty( $sub->period ) ) {
//			throw new \Exception( sprintf( 'Subscription lookup has an empty "period" for Txn: %s', $sTxnID ) );
			error_log( sprintf( 'Default to "year" as subscription has an empty "period" for Txn: %s', $gatewayChargeID ) );
			$period = 'year';
		}
		else {
			$period = $sub->period;
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
			throw new \Exception( 'Item cart total does not equal PayPal charge total' );
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

	protected function getTxnChargeDetails( string $txnID ) :TransactionVO {
		try {
			$txn = $this->getTxnChargeDetailsPayPalAPI( $txnID );
		}
		catch ( \Exception $e ) {
			error_log( sprintf( '%s::%s: %s', __CLASS__, __METHOD__, $e->getMessage() ) );
			$txn = $this->getTxnChargeDetailsLegacy( $txnID );
		}
		return $txn;
	}

	/**
	 * @throws \Exception
	 */
	protected function getTxnChargeDetailsPayPalAPI( string $txnID ) :TransactionVO {
		return ( new GetPaypalTransactionsFromPayment() )
			->setEddPayment( ( new Utilities\GetEddPaymentFromGatewayTxnId() )->retrieve( $txnID ) )
			->retrieve( $txnID );
	}

	public function getExternalBankTxnId( PayoutVO $payout ) :?string {
		return (string)( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $payout->id )
			->get_meta( static::PAYMENTMETA_EXT_BANK_TXN_ID );
	}

	public function getExternalBillId( PayoutVO $payout ) :?string {
		return (string)( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $payout->id )
			->get_meta( static::PAYMENTMETA_EXT_BILL_ID );
	}

	public function storeExternalBankTxnId( PayoutVO $payout, BankTransactionVO $bankTxn ) :self {
		( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $payout->id )
			->update_meta( static::PAYMENTMETA_EXT_BANK_TXN_ID, $bankTxn->getId() );
		return $this;
	}

	public function storeExternalBillId( PayoutVO $payout, BillVO $bill ) :self {
		( new Utilities\GetEddPaymentFromGatewayTxnId() )
			->retrieve( $payout->id )
			->update_meta( static::PAYMENTMETA_EXT_BILL_ID, $bill->getId() );
		return $this;
	}
}