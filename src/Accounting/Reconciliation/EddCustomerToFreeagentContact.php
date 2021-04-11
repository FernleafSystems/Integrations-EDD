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
	private $contact;

	/**
	 * @var \EDD_Customer
	 */
	private $customer;

	/**
	 * @var \EDD_Payment
	 */
	private $payment;

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
		if ( empty( $aNames[ 1 ] ) ) {
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
		$pymt = $this->getPayment();

		// Freeagent uses full country names; EDD uses ISO2 codes
		$paymentCountry = $this->getCountryNameFromIso2Code( $pymt->address[ 'country' ] );
		$userInfo = edd_get_payment_meta_user_info( $pymt->ID );

		$originalContact = $this->getContact();

		$contact = ( new Entities\Contacts\Update() )
			->setFirstName( $originalContact->first_name )
			->setLastName( $originalContact->last_name )
			->setConnection( $this->getConnection() )
			->setEntityId( $this->getContact()->getId() )
			->setEmail( $pymt->email )
			->setAddress_Line( $userInfo[ 'line1' ], 1 )
			->setAddress_Line( $userInfo[ 'line2' ], 2 )
			->setAddress_Town( $userInfo[ 'city' ] )
			->setAddress_Region( $userInfo[ 'state' ] )
			->setAddress_PostalCode( $userInfo[ 'zip' ] )
			->setAddress_Country( $paymentCountry )
			->setSalesTaxNumber( $userInfo[ 'vat_number' ] )
			->setOrganisationName( $userInfo[ 'company' ] )
			->setUseContactLevelInvoiceSequence( true )
			->update();

		return $this->setContact( $contact );
	}

	/**
	 * @param string $code
	 * @return string
	 */
	protected function getCountryNameFromIso2Code( $code ) {
		$countries = edd_get_country_list();
		$countries[ 'HR' ] = 'Croatia'; // bug when country name is Croatia/Hrvatska and FreeAgent doesn't understand
		return $countries[ $code ] ?? $code;
	}

	/**
	 * @return Entities\Contacts\ContactVO
	 */
	protected function getContact() {
		if ( !isset( $this->contact ) ) {
			$this->contact = ( new Entities\Contacts\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $this->getCustomer()->get_meta( self::KEY_FREEAGENT_CONTACT_ID ) )
				->retrieve();
		}
		return $this->contact;
	}

	/**
	 * @return \EDD_Customer
	 */
	public function getCustomer() {
		return $this->customer;
	}

	/**
	 * @return \EDD_Payment
	 */
	public function getPayment() {
		return $this->payment;
	}

	/**
	 * @param Entities\Contacts\ContactVO $oContact
	 * @return $this
	 */
	public function setContact( $oContact ) {
		$this->contact = $oContact;
		return $this;
	}

	/**
	 * @param \EDD_Customer $oCustomer
	 * @return $this
	 */
	public function setCustomer( $oCustomer ) {
		$this->customer = $oCustomer;
		return $this;
	}

	/**
	 * @param \EDD_Payment $oPayment
	 * @return $this
	 */
	public function setPayment( $oPayment ) {
		$this->payment = $oPayment;
		return $this;
	}
}