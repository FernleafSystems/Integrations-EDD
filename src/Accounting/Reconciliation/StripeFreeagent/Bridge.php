<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation\StripeFreeagent;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\EddCartItemToFreeagentInvoice;
use FernleafSystems\Integrations\Edd\Accounting\Reconciliation\EddCustomerToFreeagentContact;
use FernleafSystems\Integrations\Edd\Entities\CartItemVo;
use FernleafSystems\Integrations\Edd\Utilities;
use FernleafSystems\Integrations\Stripe_Freeagent\Consumers\FreeagentConfigVoConsumer;
use FernleafSystems\Integrations\Stripe_Freeagent\Reconciliation\Bridge\BridgeInterface;
use Stripe\BalanceTransaction;

class Bridge implements BridgeInterface {

	use ConnectionConsumer,
		FreeagentConfigVoConsumer;

	const KEY_FREEAGENT_INVOICE_IDS = 'freeagent_invoice_ids';

	public function __construct() {
		EDD_Recurring(); // initializes anything that's required
	}

	/**
	 * @param BalanceTransaction $oBalTxn
	 * @param bool               $bUpdateOnly
	 * @return Entities\Contacts\ContactVO
	 */
	public function createFreeagentContact( $oBalTxn, $bUpdateOnly = false ) {
		$oPayment = $this->getEddPaymentFromStripeBalanceTxn( $oBalTxn );
		return $this->createFreeagentContactFromPayment( $oPayment, $bUpdateOnly );
	}

	/**
	 * @param \EDD_Payment $oPayment
	 * @param bool               $bUpdateOnly
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
	public function createFreeagentInvoiceFromStripeBalanceTxn( $sChargeTxnId ) {
		return $this->createFreeagentInvoiceFromEddPaymentCartItem(
			$this->getCartItemDetailsFromGatewayTxn( $sChargeTxnId )
		);
	}

	/**
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
			function ( $sTxnId ) { /** @var string $sTxnId */
				return $this->createFreeagentInvoiceFromStripeBalanceTxn( $sTxnId );
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
			throw new \Exception( sprintf( 'Found more than 1 cart item for a Stripe Txn "%s"', $sGatewayTxnId ) );
		}
		return array_pop( $aCartItems );
	}

	/**
	 * @param BalanceTransaction $oStripeTxn
	 * @return \EDD_Subscription[]
	 */
	protected function getInternalSubscriptionsForStripeTxn( $oStripeTxn ) {
		return ( new \EDD_Subscriptions_DB() )
			->get_subscriptions( array( 'transaction_id' => $oStripeTxn->source ) );
	}

	/**
	 * @param \EDD_Payment $oEddPayment
	 * @return \EDD_Customer
	 */
	private function getEddCustomerFromEddPayment( $oEddPayment ) {
		return new \EDD_Customer( $oEddPayment->customer_id );
	}

	/**
	 * @param BalanceTransaction $oBalTxn
	 * @return \EDD_Payment|null
	 */
	private function getEddPaymentFromStripeBalanceTxn( $oBalTxn ) {
		return ( new Utilities\GetEddPaymentFromGatewayTxnId() )->retrieve( $oBalTxn->source );
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
	 * @param BalanceTransaction $oBalTxn
	 * @return int
	 */
	public function getFreeagentContactIdFromStripeBalTxn( $oBalTxn ) {
		return $this->getFreeagentContactIdFromEddPayment(
			$this->getEddPaymentFromStripeBalanceTxn( $oBalTxn )
		);
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
	 * @param BalanceTransaction $oStripeTxn
	 * @return int
	 */
	public function getFreeagentInvoiceIdFromStripeBalanceTxn( $oStripeTxn ) {
		$aIds = $this->getFreeagentInvoiceIdsFromEddPayment(
			$this->getEddPaymentFromStripeBalanceTxn( $oStripeTxn )
		);
		return isset( $aIds[ $oStripeTxn->source ] ) ? $aIds[ $oStripeTxn->source ] : null;
	}

	/**
	 * @param BalanceTransaction $oStripeTxn
	 * @return bool
	 */
	public function verifyStripeToInternalPaymentLink( $oStripeTxn ) {
		return !is_null( $this->getEddPaymentFromStripeBalanceTxn( $oStripeTxn ) );
	}
}