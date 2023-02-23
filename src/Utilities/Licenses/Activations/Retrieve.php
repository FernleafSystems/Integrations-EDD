<?php declare( strict_types=1 );

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations;

use FernleafSystems\Wordpress\Services\Utilities\Licenses\EddActions;

class Retrieve {

	/**
	 * @return EddActivationVO[]
	 */
	public function forUrl( string $url, bool $activated = true ) :array {
		$acts = ( new \EDD_SL_Activations_DB() )->get_activations( [
			'site_name' => EddActions::CleanUrl( $url ),
			'activated' => $activated ? 1 : 0,
		] );
		return array_map(
			fn( $act ) => ( new EddActivationVO() )->applyFromArray( (array)$act ),
			is_array( $acts ) ? $acts : []
		);
	}

	/**
	 * @return EddActivationVO[]
	 */
	public function forLicense( \EDD_SL_License $lic ) :array {
		return array_map(
			function ( $act ) use ( $lic ) {
				$act = ( new EddActivationVO() )->applyFromArray( (array)$act );
				$act->lic_expired = $lic->is_expired();
				return $act;
			},
			$lic->get_activations()
		);
	}
}