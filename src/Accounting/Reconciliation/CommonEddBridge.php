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
		$oPayment = $this->getEddPaymentFromCharge( $oCharge );
		return $this->createFreeagentContactFromPayment( $oPayment, $bUpdateOnly );
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
		$aIds = $this->getFreeagentInvoiceIdsFromEddPayment( $this->getEddPaymentFromCharge( $oCharge ) );
		return isset( $aIds[ $oCharge->getId() ] ) ? $aIds[ $oCharge->getId() ] : null;
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
	 * @param string $sChargeTxnId
	 * @return Entities\Invoices\InvoiceVO
	 * @throws \Exception
	 */
	public function createFreeagentInvoiceFromChargeId( $sChargeTxnId ) {
		return $this->createFreeagentInvoiceFromEddPaymentCartItem(
			$this->getCartItemDetailsFromGatewayTxn( $sChargeTxnId )
		);
	}

	/**
	 * First attempts to locate a previously created invoice for this Payment.
	 * @param CartItemVo $oCartItem
	 * @return Entities\Invoices\InvoiceVO|null
	 * @throws \Exception
	 */
	public function createFreeagentInvoiceFromEddPaymentCartItem( $oCartItem ) {

		$sTxnId = ( new Utilities\GetTransactionIdFromCartItem() )->retrieve( $oCartItem );
		$oCharge = $this->buildChargeFromTransaction( $sTxnId );

		$nInvoiceId = $this->getFreeagentInvoiceId( $oCharge );
		if ( empty( $nInvoiceId ) ) {
			$oInvoice = ( new CreateFromCharge() )
				->setBridge( $this )
				->setConnection( $this->getConnection() )
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->setChargeVO( $oCharge )
				->create();
		}
		else {
			$oInvoice = ( new Entities\Invoices\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $nInvoiceId )
				->retrieve();
		}
		return $oInvoice;
	}

	/**
	 * First attempts to locate a previously created invoice for this Payment.
	 * @param \EDD_Payment $oPayment
	 * @return Entities\Invoices\InvoiceVO[]
	 */
	public function createFreeagentInvoicesFromEddPayment( $oPayment ) {
		return array_filter( array_map(
			function ( $sTxnId ) {
				/** @var string $sTxnId */
				return $this->createFreeagentInvoiceFromChargeId( $sTxnId );
			},
			( new Utilities\GetTransactionIdsFromPayment() )->retrieve( $oPayment )
		) );
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
	 * @param ChargeVO $oCharge
	 * @return \EDD_Payment|null
	 */
	protected function getEddPaymentFromCharge( $oCharge ) {
		return ( new Utilities\GetEddPaymentFromGatewayTxnId() )->retrieve( $oCharge->getId() );
	}

	/**
	 * @param \EDD_Payment $oEddPayment
	 * @return int
	 */
	public function getFreeagentContactIdFromEddPayment( $oEddPayment ) {
		return $this->getEddCustomerFromEddPayment( $oEddPayment )
					->get_meta( 'freeagent_contact_id' );
	}

	/**
	 * @param \EDD_Payment $oEddPayment
	 * @return array
	 */
	public function getFreeagentInvoiceIdsFromEddPayment( $oEddPayment ) {
		$aIds = $oEddPayment->get_meta( self::KEY_FREEAGENT_INVOICE_IDS );
		return is_array( $aIds ) ? $aIds : array();
	}

	/**
	 * @param ChargeVO                    $oCharge
	 * @param Entities\Invoices\InvoiceVO $oInvoice
	 * @return $this
	 */
	public function storeFreeagentInvoiceIdForCharge( $oCharge, $oInvoice ) {
		$aInvoiceIds[ $oCharge->getId() ] = $oInvoice->getId();
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

	/**
	 * @param ChargeVO $oCharge
	 * @return bool
	 */
	protected function isPaymentEuVatMossRegion( $oCharge ) {
		$sPaymentCountry = $this->getEddPaymentFromCharge( $oCharge )->address[ 'country' ];
		return ( $sPaymentCountry != 'GB' &&
				 array_key_exists( $sPaymentCountry, $this->getTaxCountriesRates() ) );
	}

	/**
	 * @return array
	 */
	protected function getTaxCountriesRates() {
		$aCountriesToRates = array();
		foreach ( edd_get_tax_rates() as $aCountryRate ) {
			if ( !empty( $aCountryRate[ 'country' ] ) ) {
				$aCountriesToRates[ $aCountryRate[ 'country' ] ] = $aCountryRate[ 'rate' ];
			}
		}
		return $aCountriesToRates;
	}
}