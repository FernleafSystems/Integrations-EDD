<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Customers;

class RetrieveAll {

	/**
	 * @return \EDD_Customer[]
	 */
	public function retrieve( array $queryOptions = [] ) :array {
		$all = [];

		$perPage = 20; // default anyway
		if ( isset( $queryOptions[ 'per_page' ] ) ) {
			$perPage = $queryOptions[ 'per_page' ];
			unset( $queryOptions[ 'per_page' ] );
		}

		$queryOptions = array_merge(
			[
				'orderby' => 'id',
				'order'   => 'ASC',
				'number'  => $perPage,
				'offset'  => 0,
			],
			$queryOptions
		);

		$currentPage = 0;
		do {
			$queryOptions = array_merge(
				$queryOptions,
				[
					'offset' => $currentPage*$perPage,
				]
			);

			$countBefore = count( $all );
			$all = array_merge(
				$all,
				array_map(
					function ( $customerStdClass ) {
						return new \EDD_Customer( $customerStdClass->id );
					},
					( new \EDD_Customer_Query() )->query( $queryOptions )
				)
			);

			$currentPage++;
		} while ( $countBefore != count( $all ) );

		return $all;
	}
}