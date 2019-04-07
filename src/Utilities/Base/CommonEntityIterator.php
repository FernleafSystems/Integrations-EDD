<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Base;

use Elliotchance\Iterator\AbstractPagedIterator;

/**
 * Class CommonEntityIterator
 * @package FernleafSystems\Integrations\Edd\Utilities\Base
 */
abstract class CommonEntityIterator extends AbstractPagedIterator {

	const PAGE_LIMIT = 50;
	const PAGINATION_TYPE = 'offset';

	/**
	 * @var int
	 */
	private $nTotalSize;

	/**
	 * @var array
	 */
	private $aQueryFilters;

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
			'number'  => $this->getPageSize(),
		];
	}

	/**
	 * @return array
	 */
	protected function getFinalQueryFilters() {
		return array_merge( $this->getDefaultQueryFilters(), $this->getCustomQueryFilters() );
	}

	/**
	 * @param int $nPage - always starts at 0
	 * @return array
	 */
	public function getPage( $nPage ) {

		switch ( static::PAGINATION_TYPE ) {
			case 'offset':
				$this->setCustomQueryFilter( 'offset', $nPage*$this->getPageSize() );
				break;

			case 'page':
			default:
				$this->setCustomQueryFilter( 'page', $nPage + 1 );
				break;
		}

		return $this->runQuery();
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
	 * @return integer
	 */
	public function getPageSize() {
		return static::PAGE_LIMIT;
	}

	/**
	 * @return int
	 */
	public function getTotalSize() {
		if ( !isset( $this->nTotalSize ) ) {
			$this->nTotalSize = $this->runQueryCount();
		}
		return $this->nTotalSize;
	}

	/**
	 * @return array
	 */
	abstract protected function runQuery();

	/**
	 * @return int
	 */
	abstract protected function runQueryCount();

	/**
	 */
	public function rewind() {
		parent::rewind();
		unset( $this->nTotalSize );
	}
}