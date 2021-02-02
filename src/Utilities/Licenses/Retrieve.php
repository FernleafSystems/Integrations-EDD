<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;

/**
 * Class Retrieve
 * @package FernleafSystems\Integrations\Edd\Utilities\Licenses
 */
class Retrieve {

	use EddCustomerConsumer;
	use EddDownloadConsumer;

	/**
	 * @var array
	 */
	private $aLastResults;

	/**
	 * @var array
	 */
	private $aStatuses;

	/**
	 * @return array
	 */
	public function getLastResults() {
		return is_array( $this->aLastResults ) ? $this->aLastResults : [];
	}

	/**
	 * @return $this
	 */
	public function reset() {
		$this->aLastResults = [];
		return $this;
	}

	/**
	 * @param array $aExtraParams
	 * @return \EDD_SL_License[]
	 */
	public function retrieve( $aExtraParams = [] ) {
		return array_map(
			function ( $nLicenseId ) {
				return new \EDD_SL_License( $nLicenseId );
			},
			$this->runQuery( $aExtraParams )
		);
	}

	/**
	 * @param array $aExtraParams
	 * @return int[] - license IDs
	 */
	public function runQuery( $aExtraParams = [] ) {

		if ( !empty( $aExtraParams[ 'site_name' ] ) ) {
			/**
			 * We can't just take the License IDs as they're provided from this result.
			 * The reason being that this query doesn't take into consideration
			 * the status of the license to which this site name is attached. That
			 * license could be expired, disabled etc. So we grab the IDs and send
			 * them along.
			 */
			$aPossibleIds = $this->runQueryForSite( $aExtraParams[ 'site_name' ] );
			if ( !empty( $aPossibleIds ) ) {
				$aExtraParams[ 'id' ] = $aPossibleIds;
			}
			unset( $aExtraParams[ 'site_name' ] );
		}

		$aParams = array_merge(
			[
				'status' => $this->getStatusesForQuery(),
				'fields' => 'ids',
			],
			$aExtraParams
		);

		if ( !empty( $this->getEddDownload() ) ) {
			$aParams[ 'download_id' ] = $this->getEddDownload()->get_ID();
		}
		if ( !empty( $this->getEddCustomer() ) ) {
			$aParams[ 'user_id' ] = $this->getEddCustomer()->user_id;
		}

		$aIds = edd_software_licensing()->licenses_db->get_licenses( $aParams );
		return is_array( $aIds ) ? $aIds : [];
	}

	/**
	 * @param string $sSiteName
	 * @return int[] - license record IDs
	 */
	public function runQueryForSite( $sSiteName ) {
		$aIds = ( new \EDD_SL_Activations_DB() )->get_activations(
			[
				'fields'    => 'license_id',
				'site_name' => trailingslashit( $sSiteName ),
				'activated' => 1,
			]
		);
		return $aIds;
	}

	/**
	 * @return array
	 */
	protected function getStatusesForQuery() {
		return array_keys( array_filter( $this->getLicenseStatuses() ) );
	}

	/**
	 * @return array
	 */
	public function getLicenseStatuses() {
		$aDefault = [
			'active'   => true,
			'inactive' => true,
			'disabled' => false,
			'expired'  => true,
		];
		return is_array( $this->aStatuses ) ? array_merge( $aDefault, $this->aStatuses ) : $aDefault;
	}

	/**
	 * @param bool $bInclude
	 * @return $this
	 */
	public function setIncludeDisabled( $bInclude = true ) {
		return $this->setIncludeStatus( 'disabled', $bInclude );
	}

	/**
	 * @param bool $bInclude
	 * @return $this
	 */
	public function setIncludeExpired( $bInclude = true ) {
		return $this->setIncludeStatus( 'expired', $bInclude );
	}

	/**
	 * @param string $sStatus
	 * @param bool   $bInclude
	 * @return $this
	 */
	public function setIncludeStatus( $sStatus, $bInclude ) {
		$aSt = $this->getLicenseStatuses();
		$aSt[ $sStatus ] = (bool)$bInclude;
		return $this->setLicenseStatuses( $aSt );
	}

	/**
	 * @param array $aLicenseStatuses
	 * @return $this
	 */
	protected function setLicenseStatuses( $aLicenseStatuses ) {
		$this->aStatuses = $aLicenseStatuses;
		return $this;
	}

	/**
	 * @param array $aRes
	 * @return $this
	 */
	protected function setLastResults( $aRes ) {
		$this->aLastResults = $aRes;
		return $this;
	}
}