<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Customers;

use FernleafSystems\Integrations\Edd\Utilities\Base\EddEntityIterator;

/**
 * The query class (EDD_Customer_Query) uses "offset" to paginate
 *
 * Class CustomerIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Customers
 */
class CustomerIterator extends EddEntityIterator {

	const PAGINATION_TYPE = 'offset';

	/**
	 * @return \EDD_Customer|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 */
	protected function runQuery() {
		$this->setCurrentPageResults(
			array_map(
				function ( $oStdClass ) {
					return new \EDD_Customer( $oStdClass->id );
				},
				( new \EDD_Customer_Query() )->query( $this->getFinalQueryFilters() )
			)
		);
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		return ( new \EDD_Customer_Query() )->query( [ 'count' => true ] );
	}
}