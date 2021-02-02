<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

use FernleafSystems\Integrations\Edd\Utilities\Base\CommonEntityIterator;

/**
 * The query class (EDD_Payments_Query) uses "page" to paginate
 *
 * Class PaymentIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Payments
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
		$aCounts = (array)wp_count_posts( 'edd_payment' );
		$aFil = $this->getFinalQueryFilters();
		$aStati = isset( $aFil[ 'status' ] ) ? $aFil[ 'status' ] : edd_get_payment_status_keys();
		if ( is_string( $aStati ) ) {
			$aStati = array_map( 'trim', explode( ',', $aStati ) );
		}
		if ( !in_array( 'all', $aStati ) ) {
			$aCounts = array_intersect_key( $aCounts, array_flip( $aStati ) );
		}
		return array_sum( $aCounts );
	}
}