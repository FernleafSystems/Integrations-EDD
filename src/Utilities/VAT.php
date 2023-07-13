<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Utilities;

use FernleafSystems\Integrations\Edd\Utilities\Payments\Payment;

class VAT {

	private const META_LEGACY_VAT_ID = 'vat_number';
	private const META_LEGACY_VAT_COMPANY = 'company';
	public const META_VAT_ID = '_edd_payment_vat_number';
	public const META_VAT_COMPANY = '_edd_payment_vat_company_name';
	public const META_VAT_ADDRESS = '_edd_payment_vat_company_address';
	public const META_VAT_ID_VALID = '_edd_payment_vat_number_valid';
	public const META_VAT_ID_REVERSE_CHARGE = '_edd_payment_vat_reverse_charged';
	public const META_META_VAT_COMPANY = '_edd_payment_vat_company_name';
	public const META_META_VAT_ADDRESS = '_edd_payment_vat_company_address';

	public static function VatNumberFromPayment( \EDD_Payment $payment ) :string {

		$vatNumber = $payment->get_meta( self::META_VAT_ID );
		if ( empty( $vatNumber ) ) {
			$userInfo = edd_get_payment_meta_user_info( $payment->ID );
			$vatNumber = is_string( $userInfo[ self::META_LEGACY_VAT_ID ] ?? null ) ? $userInfo[ self::META_LEGACY_VAT_ID ] : '';

			// No sign of any VAT, so we look to the parent payment if it's available.
			if ( empty( $vatNumber ) && Payment::IsRenewal( $payment ) && !empty( $payment->parent_payment ) ) {
				$vatNumber = self::VatNumberFromPayment( edd_get_payment( $payment->parent_payment ) );
			}

			if ( !empty( $vatNumber ) ) {
				$payment->add_meta( self::META_VAT_ID, $vatNumber, true );
				if ( !empty( $userInfo[ self::META_LEGACY_VAT_COMPANY ] ?? '' ) ) {
					$payment->add_meta( self::META_META_VAT_COMPANY, $userInfo[ self::META_LEGACY_VAT_COMPANY ], true );
					$payment->add_meta( self::META_VAT_ID_VALID, 1, true );
					$payment->add_meta( self::META_VAT_ID_REVERSE_CHARGE, 1, true );
				}
			}
		}
		return (string)$vatNumber;
	}
}