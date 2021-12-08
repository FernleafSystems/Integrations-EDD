<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers;

abstract class BaseLicenses {

	use Consumers\EddCustomerConsumer;
	use Consumers\EddDownloadConsumer;

	private static ?LicensesIterator $LicIterator;

	/**
	 * @param bool $bReset
	 * @return LicensesIterator
	 */
	protected function getLicIterator( bool $bReset = false ) :LicensesIterator {
		if ( $bReset || empty( self::$LicIterator ) ) {
			self::$LicIterator = new LicensesIterator();
			self::$LicIterator->filterByCustomer( $this->getEddCustomer()->id );
		}
		return self::$LicIterator;
	}

	public static function ResetIterator() {
		self::$LicIterator = null;
	}
}