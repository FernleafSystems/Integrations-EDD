<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_SL_License_DB) uses "offset" to paginate
 *
 * Class SubscriptionsIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Subscriptions
 */
class LicensesIterator extends CommonEntityIterator {

	/**
	 * @return \EDD_SL_License|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @param int|array $nCustomerId
	 * @return $this
	 */
	public function filterByCustomer( $nCustomerId ) {
		return $this->setCustomQueryFilter( 'customer_id', $nCustomerId );
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