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
	 */
	public function filterByCustomer( $customerID ) :self {
		return $this->setCustomQueryFilter( 'customer_id', $customerID );
	}

	public function filterByDownloadIDs( array $downloadIDs ) :self {
		return $this->setCustomQueryFilter( 'download_id', $downloadIDs );
	}

	/**
	 * @return \EDD_SL_License[]
	 */
	protected function runQuery() :array {
		return ( new \EDD_SL_License_DB() )->get_licenses( $this->getFinalQueryFilters() );
	}

	protected function runQueryCount() :int {
		return ( new \EDD_SL_License_DB() )->count( $this->getCustomQueryFilters() );
	}
}