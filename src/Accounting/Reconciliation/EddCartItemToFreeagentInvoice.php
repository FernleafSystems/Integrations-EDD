<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;
use FernleafSystems\Integrations\Edd\Entities\CartItemVo;
use FernleafSystems\Integrations\Edd\Utilities\GetTransactionIdFromCartItem;
use FernleafSystems\Integrations\Freeagent\Consumers\ContactVoConsumer;
use FernleafSystems\Integrations\Freeagent\Consumers\FreeagentConfigVoConsumer;

/**
 * Class EddCartItemToFreeagentInvoice
 * @package FernleafSystems\Integrations\Edd\Accounting\Reconciliation\StripeFreeagent
 */
class EddCartItemToFreeagentInvoice {

	use ConnectionConsumer,
		ContactVoConsumer,
		FreeagentConfigVoConsumer;

	/**
	 * @var \EDD_Payment
	 */
	private $oPayment;

	/**
	 * @param CartItemVo $oCartItem
	 * @return Entities\Invoices\InvoiceVO|null
	 */
	public function createInvoice( $oCartItem ) {

		$oContact = $this->getContactVo();
		$oLocalPayment = $this->getPayment();

		$nDatedOn = empty( $oLocalPayment->date ) ? time() : strtotime( $oLocalPayment->date );

		$oInvoiceCreator = ( new Entities\Invoices\Create() )
			->setConnection( $this->getConnection() )
			->setContact( $oContact )
			->setDatedOn( $nDatedOn )
			->setPaymentTerms( 14 )
			->setExchangeRate( 1.0 )// TODO: Verify this perhaps with Stripe Txn
			->setCurrency( $oLocalPayment->currency )
			->setComments(
				serialize(
					array(
						'payment_id'        => $oLocalPayment->ID,
						'gateway_charge_id' => ( new GetTransactionIdFromCartItem() )->retrieve( $oCartItem )
					)
				)
			)
			->addInvoiceItemVOs( $this->buildLineItemsFromCartItem( $oCartItem ) );

		if ( $this->isPaymentEuVatMossRegion() ) {
			$oInvoiceCreator->setEcPlaceOfSupply( $oContact->getCountry() )
							->setEcStatusVatMoss();
		}
		else {
			$oInvoiceCreator->setEcStatusNonEc();
		}

		$oExportedInvoice = $oInvoiceCreator->create();

		if ( !is_null( $oExportedInvoice ) ) {
			sleep( 2 );
			$oExportedInvoice = $this->markInvoiceAsSent( $oExportedInvoice );
		}
		return $oExportedInvoice;
	}

	/**
	 * @param Entities\Invoices\InvoiceVO $oInvoice
	 * @return Entities\Invoices\InvoiceVO
	 */
	protected function markInvoiceAsSent( $oInvoice ) {
		( new Entities\Invoices\MarkAs() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oInvoice->getId() )
			->sent();
		return ( new Entities\Invoices\Retrieve() )
			->setConnection( $this->getConnection() )
			->setEntityId( $oInvoice->getId() )
			->retrieve();
	}

	/**
	 * @return string[]
	 */
	protected function getTransactionIdsFromPayment() {
		/** @var \EDD_Subscription[] $aSubscriptions */
		$aSubscriptions = ( new \EDD_Subscriptions_DB() )
			->get_subscriptions( array( 'parent_payment_id' => $this->getPayment()->ID ) );

		return array_map(
			function ( $oSub ) {
				/** @var \EDD_Subscription $oSub */
				return $oSub->get_transaction_id();
			},
			$aSubscriptions
		);
	}

	/**
	 * @param CartItemVo $oCartItem
	 * @return Entities\Invoices\Items\InvoiceItemVO[]
	 */
	protected function buildLineItemsFromCartItem( $oCartItem ) {
		$aInvoiceItems = array();

		$aInvoiceItems[] = ( new Entities\Invoices\Items\InvoiceItemVO() )
			->setDescription( $oCartItem->getName() )
			->setQuantity( $oCartItem->getQuantity() )
			->setPrice( $oCartItem->getSubtotal() )
			->setSalesTaxRate( $oCartItem->getTaxRate()*100 )
			->setCategoryId( $this->getFreeagentConfigVO()->getInvoiceItemCategoryId() )
			->setType( 'Years' ); //TODO: Hard coded, need to adapt to purchase

		return $aInvoiceItems;
	}

	/**
	 * @return \EDD_Payment
	 */
	public function getPayment() {
		return $this->oPayment;
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

	/**
	 * @return bool
	 */
	protected function isPaymentEuVatMossRegion() {
		$sPaymentCountry = $this->getPayment()->address[ 'country' ];
		return ( $sPaymentCountry != 'GB' &&
				 array_key_exists( $sPaymentCountry, $this->getTaxCountriesRates() ) );
	}

	/**
	 * @param \EDD_Payment $oPayment
	 * @return $this
	 */
	public function setPayment( $oPayment ) {
		$this->oPayment = $oPayment;
		return $this;
	}
}