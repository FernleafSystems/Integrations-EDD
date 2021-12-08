<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_SL_License_DB) uses "offset" to paginate
 */
class LicensesIterator extends CommonEntityIterator {

	/**
	 * @return \EDD_SL_License|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @param int|array $customerID
	 * @return $this
	 */
	public function filterByCustomer( $customerID ) {
		return $this->setCustomQueryFilter( 'customer_id', $customerID );
	}

	/**
	 * @return \EDD_SL_License[]
	 */
	protected function runQuery() {
		return ( new \EDD_SL_License_DB() )->get_licenses( $this->getFinalQueryFilters() );
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		return ( new \EDD_SL_License_DB() )->count( $this->getCustomQueryFilters() );
	}
}