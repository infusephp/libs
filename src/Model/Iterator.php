<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace infuse\Model;

class Iterator implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var string
     */
    private $model;

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
     * @var array
     */
    private $where;

    /**
     * @var string
     */
    private $sort;

    /**
     * @var bool
     */
    private $loadedStart;

    /**
     * @var array
     */
    private $models = [];

    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $max;

    /**
     * @param string $model
     * @param array  $parameters
     */
    public function __construct($model, array $parameters = [])
    {
        $this->model = $model;
        $this->models = [];

        $this->start = (isset($parameters['start'])) ? $parameters['start'] : 0;
        $this->pointer = $this->start;
        $this->limit = (isset($parameters['limit'])) ? $parameters['limit'] : 100;
        $this->where = (isset($parameters['where'])) ? $parameters['where'] : [];
        $this->sort = (isset($parameters['sort'])) ? $parameters['sort'] : '';
        $this->max = -1;

        if (!empty($parameters['search'])) {
            $w = [];
            $search = addslashes($parameters['search']);
            foreach ($model::properties() as $name => $property) {
                if ($property['searchable']) {
                    $w[] = "`$name` LIKE '%$search%'";
                }
            }

            if (count($w) > 0) {
                $this->where[] = '('.implode(' OR ', $w).')';
            }
        }

        if (empty($this->sort)) {
            $idProperties = (array) $model::idProperty();
            foreach ($idProperties as $k => $property) {
                $idProperties[$k] .= ' ASC';
            }
            $this->sort = implode(',', $idProperties);
        }
    }

    /**
     * Sets the maximum number of results to return.
     *
     * @param int $n
     *
     * @return self
     */
    public function setMax($n)
    {
        $this->max = $n;

        return $this;
    }

    /**
     * Gets the maximum number of results to return.
     *
     * @return int
     */
    public function getMax()
    {
        return $this->max;
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

        return (isset($this->models[$k])) ? $this->models[$k] : null;
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

        if ($this->max >= 0) {
            return min($this->max, $this->count);
        }

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
     *
     * @return bool success
     */
    private function loadModels()
    {
        $start = $this->rangeStart($this->pointer, $this->limit);
        if ($this->loadedStart !== $start) {
            $model = $this->model;
            $query = $model::query();
            $query->where($this->where)
                  ->start($start)
                  ->limit($this->limit)
                  ->sort($this->sort);

            $this->models = $query->execute($this->model);
            $this->loadedStart = $start;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Updates the total count of models. For better performance, the
     * count is only updated on edges, i.e. when new models need to
     * be loaded.
     */
    private function updateCount()
    {
        // The count only needs to be updated when the pointer is
        // on the edges
        if ($this->pointer % $this->limit != 0 &&
            $this->pointer < $this->count) {
            return;
        }

        $model = $this->model;
        $count = $model::totalRecords($this->where);

        // Often when iterating over models they are
        // mutated DURING iteration. Thus, the model count is not
        // a fixed value like we would hope. Each call to updateCount()
        // could yield a different count. To counteract this we
        // can shift the pointer as needed each time updateCount() is called.
        if ($this->count != 0 && $count < $this->count) {
            $this->pointer -= $this->count - $count;
        }

        $this->count = $count;
    }

    private function rangeStart($n, $limit)
    {
        return floor($n / $limit) * $limit;
    }
}
