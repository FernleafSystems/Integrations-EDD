<?php

namespace FernleafSystems\Integrations\Edd\Accounting\Reconciliation;

use FernleafSystems\ApiWrappers\Base\ConnectionConsumer;
use FernleafSystems\ApiWrappers\Freeagent\Entities\Contacts\{
	ContactVO,
	Create,
	Retrieve,
	Update
};
use FernleafSystems\Integrations\Edd\Consumers\EddPaymentConsumer;
use FernleafSystems\Integrations\Edd\Utilities\VAT;

/**
 * TODO: Abstract this whole thing into the Freeagent Integration Bridge
 */
class EddCustomerToFreeagentContact {

	use ConnectionConsumer;
	use EddPaymentConsumer;

	public const KEY_FREEAGENT_CONTACT_ID = 'freeagent_contact_id';

	private ?ContactVO $contact = null;

	private ?\EDD_Payment $payment;

	/**
	 * @throws \Exception
	 */
	public function create( bool $updateFromPayment = true ) :ContactVO {
		$customer = $this->getCustomer();

		// If there is no link between Customer and Contact, create it.
		$contactID = $customer->get_meta( self::KEY_FREEAGENT_CONTACT_ID );
		if ( empty( $contactID ) ) {

			$customer = $this->getCustomer();
			$names = explode( ' ', $customer->name, 2 );
			if ( empty( $names[ 1 ] ) ) {
				$names[ 1 ] = 'Surname-Unknown';
			}

			$contact = ( new Create() )
				->setConnection( $this->getConnection() )
				->setFirstName( $names[ 0 ] )
				->setLastName( $names[ 1 ] )
				->setEmail( $customer->email )
				->create();

			if ( empty( $contact ) || empty( $contact->getId() ) ) {
				throw new \Exception( 'Failed to create new Freeagent contact' );
			}

			$customer->update_meta( self::KEY_FREEAGENT_CONTACT_ID, $contact->getId() );
		}

		if ( $updateFromPayment ) {
			$this->updateContactUsingPaymentInfo();
		}

		return $this->getContact();
	}

	/**
	 * @return $this
	 */
	protected function updateContactUsingPaymentInfo() {
		$p = $this->getEddPayment();

		$originalContact = $this->getContact();
		$userInfo = edd_get_payment_meta_user_info( $p->ID );

		$contact = ( new Update() )
			->setConnection( $this->getConnection() )
			->setEntityId( $this->getContact()->getId() )
			->setFirstName( $originalContact->first_name )
			->setLastName( $originalContact->last_name )
			->setEmail( $p->email )
			->setAddress_Line( $userInfo[ 'line1' ] ?? '', 1 )
			->setAddress_Line( $userInfo[ 'line2' ] ?? '', 2 )
			->setAddress_Town( $userInfo[ 'city' ] ?? '' )
			->setAddress_Region( $userInfo[ 'state' ] ?? '' )
			->setAddress_PostalCode( $userInfo[ 'zip' ] ?? '' )
			->setAddress_Country( $this->getCountryNameFromIso2Code( (string)$p->address[ 'country' ] ) )
			->setSalesTaxNumber( VAT::VatNumberFromPayment( $p ) )
			->setOrganisationName( $p->get_meta( VAT::META_META_VAT_COMPANY ) )
			->setUseContactLevelInvoiceSequence( true )
			->setStatus( ContactVO::STATUS_ACTIVE )
			->update();

		return $this->contact = $contact;
	}

	/**
	 * Freeagent uses full country names; EDD uses ISO2 codes
	 */
	protected function getCountryNameFromIso2Code( string $code ) :string {
		$countries = edd_get_country_list();
		$countries[ 'HR' ] = 'Croatia'; // bug when country name is Croatia/Hrvatska and FreeAgent doesn't understand
		return $countries[ $code ] ?? $code;
	}

	protected function getContact() :?ContactVO {
		if ( $this->contact === null ) {
			$contact = ( new Retrieve() )
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
		return edd_get_customer( $this->getEddPayment()->customer_id );
	}

	public function setContact( ContactVO $contact ) :self {
		$this->contact = $contact;
		return $this;
	}
}