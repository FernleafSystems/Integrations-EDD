<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities;

/**
 * TODO: Abstract this whole thing into the Freeagent Integration Bridge
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
	 * @throws \Exception
	 */
	public function create( bool $updateFromPayment = true ) :Entities\Contacts\ContactVO {

		// If there is no link between Customer and Contact, create it.
		$contactID = $this->getCustomer()->get_meta( self::KEY_FREEAGENT_CONTACT_ID );
		if ( empty( $contactID ) ) {
			$id = $this->createNewFreeagentContact();
			if ( empty( $id ) ) {
				throw new \Exception( 'Failed to create new Freeagent contact' );
			}
		}

		if ( $updateFromPayment ) {
			$this->updateContactUsingPaymentInfo();
		}

		return $this->getContact();
	}

	public function update() :Entities\Contacts\ContactVO {
		return $this->create();
	}

	protected function createNewFreeagentContact() :string {
		$customer = $this->getCustomer();
		$names = explode( ' ', $customer->name, 2 );
		if ( empty( $names[ 1 ] ) ) {
			$names[ 1 ] = 'Surname-Unknown';
		}

		$contact = ( new Entities\Contacts\Create() )
			->setConnection( $this->getConnection() )
			->setFirstName( $names[ 0 ] )
			->setLastName( $names[ 1 ] )
			->setEmail( $customer->email )
			->create();

		$customer->update_meta( self::KEY_FREEAGENT_CONTACT_ID, $contact->getId() );

		return (string)$contact->getId();
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
			->setAddress_Line( $userInfo[ 'line1' ] ?? '', 1 )
			->setAddress_Line( $userInfo[ 'line2' ] ?? '', 2 )
			->setAddress_Town( $userInfo[ 'city' ] ?? '' )
			->setAddress_Region( $userInfo[ 'state' ] ?? '' )
			->setAddress_PostalCode( $userInfo[ 'zip' ] ?? '' )
			->setAddress_Country( $paymentCountry )
			->setSalesTaxNumber( $userInfo[ 'vat_number' ] ?? '' )
			->setOrganisationName( $userInfo[ 'company' ] ?? '' )
			->setUseContactLevelInvoiceSequence( true )
			->setStatus( 'Active' )
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

	protected function getContact() :?Entities\Contacts\ContactVO {
		if ( !isset( $this->contact ) ) {
			$contact = ( new Entities\Contacts\Retrieve() )
				->setConnection( $this->getConnection() )
				->setEntityId( $this->getCustomer()->get_meta( self::KEY_FREEAGENT_CONTACT_ID ) )
				->retrieve();
			if ( !empty( $contact ) ) {
				$this->contact = $contact;
			}
		}
		return $this->contact;
	}

	public function getCustomer() :?\EDD_Customer {
		return $this->customer;
	}

	public function getPayment() :?\EDD_Payment {
		return $this->payment;
	}

	public function setContact( Entities\Contacts\ContactVO $contact ) :self {
		$this->contact = $contact;
		return $this;
	}

	public function setCustomer( \EDD_Customer $customer ) :self {
		$this->customer = $customer;
		return $this;
	}

	public function setPayment( \EDD_Payment $payment ) :self {
		$this->payment = $payment;
		return $this;
	}
}