<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations;

use FernleafSystems\Wordpress\Services\Utilities\Licenses\EddActions;

/**
 * Class RetrieveForUrl
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses\Activations
 */
class Retrieve {

	/**
	 * @param string $sUrl
	 * @param bool   $bActivated
	 * @return EddActivationVO[]
	 */
	public function forUrl( string $sUrl, bool $bActivated = true ) :array {
		$aActs = ( new \EDD_SL_Activations_DB() )->get_activations( [
			'site_name' => EddActions::CleanUrl( $sUrl ),
			'activated' => $bActivated ? 1 : 0,
		] );
		return array_map(
			fn( $aAct ) => ( new EddActivationVO() )->applyFromArray( (array)$aAct ),
			is_array( $aActs ) ? $aActs : []
		);
	}

	/**
	 * @param \EDD_SL_License $oLic
	 * @return EddActivationVO[]
	 */
	public function forLicense( \EDD_SL_License $oLic ) :array {
		return array_map(
			function ( $oAct ) use ( $oLic ) {
				$oAct = ( new EddActivationVO() )->applyFromArray( (array)$oAct );
				$oAct->lic_expired = $oLic->is_expired();
				return $oAct;
			},
			$oLic->get_activations()
		);
	}
}