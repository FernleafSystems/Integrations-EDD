<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Utilities;

class Countries {

	public const EC_COUNTRIES = [
		'AT',
		'BE',
		'BG',
		'CY',
		'CZ',
		'DE',
		'DK',
		'EE',
		'ES',
		'FI',
		'FR',
		'GR',
		'HR',
		'HU',
		'IE',
		'IT',
		'LT',
		'LU',
		'LV',
		'MT',
		'NL',
		'PL',
		'PT',
		'RO',
		'SE',
		'SI',
		'SK',
	];

	public static function TaxedCountries() :array {
		$countries = \array_filter( \array_map(
			fn( $countryData ) => $countryData[ 'country' ],
			edd_get_tax_rates()
		) );
		\asort( $countries );
		return \array_values( $countries );
	}
}