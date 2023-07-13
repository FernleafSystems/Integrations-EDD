<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Common\Constants;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\ContactVO;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Invoices;
use FernleafSystems\Integrations\Edd\Consumers\EddPaymentConsumer;
use FernleafSystems\Integrations\Edd\Entities\CartItemVo;
use FernleafSystems\Integrations\Edd\Utilities;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;
use FernleafSystems\Integrations\Freeagent\Reconciliation\Invoices\CreateFromCharge;

/**
 * Implements the FernleafSystems\Integrations\Freeagent\Reconciliation\Bridge\BridgeInterface
 */
trait CommonEddBridge {

	use ConnectionConsumer;
	use EddPaymentConsumer;
	use FreeagentConfigVoConsumer;

	/**
	 * @var \EDD_Payment[]
	 */
	private array $paymentsFromCharge = [];

	public function __construct() {
		EDD_Recurring(); // initializes anything that's required
	}

	/**
	 * @throws \Exception
	 */
	public function createFreeagentContact( ChargeVO $charge, bool $updateOnly = false ) :?ContactVO {
		return ( new EddCustomerToFreeagentContact() )
			->setConnection( $this->getConnection() )
			->setEddPayment( $this->getEddPaymentFromCharge( $charge ) )
			->create();
	}

	/**
	 * @throws \Exception
	 */
	public function getFreeagentContactId( ChargeVO $charge ) :?int {
		return $this->getFreeagentContactIdFromEddPayment( $this->getEddPaymentFromCharge( $charge ) );
	}

	/**
	 * @throws \Exception
	 */
	public function getFreeagentInvoiceId( ChargeVO $charge ) :?int {
		$ids = $this->getFreeagentInvoiceIdsFromEddPayment(
			$this->getEddPaymentFromCharge( $charge )
		);
		return $ids[ $charge->id ] ?? null;
	}

	/**
	 * @throws \Exception
	 */
	public function createFreeagentInvoiceFromChargeId( string $gatewayTxnID ) :?Invoices\InvoiceVO {
		$cartItem = $this->getCartItemDetailsFromGatewayTxn( $gatewayTxnID );
		return empty( $cartItem ) ? null : $this->createFreeagentInvoiceFromEddPaymentCartItem( $cartItem );
	}

	/**
	 * First attempts to locate a previously created invoice for this Payment.
	 * @throws \Exception
	 */
	public function createFreeagentInvoiceFromEddPaymentCartItem( CartItemVo $cartItem ) :?Invoices\InvoiceVO {
		$inv = null;

		$charge = $this->buildChargeFromTransaction(
			( new Utilities\GetTransactionIdFromCartItem() )->retrieve( $cartItem )
		);

		$invoiceID = $charge->local_payment_id > 0 ? $this->getFreeagentInvoiceId( $charge ) : null;

		if ( !empty( $invoiceID ) ) {
			$inv = ( new Invoices\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $invoiceID )
				->retrieve();
		}
		if ( empty( $invoiceID ) || empty( $inv ) ) {
			$inv = ( new CreateFromCharge() )
				->setBridge( $this )
				->setConnection( $this->getConnection() )
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->setChargeVO( $charge )
				->create();
		}

		if ( !empty( $inv ) && $inv->isStatusDraft() ) {
			sleep( 5 );
			( new Invoices\MarkAs() )
				->setConnection( $this->getConnection() )
				->setEntityId( $inv->getId() )
				->sent();
			$inv = ( new Invoices\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $inv->getId() )
				->retrieve();
		}

		return $inv;
	}

	/**
	 * First attempts to locate a previously created invoice for this Payment.
	 * @return Invoices\InvoiceVO[]
	 * @throws \Exception
	 */
	public function createFreeagentInvoicesFromEddPayment( \EDD_Payment $payment ) :array {
		return \array_filter( \array_map(
			fn( $txnID ) => $this->createFreeagentInvoiceFromChargeId( $txnID ),
			( new Utilities\GetTransactionIdsFromPayment() )->retrieve( $payment )
		) );
	}

	protected function getCartItemName( CartItemVo $item ) :string {
		if ( !empty( $item->item_number[ 'options' ][ 'price_id' ] ) ) {
			$name = edd_get_price_option_name( $item->id, $item->item_number[ 'options' ][ 'price_id' ] );
		}
		return empty( $name ) ? $item->name : $item->name.': '.$name;
	}

	protected function getCartItemDetailsFromGatewayTxn( string $gatewayTxnId ) :?CartItemVo {
		$items = ( new Utilities\GetCartItemsFrom() )->transactionId( $gatewayTxnId );
		if ( \count( $items ) === 0 ) { // TODO - if we offer non-subscription items!
			error_log( sprintf( 'Found ZERO cart items for a Stripe Txn "%s"', $gatewayTxnId ) );
		}
		elseif ( \count( $items ) > 1 ) { // TODO - if we offer non-subscription items!
			error_log( sprintf( 'Found more than 1 cart item for a Stripe Txn "%s"', $gatewayTxnId ) );
		}
		return \array_pop( $items );
	}

	protected function getEddCustomerFromEddPayment( \EDD_Payment $payment ) :\EDD_Customer {
		return new \EDD_Customer( $payment->customer_id );
	}

	/**
	 * @throws \Exception
	 */
	protected function getEddPaymentFromCharge( ChargeVO $charge ) :\EDD_Payment {
		if ( !isset( $this->paymentsFromCharge[ $charge->id ] ) ) {
			$p = ( new Utilities\GetEddPaymentFromGatewayTxnId() )->retrieve( $charge->id );
			if ( !$p instanceof \EDD_Payment ) {
				throw new \Exception( sprintf( 'Could not find \EDD_Payment from charge: %s', var_export( $charge->getRawData(), true ) ) );
			}
			$this->paymentsFromCharge[ $charge->id ] = $p;
		}
		return $this->paymentsFromCharge[ $charge->id ];
	}

	public function getFreeagentContactIdFromEddPayment( \EDD_Payment $payment ) :int {
		return $this->getFreeagentContactIdFromCustomer( edd_get_customer( $payment->customer_id ) );
	}

	public function getFreeagentContactIdFromCustomer( \EDD_Customer $customer ) :int {
		return (int)$customer->get_meta( 'freeagent_contact_id' );
	}

	public function getFreeagentInvoiceIdsFromEddPayment( ?\EDD_Payment $payment ) :array {
		$IDs = [];
		if ( !empty( $payment ) ) {
			$IDs = $payment->get_meta( self::KEY_FREEAGENT_INVOICE_IDS );
		}
		return \is_array( $IDs ) ? $IDs : [];
	}

	/**
	 * @throws \Exception
	 */
	public function storeFreeagentInvoiceIdForCharge( ChargeVO $charge, Invoices\InvoiceVO $invoice ) :self {
		$this->getEddPaymentFromCharge( $charge )
			 ->update_meta( self::KEY_FREEAGENT_INVOICE_IDS, [ $charge->id => $invoice->getId() ] );
		return $this;
	}

	public function verifyInternalPaymentLink( ChargeVO $charge ) :bool {
		try {
			$valid = $this->getEddPaymentFromCharge( $charge ) instanceof \EDD_Payment;
		}
		catch ( \Exception $e ) {
			$valid = false;
		}
		return $valid;
	}

	/**
	 * @throws \Exception
	 */
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

	/**
	 * @throws \Exception
	 */
	protected function isChargeInEcRegion( ChargeVO $charge ) :bool {
		return \in_array( $this->getChargeCountry( $charge ), Utilities\Countries::EC_COUNTRIES );
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

	/**
	 * @throws \Exception
	 */
	protected function setupChargeEcStatus( ChargeVO $charge ) {

		if ( $charge->item_taxrate == 0 ) {
			$vatNumber = $charge->local_payment_id > 0 ?
				Utilities\VAT::VatNumberFromPayment( $this->getEddPaymentFromCharge( $charge ) ) : '';
			if ( !empty( $vatNumber ) && $this->isChargeInEcRegion( $charge ) ) {
				$charge->ec_status = Constants::VAT_STATUS_REVERSE_CHARGE;
			}
			else {
				$charge->ec_status = Constants::VAT_STATUS_UK_NON_EC;
			}
		}
		elseif ( $this->isChargeInEcRegion( $charge ) ) {
			$charge->ec_status = Constants::VAT_STATUS_EC_MOSS;
		}
		else {
			$charge->ec_status = Constants::VAT_STATUS_UK_NON_EC;
		}
	}
}