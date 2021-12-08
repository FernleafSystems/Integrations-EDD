<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

class ProcessAll {

	/**
	 * @param callable $cProcessFunction
	 * @param array    $query
	 */
	public function process( $cProcessFunction, $query = [] ) {

		$query = array_merge(
			[
				'orderby' => 'ID',
				'order'   => 'ASC',
				'page'    => 1,
			],
			$query
		);

		do {
			$payments = ( new \EDD_Payments_Query( $query ) )->get_payments();
			array_map( $cProcessFunction, $payments );
			$query[ 'page' ]++;
		} while ( !empty( $payments ) );
	}
}