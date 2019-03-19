<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

use FernleafSystems\Integrations\Edd\Utilities\Base\EddEntityIterator;

/**
 * The query class (EDD_Payments_Query) uses "page" to paginate
 *
 * Class PaymentIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Payments
 */
class PaymentIterator extends EddEntityIterator {

	/**
	 * @return \EDD_Payment|null
	 */
	public function current() {
		return parent::current();
	}

	/**
	 * @param int $nCustomerId
	 * @return $this
	 */
	public function filterByCustomer( $nCustomerId ) {
		return $this->setCustomQueryFilter( 'customer', $nCustomerId );
	}

	/**
	 * @return array
	 */
	protected function getDefaultQueryFilters() {
		$aQ = parent::getDefaultQueryFilters();
		$aQ[ 'page' ] = $this->getPage();
		return $aQ;
	}

	/**
	 */
	protected function runQuery() {
		$this->setCurrentPageResults(
			( new \EDD_Payments_Query( $this->getFinalQueryFilters() ) )->get_payments()
		);
	}

	/**
	 * @return int
	 */
	protected function runQueryCount() {
		$aCounts = (array)wp_count_posts( 'edd_payment' );
		$aFil = $this->getFinalQueryFilters();
		$aStati = isset( $aFil[ 'status' ] ) ? isset( $aFil[ 'status' ] ) : edd_get_payment_status_keys();
		return array_sum( array_intersect_key( $aCounts, array_flip( $aStati ) ) );
	}
}