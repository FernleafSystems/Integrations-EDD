<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Base;

/**
 * Class EddEntityIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Base
 */
abstract class EddEntityIterator implements \Countable, \Iterator {

	const DEFAULT_PER_PAGE = 50;
	const PAGINATION_TYPE = 'page';

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

	/**
	 * EddEntityIterator constructor.
	 * @param bool $bStart - set to false if you need to setup custom query filters and manually start.
	 */
	public function __construct( $bStart = true ) {
		if ( $bStart ) {
			$this->start();
		}
	}

	/**
	 * Starts the iteration.
	 * @return $this
	 */
	public function start() {
		$this->nCursor = -1;
		$this->nPage = $this->getStartPage();
		$this->runQuery();
		return $this;
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
		$aDefaults = [
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => static::DEFAULT_PER_PAGE,
		];

		switch ( static::PAGINATION_TYPE ) {
			case 'offset':
				$aDefaults[ 'offset' ] = $this->getPage()*$this->getPerPage();
				break;

			case 'page':
			default:
				$aDefaults[ 'page' ] = $this->getPage();
				break;
		}

		return $aDefaults;
	}

	/**
	 * @return array
	 */
	protected function getFinalQueryFilters() {
		return array_merge( $this->getDefaultQueryFilters(), $this->getCustomQueryFilters() );
	}

	/**
	 * @return int
	 */
	protected function getStartPage() {
		switch ( static::PAGINATION_TYPE ) {

			case 'offset':
				$nStart = 0;
				break;

			case 'page':
			default:
				$nStart = 1;
				break;
		}
		return $nStart;
	}

	/**
	 * @param array $aQuery
	 * @return $this
	 */
	public function setCustomQueryFilters( $aQuery ) {
		if ( is_array( $aQuery ) ) {
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
		return max( $this->getStartPage(), (int)$this->nPage );
	}

	/**
	 * @return int
	 */
	public function getPerPage() {
		$aQ = $this->getCustomQueryFilters();
		return isset( $aQ[ 'number' ] ) ? $aQ[ 'number' ] : static::DEFAULT_PER_PAGE;
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
		$this->aCurrentPage = array_values( $aPageResults );
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