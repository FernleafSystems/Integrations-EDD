<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Consumers\EddPaymentConsumer;
use FernleafSystems\Integrations\Edd\Entities\CartItemVo;
use FernleafSystems\Integrations\Edd\Utilities;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices\CreateFromCharge;

/**
 * Implements the FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge\BridgeInterface
 * Trait CommonEddBridge
 * @package FernleafSystems\Integrations\Edd\Accounting\Reconciliation
 */
trait CommonEddBridge {

	use ConnectionConsumer,
		EddPaymentConsumer,
		FreeagentConfigVoConsumer;

	public function __construct() {
		EDD_Recurring(); // initializes anything that's required
	}

	/**
	 * @param ChargeVO $oCharge
	 * @param bool     $bUpdateOnly
	 * @return Entities\Contacts\ContactVO
	 */
	public function createFreeagentContact( $oCharge, $bUpdateOnly = false ) {
		$payment = $this->getEddPaymentFromCharge( $oCharge );
		return $this->createFreeagentContactFromPayment( $payment, $bUpdateOnly );
	}

	/**
	 * @param ChargeVO $oCharge
	 * @return int
	 */
	public function getFreeagentContactId( $oCharge ) {
		return $this->getFreeagentContactIdFromEddPayment( $this->getEddPaymentFromCharge( $oCharge ) );
	}

	/**
	 * @param ChargeVO $oCharge
	 * @return int
	 */
	public function getFreeagentInvoiceId( $oCharge ) {
		$ids = $this->getFreeagentInvoiceIdsFromEddPayment( $this->getEddPaymentFromCharge( $oCharge ) );
		return $ids[ $oCharge->id ] ?? null;
	}

	/**
	 * @param \EDD_Payment $oPayment
	 * @param bool         $bUpdateOnly
	 * @return Entities\Contacts\ContactVO
	 */
	protected function createFreeagentContactFromPayment( $oPayment, $bUpdateOnly = false ) {
		$oContactCreator = ( new EddCustomerToFreeagentContact() )
			->setConnection( $this->getConnection() )
			->setCustomer( $this->getEddCustomerFromEddPayment( $oPayment ) )
			->setPayment( $oPayment );
		return $bUpdateOnly ? $oContactCreator->update() : $oContactCreator->create();
	}

	/**
	 * @param string $chargeTxnID
	 * @return Entities\Invoices\InvoiceVO
	 * @throws \Exception
	 */
	public function createFreeagentInvoiceFromChargeId( $chargeTxnID ) {
		return $this->createFreeagentInvoiceFromEddPaymentCartItem(
			$this->getCartItemDetailsFromGatewayTxn( $chargeTxnID )
		);
	}

	/**
	 * First attempts to locate a previously created invoice for this Payment.
	 * @param CartItemVo $cartItem
	 * @return Entities\Invoices\InvoiceVO|null
	 * @throws \Exception
	 */
	public function createFreeagentInvoiceFromEddPaymentCartItem( $cartItem ) {
		$inv = null;

		$txnID = ( new Utilities\GetTransactionIdFromCartItem() )->retrieve( $cartItem );
		$charge = $this->buildChargeFromTransaction( $txnID );

		$nInvoiceId = $this->getFreeagentInvoiceId( $charge );

		if ( !empty( $nInvoiceId ) ) {
			$inv = ( new Entities\Invoices\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $nInvoiceId )
				->retrieve();
		}
		if ( empty( $nInvoiceId ) || empty( $inv ) ) {
			$inv = ( new CreateFromCharge() )
				->setBridge( $this )
				->setConnection( $this->getConnection() )
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->setChargeVO( $charge )
				->create();
		}

		if ( !empty( $inv ) && $inv->isStatusDraft() ) {
			sleep( 10 );
			( new Entities\Invoices\MarkAs() )
				->setConnection( $this->getConnection() )
				->setEntityId( $inv->getId() )
				->sent();
			$inv = ( new Entities\Invoices\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $inv->getId() )
				->retrieve();
		}

		return $inv;
	}

	/**
	 * First attempts to locate a previously created invoice for this Payment.
	 * @param \EDD_Payment $payment
	 * @return Entities\Invoices\InvoiceVO[]
	 */
	public function createFreeagentInvoicesFromEddPayment( $payment ) {
		return array_filter( array_map(
			function ( $txnID ) {
				return $this->createFreeagentInvoiceFromChargeId( $txnID );
			},
			( new Utilities\GetTransactionIdsFromPayment() )->retrieve( $payment )
		) );
	}

	protected function getCartItemName( CartItemVo $item ) {
		if ( !empty( $item->item_number[ 'options' ][ 'price_id' ] ) ) {
			$name = edd_get_price_option_name( $item->id, $item->item_number[ 'options' ][ 'price_id' ] );
		}
		return empty( $name ) ? $item->name : $item->name.': '.$name;
	}

	/**
	 * @param string $sGatewayTxnId
	 * @return CartItemVo
	 * @throws \Exception
	 */
	protected function getCartItemDetailsFromGatewayTxn( $sGatewayTxnId ) {
		$aCartItems = ( new Utilities\GetCartItemsFrom() )
			->transactionId( $sGatewayTxnId );
		if ( count( $aCartItems ) != 1 ) { // TODO - if we offer non-subscription items!
			error_log( sprintf( 'Found more than 1 cart item for a Stripe Txn "%s"', $sGatewayTxnId ) );
		}
		return array_pop( $aCartItems );
	}

	/**
	 * @param \EDD_Payment $oEddPayment
	 * @return \EDD_Customer
	 */
	protected function getEddCustomerFromEddPayment( $oEddPayment ) {
		return new \EDD_Customer( $oEddPayment->customer_id );
	}

	/**
	 * @param ChargeVO $charge
	 * @return \EDD_Payment|null
	 */
	protected function getEddPaymentFromCharge( $charge ) {
		return ( new Utilities\GetEddPaymentFromGatewayTxnId() )->retrieve( $charge->id );
	}

	/**
	 * @param \EDD_Payment $oEddPayment
	 * @return int
	 */
	public function getFreeagentContactIdFromEddPayment( $oEddPayment ) {
		return $this->getFreeagentContactIdFromCustomer(
			$this->getEddCustomerFromEddPayment( $oEddPayment )
		);
	}

	/**
	 * @param \EDD_Customer $oCustomer
	 * @return int
	 */
	public function getFreeagentContactIdFromCustomer( $oCustomer ) {
		return $oCustomer->get_meta( 'freeagent_contact_id' );
	}

	/**
	 * @param \EDD_Payment $payment
	 * @return array
	 */
	public function getFreeagentInvoiceIdsFromEddPayment( $payment ) {
		$aIds = $payment->get_meta( self::KEY_FREEAGENT_INVOICE_IDS );
		return is_array( $aIds ) ? $aIds : [];
	}

	/**
	 * @param ChargeVO                    $oCharge
	 * @param Entities\Invoices\InvoiceVO $oInvoice
	 * @return $this
	 */
	public function storeFreeagentInvoiceIdForCharge( $oCharge, $oInvoice ) {
		$aInvoiceIds[ $oCharge->id ] = $oInvoice->getId();
		$this->getEddPaymentFromCharge( $oCharge )
			 ->update_meta( self::KEY_FREEAGENT_INVOICE_IDS, $aInvoiceIds );
		return $this;
	}

	/**
	 * @param ChargeVO $oCharge
	 * @return bool
	 */
	public function verifyInternalPaymentLink( $oCharge ) {
		return !is_null( $this->getEddPaymentFromCharge( $oCharge ) );
	}

	protected function getChargeCountry( ChargeVO $charge ) :string {
		$payment = $this->getEddPaymentFromCharge( $charge );
		$country = $payment->address[ 'country' ];
		if ( empty( $country ) ) {
			if ( $payment->parent_payment > 0 ) {
				$country = edd_get_payment( $payment->parent_payment )->address[ 'country' ];
			}
			else {
				$country = 'US';
			}
		}
		return $country;
	}

	protected function getVatNumber( ChargeVO $charge ) :string {
		$userInfo = edd_get_payment_meta_user_info( $this->getEddPaymentFromCharge( $charge )->ID );
		return empty( $userInfo[ 'vat_number' ] ) ? '' : $userInfo[ 'vat_number' ];
	}

	protected function isChargeInEcRegion( ChargeVO $charge ) :bool {
		$country = $this->getChargeCountry( $charge );
		return $country != 'GB' &&
			   array_key_exists( $country, $this->getTaxCountriesRates() );
	}

	protected function getTaxCountriesRates() :array {
		$countriesToRates = [];
		foreach ( edd_get_tax_rates() as $countryRate ) {
			if ( !empty( $countryRate[ 'country' ] ) ) {
				$countriesToRates[ $countryRate[ 'country' ] ] = $countryRate[ 'rate' ];
			}
		}
		return $countriesToRates;
	}

	protected function setupChargeEcStatus( ChargeVO $charge ) {
		if ( $this->isChargeInEcRegion( $charge ) ) {
			$vatNumber = $this->getVatNumber( $charge );
			// TODO: Check country is the same as the VAT country code??
			$charge->ec_status = empty( $vatNumber ) ? 'EC VAT MOSS' : 'EC Services';
		}
		else {
			$charge->ec_status = 'UK/Non-EC';
		}
	}
}