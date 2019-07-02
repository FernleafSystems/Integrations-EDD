<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;

/**
 * TODO: Abstract this whole thing into the Freeagent Integration Bridge
 * Class EddCustomerToFreeagentContact
 * @package FernleafSystems\Wordpress\Plugin\Edd\Freeagent\Adaptation
 */
class EddCustomerToFreeagentContact {

	use ConnectionConsumer;
	const KEY_FREEAGENT_CONTACT_ID = 'freeagent_contact_id';

	/**
	 * @var Entities\Contacts\ContactVO
	 */
	private $oContact;

	/**
	 * @var \EDD_Customer
	 */
	private $oCustomer;

	/**
	 * @var \EDD_Payment
	 */
	private $oPayment;

	/**
	 * @return Entities\Contacts\ContactVO
	 */
	public function create() {

		// If there is no link between Customer and Contact, create it.
		$nFreeagentContactId = $this->getCustomer()
									->get_meta( self::KEY_FREEAGENT_CONTACT_ID );
		if ( empty( $nFreeagentContactId ) ) {
			$this->createNewFreeagentContact();
		}

		return $this->update();
	}

	/**
	 * @return Entities\Contacts\ContactVO
	 */
	public function update() {
		// Now update the Contact with any business information from the Payment.
		return $this->updateContactUsingPaymentInfo()
					->getContact();
	}

	/**
	 * @return string
	 */
	protected function createNewFreeagentContact() {
		$oCustomer = $this->getCustomer();
		$aNames = explode( ' ', $oCustomer->name, 2 );
		if ( !isset( $aNames[ 1 ] ) ) {
			$aNames[ 1 ] = 'Surname-Unknown';
		}

		$oContact = ( new Entities\Contacts\Create() )
			->setConnection( $this->getConnection() )
			->setFirstName( $aNames[ 0 ] )
			->setLastName( $aNames[ 1 ] )
			->setEmail( $oCustomer->email )
			->create();

		$oCustomer->update_meta( self::KEY_FREEAGENT_CONTACT_ID, $oContact->getId() );

		return $oContact->getId();
	}

	/**
	 * @return $this
	 */
	protected function updateContactUsingPaymentInfo() {
		$oPayment = $this->getPayment();

		// Freeagent uses full country names; EDD uses ISO2 codes
		$sPaymentCountry = $this->getCountryNameFromIso2Code( $oPayment->address[ 'country' ] );
		$aUserInfo = edd_get_payment_meta_user_info( $oPayment->ID );

		$oOriginalContact = $this->getContact();

		$oContact = ( new Entities\Contacts\Update() )
			->setFirstName( $oOriginalContact->getFirstName() )
			->setLastName( $oOriginalContact->getLastName() )
			->setConnection( $this->getConnection() )
			->setEntityId( $this->getContact()->getId() )
			->setEmail( $oPayment->email )
			->setAddress_Line( $aUserInfo[ 'line1' ], 1 )
			->setAddress_Line( $aUserInfo[ 'line2' ], 2 )
			->setAddress_Town( $aUserInfo[ 'city' ] )
			->setAddress_Region( $aUserInfo[ 'state' ] )
			->setAddress_PostalCode( $aUserInfo[ 'zip' ] )
			->setAddress_Country( $sPaymentCountry )
			->setSalesTaxNumber( $aUserInfo[ 'vat_number' ] )
			->setOrganisationName( $aUserInfo[ 'company' ] )
			->setUseContactLevelInvoiceSequence( true )
			->update();

		return $this->setContact( $oContact );
	}

	/**
	 * @param string $sCode
	 * @return string
	 */
	protected function getCountryNameFromIso2Code( $sCode ) {
		$aCountries = edd_get_country_list();
		$aCountries[ 'HR' ] = 'Croatia'; // bug when country name is Croatia/Hrvatska and FreeAgent doesn't understand
		return isset( $aCountries[ $sCode ] ) ? $aCountries[ $sCode ] : $sCode;
	}

	/**
	 * @return Entities\Contacts\ContactVO
	 */
	protected function getContact() {
		if ( !isset( $this->oContact ) ) {
			$this->oContact = ( new Entities\Contacts\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $this->getCustomer()->get_meta( self::KEY_FREEAGENT_CONTACT_ID ) )
				->retrieve();
		}
		return $this->oContact;
	}

	/**
	 * @return \EDD_Customer
	 */
	public function getCustomer() {
		return $this->oCustomer;
	}

	/**
	 * @return \EDD_Payment
	 */
	public function getPayment() {
		return $this->oPayment;
	}

	/**
	 * @param Entities\Contacts\ContactVO $oContact
	 * @return $this
	 */
	public function setContact( $oContact ) {
		$this->oContact = $oContact;
		return $this;
	}

	/**
	 * @param \EDD_Customer $oCustomer
	 * @return $this
	 */
	public function setCustomer( $oCustomer ) {
		$this->oCustomer = $oCustomer;
		return $this;
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