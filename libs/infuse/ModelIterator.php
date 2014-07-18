<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.23
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
		$this->sort = (isset($parameters['sort'])) ? $parameters[ 'sort' ] : '';

		if( !empty( $parameters[ 'search' ] ) )
		{
			$w = [];
			$search = addslashes( $params[ 'search' ] );
			foreach( $properties as $name => $property )
			{
				if( !in_array( $name, $modelClass::$propertiesNotInDatabase ) )
					$w[] = "`$name` LIKE '%$search%'";
			}
			
			$this->where[] = '(' . implode( ' OR ', $w ) . ')';
		}

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
		$this->updateCount();
		return $this->count;
	}

	/**
	 * Load the next round of models
	 *
	 * @return boolean success
	 */
	private function loadModels()
	{
		$start = $this->rangeStart( $this->pointer, $this->limit );
		if( $this->loadedStart !== $start )
		{
			$model = $this->modelClass;
			$result = $model::find( [
				'where' => $this->where,
				'start' => $start,
				'limit' => $this->limit,
				'sort' => $this->sort ] );

			$this->models = $result[ 'models' ];
			$this->loadedStart = $start;

			return true;
		}
		else
			return false;
	}

	/**
	 * Updates the total count of models. For better performance, the
	 * count is only updated on edges, i.e. when new models need to 
	 * be loaded
	 */
	private function updateCount()
	{
		// The count only needs to be updated when the pointer is
		// on the edges
		if( $this->pointer % $this->limit != 0 &&
			$this->pointer < $this->count )
			return;

		$model = $this->modelClass;
		$count = $model::totalRecords( $this->where );

		// Often when iterating over models they are
		// mutated DURING iteration. Thus, the model count is not
		// a fixed value like we would hope. Each call to updateCount()
		// could yield a different count. To counteract this we
		// can shift the pointer as needed each time updateCount() is called.
		if( $this->count != 0 && $count < $this->count )
			$this->pointer -= $this->count - $count;

		$this->count = $count;
	}

	private function rangeStart( $n, $limit )
	{
		return floor( $n / $limit ) * $limit;
	}
}