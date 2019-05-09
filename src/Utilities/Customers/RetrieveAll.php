<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Customers;

/**
 * Class RetrieveAll
 * @package FernleafSystems\Integrations\Edd\Utilities\Customers
 */
class RetrieveAll {

	/**
	 * @param array $aQueryOptions
	 * @return \EDD_Customer[]
	 */
	public function retrieve( $aQueryOptions = [] ) {
		$aAll = [];

		$nPerPage = 20; // default anyway
		if ( isset( $aQueryOptions[ 'per_page' ] ) ) {
			$nPerPage = $aQueryOptions[ 'per_page' ];
			unset( $aQueryOptions[ 'per_page' ] );
		}

		$aQueryOptions = array_merge(
			[
				'orderby' => 'id',
				'order'   => 'ASC',
				'number'  => $nPerPage,
				'offset'  => 0,
			],
			$aQueryOptions
		);

		$nPage = 0;
		do {
			$aQueryOptions = array_merge(
				$aQueryOptions,
				[
					'offset' => $nPage*$nPerPage,
				]
			);

			$nCountBefore = count( $aAll );
			$aAll = array_merge(
				$aAll,
				array_map(
					function ( $oCustomerStdClass ) {
						return new \EDD_Customer( $oCustomerStdClass->id );
					},
					( new \EDD_Customer_Query() )->query( $aQueryOptions )
				)
			);

			$nPage++;
		} while ( $nCountBefore != count( $aAll ) );

		return $aAll;
	}
}