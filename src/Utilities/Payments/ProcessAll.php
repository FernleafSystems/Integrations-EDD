<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Payments;

/**
 * Class ProcessAllPayments
 * @package FernleafSystems\Integrations\Edd\Utilities
 */
class ProcessAll {

	/**
	 * @param callable $cProcessFunction
	 * @param array    $aQueryOptions
	 */
	public function fix( $cProcessFunction, $aQueryOptions = array() ) {

		$aQueryOptions = array_merge(
			array(
				'orderby' => 'ID',
				'order'   => 'ASC',
				'page'    => 1,
			),
			$aQueryOptions
		);

		do {
			$aPayments = ( new \EDD_Payments_Query( $aQueryOptions ) )->get_payments();
			array_map( $cProcessFunction, $aPayments );

			$aQueryOptions[ 'page' ]++;
		} while ( !empty( $aPayments ) );
	}
}