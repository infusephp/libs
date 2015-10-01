<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse\Model;

class Iterator implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var int
     */
    private $start;

    /**
     * @var int
     */
    private $pointer;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var bool
     */
    private $loadedStart;

    /**
     * @var array
     */
    private $models;

    /**
     * @var int
     */
    private $count;

    /**
     * @param string $model
     * @param array  $parameters
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
        $this->models = [];
        $this->start = $query->getStart();
        $this->limit = $query->getLimit();
        $this->pointer = $this->start;

        $sort = $query->getSort();
        if (empty($sort)) {
            $model = $query->getModel();
            $idProperties = $model::$ids;
            foreach ($idProperties as &$property) {
                $property .= ' ASC';
            }

            $query->sort(implode(',', $idProperties));
        }
    }

    public function getQuery()
    {
        return $this->query;
    }

    //////////////////////////
    // Iterator Interface
    //////////////////////////

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        $this->pointer = $this->start;
        $this->loadedStart = false;
        $this->models = [];
        $this->count = false;
    }

    /**
     * Returns the current element.
     *
     * @return mixed
     */
    public function current()
    {
        if ($this->pointer >= $this->count()) {
            return;
        }

        $this->loadModels();
        $k = $this->pointer % $this->limit;

        if (isset($this->models[$k])) {
            return $this->models[$k];
        }

        return;
    }

    /**
     * Return the key of the current element.
     *
     * @return int
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * Move forward to the next element.
     */
    public function next()
    {
        ++$this->pointer;
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->pointer < $this->count();
    }

    //////////////////////////
    // Countable Interface
    //////////////////////////

    /**
     * Get total number of models matching query.
     *
     * @return int
     */
    public function count()
    {
        $this->updateCount();

        return $this->count;
    }

    //////////////////////////
    // ArrayAccess Interface
    //////////////////////////

    public function offsetExists($offset)
    {
        return is_numeric($offset) && $offset < $this->count();
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException("$offset does not exist on this Iterator");
        }

        $this->pointer = $offset;

        return $this->current();
    }

    public function offsetSet($offset, $value)
    {
        // iterators are immutable
        throw new \Exception('Cannot perform set on immutable Iterator');
    }

    public function offsetUnset($offset)
    {
        // iterators are immutable
        throw new \Exception('Cannot perform unset on immutable Iterator');
    }

    //////////////////////////
    // Private Methods
    //////////////////////////

    /**
     * Load the next round of models.
     */
    private function loadModels()
    {
        $start = $this->rangeStart($this->pointer, $this->limit);
        if ($this->loadedStart !== $start) {
            $this->query->start($start);

            $this->models = $this->query->execute();
            $this->loadedStart = $start;
        }
    }

    /**
     * Updates the total count of models. For better performance
     * the count is only updated on edges, which is when new models
     * need to be loaded.
     */
    private function updateCount()
    {
        // The count only needs to be updated when the pointer is
        // on the edges
        if ($this->pointer % $this->limit != 0 &&
            $this->pointer < $this->count) {
            return;
        }

        $model = $this->query->getModel();
        $newCount = $model::totalRecords($this->query->getWhere());

        // It's possible when iterating over models that something
        // is modified or deleted that causes the model count
        // to decrease. If the count has decreased then we
        // shift the pointer to prevent overflow.
        // This calculation is based on the assumption that
        // the first N (count - count') models are deleted.
        if ($this->count != 0 && $newCount < $this->count) {
            $this->pointer = max(0, $this->pointer - ($this->count - $newCount));
        }

        // If the count has increased then the pointer is still
        // valid. Update the count to include the extra models.
        $this->count = $newCount;
    }

    /**
     * Generates the starting page given a pointer and limit.
     *
     * @param int $pointer
     * @param int $limit
     */
    private function rangeStart($pointer, $limit)
    {
        return floor($pointer / $limit) * $limit;
    }
}
