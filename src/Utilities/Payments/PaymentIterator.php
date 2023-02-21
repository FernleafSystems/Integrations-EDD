<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_Payments_Query) uses "page" to paginate
 */
class PaymentIterator extends CommonEntityIterator {

	public const PAGINATION_TYPE = 'page';

	/**
	 * @return \EDD_Payment|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @param int $customerID
	 */
	public function filterByCustomer( $customerID ) :self {
		return $this->setCustomQueryFilter( 'customer', $customerID );
	}

	/**
	 * @param string|array $status
	 */
	public function filterByStatus( $status ) :self {
		return $this->setCustomQueryFilter( 'status', $status );
	}

	protected function getDefaultQueryFilters() :array {
		$defs = parent::getDefaultQueryFilters();
		$defs[ 'status' ] = 'any';
		return $defs;
	}

	/**
	 * @return \EDD_Payment[]
	 */
	protected function runQuery() :array {
		return ( new \EDD_Payments_Query( $this->getFinalQueryFilters() ) )->get_payments();
	}

	/**
	 * The query to count EDD Payment is done through a separate query class
	 * and the parameters and structure is different.
	 * @return int
	 */
	protected function runQueryCount() :int {
		$filters = $this->getFinalQueryFilters();
		if ( isset( $filters[ 'start_date' ] ) ) {
			$filters[ 'start-date' ] = date( 'm/d/Y', $filters[ 'start_date' ] );
			unset( $filters[ 'start_date' ] );
		}
		if ( isset( $filters[ 'end_date' ] ) ) {
			$filters[ 'end-date' ] = date( 'm/d/Y', $filters[ 'end_date' ] );
			unset( $filters[ 'end_date' ] );
		}
		unset( $filters[ 'number' ] );
		unset( $filters[ 'page' ] );

		$eddCounts = (array)edd_count_payments( $filters );
		$stati = $filters[ 'status' ] ?? edd_get_payment_status_keys();
		if ( is_string( $stati ) ) {
			$stati = array_map( 'trim', explode( ',', $stati ) );
		}
		if ( !in_array( 'any', $stati ) ) {
			$eddCounts = array_intersect_key( $eddCounts, array_flip( $stati ) );
		}

		return (int)array_sum( $eddCounts );
	}
}