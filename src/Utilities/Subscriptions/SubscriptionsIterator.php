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
	 */
	public function filterByCustomer( $customerID ) :self {
		return $this->setCustomQueryFilter( 'customer_id', $customerID );
	}

	/**
	 * @param int|array $ID
	 */
	public function filterByProductId( $ID ) :self {
		return $this->setCustomQueryFilter( 'product_id', $ID );
	}

	/**
	 * @param string|array $status
	 */
	public function filterByStatus( $status ) :self {
		return $this->setCustomQueryFilter( 'status', $status );
	}

	/**
	 * @return \EDD_Subscription[]
	 */
	protected function runQuery() :array {
		return ( new \EDD_Subscriptions_DB() )->get_subscriptions( $this->getFinalQueryFilters() );
	}

	protected function runQueryCount() :int {
		return ( new \EDD_Subscriptions_DB() )->count( $this->getCustomQueryFilters() );
	}
}