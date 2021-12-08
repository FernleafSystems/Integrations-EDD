<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_Payments_Query) uses "page" to paginate
 */
class PaymentIterator extends CommonEntityIterator {

	const PAGINATION_TYPE = 'page';

	/**
	 * @return \EDD_Payment|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @param int $customerID
	 * @return $this
	 */
	public function filterByCustomer( $customerID ) {
		return $this->setCustomQueryFilter( 'customer', $customerID );
	}

	/**
	 * @param string|array $status
	 * @return $this
	 */
	public function filterByStatus( $status ) {
		return $this->setCustomQueryFilter( 'status', $status );
	}

	/**
	 * @return array
	 */
	protected function getDefaultQueryFilters() {
		$defs = parent::getDefaultQueryFilters();
		$defs[ 'status' ] = 'all';
		return $defs;
	}

	/**
	 * @return \EDD_Payment[]
	 */
	protected function runQuery() {
		return ( new \EDD_Payments_Query( $this->getFinalQueryFilters() ) )->get_payments();
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		$counts = (array)wp_count_posts( 'edd_payment' );
		$filters = $this->getFinalQueryFilters();
		$stati = $filters[ 'status' ] ?? edd_get_payment_status_keys();
		if ( is_string( $stati ) ) {
			$stati = array_map( 'trim', explode( ',', $stati ) );
		}
		if ( !in_array( 'all', $stati ) ) {
			$counts = array_intersect_key( $counts, array_flip( $stati ) );
		}
		return array_sum( $counts );
	}
}