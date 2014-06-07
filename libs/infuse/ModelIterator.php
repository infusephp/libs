<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21.1
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class ModelIterator implements \Iterator
{
	private $modelClass;
	private $start;
	private $pointer;
	private $limit;
	private $where;
	private $search;
	private $sort;
	private $loadedStart = false;
	private $models = [];
	private $count = false;

	function __construct( $modelClass, array $parameters )
	{
		$this->modelClass = $modelClass;
		$this->parameters = $parameters;

		$this->start = (isset($parameters['start'])) ? $parameters[ 'start' ] : 0;
		$this->pointer = $this->start;
		$this->limit = (isset($parameters['limit'])) ? $parameters[ 'limit' ] : 100;
		$this->where = (isset($parameters['where'])) ? $parameters[ 'where' ] : [];
		$this->search = (isset($parameters['search'])) ? $parameters[ 'search' ] : '';
		$this->sort = (isset($parameters['sort'])) ? $parameters[ 'sort' ] : '';

		if( empty( $this->sort ) )
		{
			$idProperties = (array)$modelClass::idProperty();
			foreach( $idProperties as $k => $property )
				$idProperties[ $k ] .= ' ASC';
			$this->sort = implode( ',', $idProperties );
		}
	}

	/**
	 * Rewind the Iterator to the first element
	 */
	function rewind()
	{
		$this->pointer = $this->start;
		$this->loadedStart = false;
		$this->models = [];
		$this->count = false;
	}

	/**
	 * Returns the current element
	 *
	 * @return mixed
	 */
	function current()
	{
		if( $this->pointer >= $this->count() )
			return null;

		$this->loadModels();
		$k = $this->pointer % $this->limit;

		return (isset($this->models[$k])) ? $this->models[ $k ] : null;
	}

	/**
	 * Return the key of the current element
	 *
	 * @return int
	 */
	function key()
	{
		return $this->pointer;
	}

	/**
	 * Move forward to the next element
	 */
	function next()
	{
		$this->pointer++;
	}

	/**
	 * Checks if current position is valid
	 *
	 * @return boolean
	 */
	function valid()
	{
		return $this->pointer < $this->count();
	}

	/**
	 * Get total number of models matching query
	 *
	 * @return int
	 */
	private function count()
	{
		if( $this->count === false )
			$this->loadModels();

		return $this->count;
	}

	/**
	 * Load the next round of models
	 */
	private function loadModels()
	{
		$expectedStart = $this->rangeStart( $this->pointer, $this->limit );
		if( $this->loadedStart !== $expectedStart )
		{
			$model = $this->modelClass;
			$result = $model::find( [
				'start' => $expectedStart,
				'limit' => $this->limit,
				'where' => $this->where,
				'search' => $this->search,
				'sort' => $this->sort ] );

			$this->count = $result[ 'count' ];
			$this->models = $result[ 'models' ];
			$this->loadedStart = $expectedStart;
		}

		return $this->models;
	}

	private function rangeStart( $n, $limit )
	{
		return floor( $n / $limit ) * $limit;
	}
}