<?php

namespace FernleafSystems\Integrations\Edd\Utilities\Base;

use Elliotchance\Iterator\AbstractPagedIterator;

abstract class CommonEntityIterator extends AbstractPagedIterator {

	public const PAGE_LIMIT = 50;
	public const PAGINATION_TYPE = 'offset';

	private ?int $totalSize = null;

	/**
	 * @var array
	 */
	private $queryFilters;

	public function getCustomQueryFilters() :array {
		return is_array( $this->queryFilters ) ? $this->queryFilters : [];
	}

	protected function getDefaultQueryFilters() :array {
		return [
			'orderby' => 'ID',
			'order'   => 'ASC',
			'number'  => $this->getPageSize(),
		];
	}

	protected function getFinalQueryFilters() :array {
		return array_merge( $this->getDefaultQueryFilters(), $this->getCustomQueryFilters() );
	}

	/**
	 * @param int $page - always starts at 0
	 */
	public function getPage( $page ) :array {

		switch ( static::PAGINATION_TYPE ) {
			case 'offset':
				$this->setCustomQueryFilter( 'offset', $page*$this->getPageSize() );
				break;

			case 'page':
			default:
				$this->setCustomQueryFilter( 'page', $page + 1 );
				break;
		}

		return $this->runQuery();
	}

	public function setCustomQueryFilters( array $query ) :self {
		if ( isset( $query[ 'number' ] ) && (int)$query[ 'number' ] < 0 ) {
			unset( $query[ 'number' ] );
		}
		$this->queryFilters = $query;
		return $this;
	}

	/**
	 * @param mixed $value
	 */
	public function setCustomQueryFilter( string $key, $value ) :self {
		$q = $this->getCustomQueryFilters();
		$q[ $key ] = $value;
		return $this->setCustomQueryFilters( $q );
	}

	/**
	 * @return int
	 */
	public function getPageSize() {
		return static::PAGE_LIMIT;
	}

	public function getTotalSize() {
		return is_null( $this->totalSize ) ? $this->totalSize = $this->runQueryCount() : $this->totalSize;
	}

	abstract protected function runQuery() :array;

	abstract protected function runQueryCount() :int;

	public function rewind() {
		parent::rewind();
		$this->totalSize = null;
	}
}