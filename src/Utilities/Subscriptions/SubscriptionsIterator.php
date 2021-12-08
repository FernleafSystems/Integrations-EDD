<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Subscriptions;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_Subscriptions_DB) uses "offset" to paginate
 */
class SubscriptionsIterator extends CommonEntityIterator {

	/**
	 * @return \EDD_Subscription|null
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
	 * @param int|array $sId
	 * @return $this
	 */
	public function filterByProductId( $sId ) {
		return $this->setCustomQueryFilter( 'product_id', $sId );
	}

	/**
	 * @param string|array $sStatus
	 * @return $this
	 */
	public function filterByStatus( $sStatus ) {
		return $this->setCustomQueryFilter( 'status', $sStatus );
	}

	/**
	 * @return \EDD_Subscription[]
	 */
	protected function runQuery() {
		return ( new \EDD_Subscriptions_DB() )->get_subscriptions( $this->getFinalQueryFilters() );
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		return ( new \EDD_Subscriptions_DB() )->count( $this->getCustomQueryFilters() );
	}
}