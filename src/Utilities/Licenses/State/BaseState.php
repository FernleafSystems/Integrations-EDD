<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\State;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;
use FernleafSystems\Utilities\{
	Data\Adapter\DynPropertiesClass,
	Logic\OneTimeExecute
};
use FernleafSystems\Integrations\Edd\Utilities\Licenses\LicensesIterator;

abstract class BaseState extends DynPropertiesClass {

	use EddCustomerConsumer;
	use EddDownloadConsumer;
	use OneTimeExecute;

	public function __get( string $key ) {
		$this->execute();
		return parent::__get( $key );
	}

	protected function getLicenseIterator() :LicensesIterator {
		$licIT = new LicensesIterator();
		if ( !empty( $this->getEddCustomer() ) ) {
			$licIT->filterByCustomer( $this->getEddCustomer()->id );
		}
		return $licIT;
	}
}