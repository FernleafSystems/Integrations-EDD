<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Invoices\InvoiceVO;
use FernleafSystems\Integrations\Edd\Consumers\EddPaymentConsumer;
use FernleafSystems\Integrations\Edd\Entities\CartItemVo;
use FernleafSystems\Integrations\Edd\Utilities;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Freeagent\DataWrapper\ChargeVO;

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
	 * TODO : Be able to replace cartiem with ChargeVO so we can abstract this.
	 * First attempts to locate a previously created invoice for this Payment.
	 * @param CartItemVo $oCartItem
	 * @return Entities\Invoices\InvoiceVO
	 */
	public function createFreeagentInvoiceFromEddPaymentCartItem( $oCartItem ) {
		$oInvoice = null;

		$oEddPayment = new \EDD_Payment( $oCartItem->getParentPaymentId() );

		// 1st: Create/update the FreeAgent Contact.
		$nContactId = $this->getFreeagentContactIdFromEddPayment( $oEddPayment );
		$oContact = $this->createFreeagentContactFromPayment( $oEddPayment, !empty( $nContactId ) );

		// 2nd: Retrieve/Create FreeAgent Invoice
		$sTxnId = ( new Utilities\GetTransactionIdFromCartItem() )->retrieve( $oCartItem );
		$aInvoiceIds = $this->getFreeagentInvoiceIdsFromEddPayment( $oEddPayment );

		$nInvoiceId = isset( $aInvoiceIds[ $sTxnId ] ) ? $aInvoiceIds[ $sTxnId ] : null;
		if ( !empty( $nInvoiceId ) ) {
			$oInvoice = ( new Entities\Invoices\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $nInvoiceId )
				->retrieve();
		}

		if ( empty( $oInvoice ) ) {
			$oInvoice = ( new EddCartItemToFreeagentInvoice() )
				->setFreeagentConfigVO( $this->getFreeagentConfigVO() )
				->setConnection( $this->getConnection() )
				->setContactVo( $oContact )
				->setPayment( $oEddPayment )
				->createInvoice( $oCartItem );

			if ( !is_null( $oInvoice ) ) {
				$aInvoiceIds[ $sTxnId ] = $oInvoice->getId();
				$oEddPayment->update_meta( self::KEY_FREEAGENT_INVOICE_IDS, $aInvoiceIds );
			}
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
		$aCartItems = ( new Utilities\GetCartItemsFromTransactionId() )
			->retrieve( $sGatewayTxnId );
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
	 * @param ChargeVO $oCharge
	 * @return bool
	 */
	public function verifyInternalPaymentLink( $oCharge ) {
		return !is_null( $this->getEddPaymentFromCharge( $oCharge ) );
	}

	/**
	 * @return bool
	 */
	protected function isPaymentEuVatMossRegion() {
		$sPaymentCountry = $this->getEddPaymentFromCharge()->address[ 'country' ];
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