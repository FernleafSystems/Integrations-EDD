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

	const START_PAGE = 0;

	/**
	 * @return \EDD_Customer|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @return array
	 */
	protected function getDefaultQueryFilters() {
		$aQ = parent::getDefaultQueryFilters();
		$aQ[ 'offset' ] = $this->getPage()*static::PER_PAGE;
		return $aQ;
	}

	/**
	 */
	protected function runQuery() {
		$this->setCurrentPageResults(
			array_values( array_map(
				function ( $oCustomerStdClass ) {
					return new \EDD_Customer( $oCustomerStdClass->id );
				},
				( new \EDD_Customer_Query() )->query( $this->getFinalQueryFilters() )
			) )
		);
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		return ( new \EDD_Customer_Query() )->query( [ 'count' => true ] );
	}
}