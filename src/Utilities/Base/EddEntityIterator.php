<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Base;

/**
 * Class EddEntityIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Base
 */
abstract class EddEntityIterator implements \Countable, \Iterator {

	const PER_PAGE = 50;
	const START_PAGE = 1;

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
	protected $nPage;

	/**
	 * @var array
	 */
	private $aCurrentPage;

	/**
	 * @var array
	 */
	private $aQueryFilters;

	public function __construct() {
		$this->start();
	}

	private function start() {
		$this->nCursor = -1;
		$this->nPage = static::START_PAGE;
		$this->runQuery();
	}

	/**
	 * @return array
	 */
	public function getCustomQueryFilters() {
		return is_array( $this->aQueryFilters ) ? $this->aQueryFilters : [];
	}

	/**
	 * @return array
	 */
	protected function getDefaultQueryFilters() {
		return [
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => static::PER_PAGE,
		];
	}

	/**
	 * @return array
	 */
	protected function getFinalQueryFilters() {
		return array_merge( $this->getDefaultQueryFilters(), $this->getCustomQueryFilters() );
	}

	/**
	 * @param array $aQuery
	 * @return $this
	 */
	public function setCustomQueryFilters( $aQuery ) {
		if ( is_array( $this->aQueryFilters ) ) {
			if ( isset( $aQuery[ 'number' ] ) && (int)$aQuery[ 'number' ] < 0 ) {
				unset( $aQuery[ 'number' ] );
			}
			$this->aQueryFilters = $aQuery;
		}
		return $this;
	}

	/**
	 * @param string $sKey
	 * @param mixed  $mValue
	 * @return $this
	 */
	public function setCustomQueryFilter( $sKey, $mValue ) {
		$aQ = $this->getCustomQueryFilters();
		$aQ[ $sKey ] = $mValue;
		return $this->setCustomQueryFilters( $aQ );
	}

	/**
	 * @return int
	 */
	protected function getPage() {
		return max( static::START_PAGE, (int)$this->nPage );
	}

	/**
	 * @return int
	 */
	public function getPerPage() {
		$aQ = $this->getCustomQueryFilters();
		return isset( $aQ[ 'number' ] ) ? $aQ[ 'number' ] : static::PER_PAGE;
	}

	/**
	 */
	abstract protected function runQuery();

	/**
	 * @return mixed|null
	 */
	public function current() {
		$aPageResults = $this->getCurrentPageResults();
		$nIndex = $this->getCurrentItemPageIndex();
		return isset( $aPageResults[ $nIndex ] ) ? $aPageResults[ $nIndex ] : null;
	}

	/**
	 * @return array
	 */
	protected function getCurrentPageResults() {
		return is_array( $this->aCurrentPage ) ? $this->aCurrentPage : [];
	}

	/**
	 * @param array $aPageResults
	 * @return $this
	 */
	protected function setCurrentPageResults( $aPageResults ) {
		$this->aCurrentPage = $aPageResults;
		return $this;
	}

	/**
	 */
	public function next() {
		$this->nCursor++;
		if ( $this->nCursor > 0 && ( $this->getCurrentItemPageIndex() == 0 ) ) {
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
		return isset( $this->getCurrentPageResults()[ $this->getCurrentItemPageIndex() ] );
	}

	/**
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
			$this->nCount = $this->runQueryCount();
		}
		return $this->nCount;
	}

	/**
	 * @return int
	 */
	private function getCurrentItemPageIndex() {
		return $this->nCursor%$this->getPerPage();
	}

	/**
	 * @return int
	 */
	abstract protected function runQueryCount();
}