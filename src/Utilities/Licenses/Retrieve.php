<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Licenses;

use FernleafSystems\Integrations\Edd\Consumers\EddCustomerConsumer;
use FernleafSystems\Integrations\Edd\Consumers\EddDownloadConsumer;

class Retrieve {

	use EddCustomerConsumer;
	use EddDownloadConsumer;

	/**
	 * @var array
	 */
	private array $lastResults = [];

	private array $statuses = [];

	public function getLastResults() :array {
		return $this->lastResults;
	}

	public function reset() :self {
		$this->lastResults = [];
		return $this;
	}

	/**
	 * @return \EDD_SL_License[]
	 */
	public function retrieve( array $extraParams = [] ) :array {
		return array_map(
			fn( $licID ) => new \EDD_SL_License( $licID ),
			$this->runQuery( $extraParams )
		);
	}

	/**
	 * @return int[] - license IDs
	 */
	public function runQuery( array $extraParams = [] ) :array {

		if ( !empty( $extraParams[ 'site_name' ] ) ) {
			/**
			 * We can't just take the License IDs as they're provided from this result.
			 * The reason being that this query doesn't take into consideration
			 * the status of the license to which this site name is attached. That
			 * license could be expired, disabled etc. So we grab the IDs and send
			 * them along.
			 */
			$possibleIds = $this->runQueryForSite( $extraParams[ 'site_name' ] );
			if ( !empty( $possibleIds ) ) {
				$extraParams[ 'id' ] = $possibleIds;
			}
			unset( $extraParams[ 'site_name' ] );
		}

		$params = array_merge(
			[
				'status' => $this->getStatusesForQuery(),
				'fields' => 'ids',
			],
			$extraParams
		);

		if ( !empty( $this->getEddDownload() ) ) {
			$params[ 'download_id' ] = $this->getEddDownload()->get_ID();
		}
		if ( !empty( $this->getEddCustomer() ) ) {
			$params[ 'user_id' ] = $this->getEddCustomer()->user_id;
		}

		return edd_software_licensing()->licenses_db->get_licenses( $params );
	}

	/**
	 * @return int[] - license record IDs
	 */
	public function runQueryForSite( string $siteName ) :array {
		$licIDs = ( new \EDD_SL_Activations_DB() )->get_activations( [
			'fields'    => 'license_id',
			'site_name' => trailingslashit( $siteName ),
			'activated' => 1,
		] );
		return is_array( $licIDs ) ? $licIDs : [];
	}

	protected function getStatusesForQuery() :array {
		return array_keys( array_filter( $this->getLicenseStatuses() ) );
	}

	public function getLicenseStatuses() :array {
		return array_merge( [
			'active'   => true,
			'inactive' => true,
			'disabled' => false,
			'expired'  => true,
		], $this->statuses );
	}

	public function setIncludeDisabled( bool $include = true ) :self {
		return $this->setIncludeStatus( 'disabled', $include );
	}

	public function setIncludeExpired( bool $include = true ) :self {
		return $this->setIncludeStatus( 'expired', $include );
	}

	public function setIncludeStatus( string $status, bool $include ) :self {
		$statuses = $this->getLicenseStatuses();
		$statuses[ $status ] = $include;
		return $this->setLicenseStatuses( $statuses );
	}

	protected function setLicenseStatuses( array $licenseStatuses ) :self {
		$this->statuses = $licenseStatuses;
		return $this;
	}

	/**
	 * @param array $results
	 * @return $this
	 */
	protected function setLastResults( array $results ) {
		$this->lastResults = $results;
		return $this;
	}
}