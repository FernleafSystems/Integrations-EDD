<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

class RetrieveAll {

	/**
	 * @param array $queryParams
	 * @return \EDD_Payment[]
	 */
	public function retrieve( $queryParams = [] ) {
		$all = [];

		$queryParams = array_merge(
			[
				'orderby' => 'ID',
				'order'   => 'ASC',
				'page'    => 1,
			],
			$queryParams
		);

		do {
			$nCountBefore = count( $all );
			$all = array_merge(
				$all,
				( new \EDD_Payments_Query( $queryParams ) )->get_payments()
			);

			$queryParams[ 'page' ]++;
		} while ( $nCountBefore != count( $all ) );

		return $all;
	}
}