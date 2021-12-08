<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Customers;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_Customer_Query) uses "offset" to paginate
 */
class CustomerIterator extends CommonEntityIterator {

	/**
	 * @return \EDD_Customer|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @return \EDD_Customer[]
	 */
	protected function runQuery() {
		return array_map(
			function ( $oStdClass ) {
				return new \EDD_Customer( $oStdClass->id );
			},
			( new \EDD_Customer_Query() )->query( $this->getFinalQueryFilters() )
		);
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		return ( new \EDD_Customer_Query() )->query( [ 'count' => true ] );
	}
}