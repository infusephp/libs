<?php

namespace infuse\Model;

class Query
{
    const DEFAULT_LIMIT = 100;
    const MAX_LIMIT = 1000;

    /**
     * @var string
     */
    private $model;

    /**
     * @var array
     */
    private $where;

    /**
     * @var int
     */
    private $limit;

    /**
     * @var int
     */
    private $start;

    /**
     * @var array
     */
    private $sort;

    /**
     * @param string $model model class
     */
    public function __construct($model = false)
    {
        $this->model = $model;
        $this->where = [];
        $this->start = 0;
        $this->limit = self::DEFAULT_LIMIT;
        $this->sort = [];
    }

    /**
     * Gets the model class associated with this query.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Sets the limit for this query.
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        $this->limit = min($limit, self::MAX_LIMIT);

        return $this;
    }

    /**
     * Gets the limit for this query.
     *
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Sets the start offset.
     *
     * @param int $start
     *
     * @return self
     */
    public function start($start)
    {
        $this->start = max($start, 0);

        return $this;
    }

    /**
     * Gets the start offset.
     *
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets the sort pattern for the query.
     *
     * @param array|string $sort
     *
     * @return self
     */
    public function sort($sort)
    {
        $columns = explode(',', $sort);

        $sortParams = [];
        foreach ($columns as $column) {
            $c = explode(' ', trim($column));

            if (count($c) != 2) {
                continue;
            }

            // validate direction
            $direction = strtolower($c[1]);
            if (!in_array($direction, ['asc', 'desc'])) {
                continue;
            }

            $sortParams[] = [$c[0], $direction];
        }

        $this->sort = $sortParams;

        return $this;
    }

    /**
     * Gets the sort parameters.
     *
     * @return array
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Sets the where parameters.
     *
     * @param array $where
     *
     * @return self
     */
    public function where(array $where)
    {
        $this->where = array_merge($this->where, $where);

        return $this;
    }

    /**
     * Gets the where parameters.
     *
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Executes the query against the model's driver.
     *
     * @param string $model optionalmodel class
     *
     * @return array results
     */
    public function execute($model = false)
    {
        $model = $model ? $model : $this->model;

        $driver = $model::getDriver();

        $models = [];
        foreach ($driver->queryModels($this) as $row) {
            // determine the model id
            $id = false;
            $idProperty = $model::idProperty();
            if (is_array($idProperty)) {
                $id = [];

                foreach ($idProperty as $f) {
                    $id[] = $row[$f];
                }
            } else {
                $id = $row[$idProperty];
            }

            // create the model and cache the loaded values
            $models[] = new $model($id, $row);
        }

        return $models;
    }

    /**
     * Executes the query against the model's driver and returns the first result.
     *
     * @param string $model model class
     *
     * @return \infuse\Model|null
     */
    public function first($model = false)
    {
        $models = $this->execute($model);

        return (count($models) > 0) ? $models[0] : null;
    }
}
