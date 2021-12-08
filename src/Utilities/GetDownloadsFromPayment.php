<?php

namespace FernleafSystems\Integrations\Edd\Utilities;

class GetDownloadsFromPayment {

	/**
	 * @param \EDD_Payment $oPayment
	 * @return \EDD_Download[]
	 */
	public function asObjects( $oPayment ) {
		return $this->retrieve( $oPayment, 'objects' );
	}

	/**
	 * @param \EDD_Payment $oPayment
	 * @return int[]
	 */
	public function asIds( $oPayment ) {
		return $this->retrieve( $oPayment, 'ids' );
	}

	/**
	 * @param \EDD_Payment $oPayment
	 * @param string       $sToExtract
	 * @return array
	 */
	public function retrieve( $oPayment, $sToExtract ) {
		$aDownloads = [];
		foreach ( $oPayment->downloads as $aDownload ) {
			/** @var array $aDownload */
			switch ( $sToExtract ) {
				case 'ids':
					$mItem = $aDownload[ 'id' ];
					break;

				case 'arrays':
					$mItem = $aDownload;
					break;

				case 'objects':
				default:
					$mItem = new \EDD_Download( $aDownload[ 'id' ] );
					break;
			}
			$aDownloads[] = $mItem;
		}
		return $aDownloads;
	}
}