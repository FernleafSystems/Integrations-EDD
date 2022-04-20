<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class GetDownloadsFromPayment {

	/**
	 * @param \EDD_Payment $pym
	 * @return \EDD_Download[]
	 */
	public function asObjects( $pym ) :array {
		return $this->retrieve( $pym, 'objects' );
	}

	/**
	 * @param \EDD_Payment $pym
	 * @return int[]
	 */
	public function asIds( $pym ) :array {
		return $this->retrieve( $pym, 'ids' );
	}

	/**
	 * @param \EDD_Payment $pym
	 * @param string       $toExtract
	 */
	public function retrieve( $pym, $toExtract ) :array {
		$downloads = [];
		foreach ( $pym->downloads as $dln ) {
			/** @var array $dln */
			switch ( $toExtract ) {
				case 'ids':
					$mItem = $dln[ 'id' ];
					break;

				case 'arrays':
					$mItem = $dln;
					break;

				case 'objects':
				default:
					$mItem = new \EDD_Download( $dln[ 'id' ] );
					break;
			}
			$downloads[] = $mItem;
		}
		return $downloads;
	}
}