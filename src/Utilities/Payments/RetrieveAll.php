<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

/**
 * Class RetrieveAll
 * @package FernleafSystems\Integrations\Edd\Utilities\Payments
 */
class RetrieveAll {

	/**
	 * @param array $aQueryOptions
	 * @return \EDD_Payment[]
	 */
	public function retrieve( $aQueryOptions = array() ) {
		$aAll = array();

		$aQueryOptions = array_merge(
			array(
				'orderby' => 'ID',
				'order'   => 'ASC',
				'page'    => 1,
			),
			$aQueryOptions
		);

		do {
			$nCountBefore = count( $aAll );
			$aAll = array_merge(
				$aAll,
				( new \EDD_Payments_Query( $aQueryOptions ) )->get_payments()
			);

			$aQueryOptions[ 'page' ]++;
		} while ( $nCountBefore != count( $aAll ) );

		return $aAll;
	}
}