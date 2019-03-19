<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Customers;

/**
 * Class CustomerIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Customers
 */
class CustomerIterator implements \Countable, \Iterator {

	const PER_PAGE = 50;

	/**
	 * @var int
	 */
	private $nCount;

	/**
	 * @var int
	 */
	private $nCursor;

	/**
	 * @var int
	 */
	private $nPage;

	/**
	 * @var \EDD_Customer[]
	 */
	private $aCurrentPage;

	/**
	 * @var array
	 */
	private $aQueryFilters;

	public function __construct() {
	}

	private function start() {
		$this->nCursor = -1;
		$this->nPage = 0;
		$this->runQuery();
	}

	/**
	 * @return array
	 */
	public function getQueryFilters() {
		return is_array( $this->aQueryFilters ) ? $this->aQueryFilters : [];
	}

	/**
	 * @param array $aQuery
	 * @return $this
	 */
	public function setQueryFilters( $aQuery ) {
		if ( is_array( $this->aQueryFilters ) ) {
			if ( isset( $aQuery[ 'number' ] ) && (int)$aQuery[ 'number' ] < 0 ) {
				unset( $aQuery[ 'number' ] );
			}
			$this->aQueryFilters = $aQuery;
		}
		return $this;
	}

	/**
	 * @return int
	 */
	public function getPerPage() {
		$aQ = $this->getQueryFilters();
		return isset( $aQ[ 'number' ] ) ? $aQ[ 'number' ] : static::PER_PAGE;
	}

	/**
	 */
	private function runQuery() {
		$aQueryOptions = array_merge(
			[
				'orderby' => 'id',
				'order'   => 'ASC',
				'number'  => static::PER_PAGE,
				'offset'  => $this->nPage*static::PER_PAGE,
			],
			$this->getQueryFilters()
		);

		$this->aCurrentPage = array_values( array_map(
			function ( $oCustomerStdClass ) {
				return new \EDD_Customer( $oCustomerStdClass->id );
			},
			( new \EDD_Customer_Query() )->query( $aQueryOptions )
		) );
	}

	/**
	 * @return \EDD_Customer|null
	 */
	public function current() {
		return $this->aCurrentPage[ ( $this->nCursor%$this->getPerPage() ) ];
	}

	/**
	 */
	public function next() {
		$this->nCursor++;
		if ( ( $this->nCursor%$this->getPerPage() ) == 0 ) {
			$this->nPage++;
			$this->runQuery();
		}
	}

	/**
	 * @return int
	 */
	public function key() {
		return $this->nCursor;
	}

	/**
	 * @return bool
	 */
	public function valid() {
		return ( $this->nCursor < $this->count() );
	}

	/**
	 *
	 */
	public function rewind() {
		if ( $this->nCursor >= 0 ) {
			$this->start();
		}
		$this->next();
	}

	/**
	 * @return int
	 */
	public function count() {
		if ( !isset( $this->nCount ) ) {
			$this->nCount = ( new \EDD_Customer_Query() )->query( [ 'count' => true ] );
		}
		return $this->nCount;
	}
}