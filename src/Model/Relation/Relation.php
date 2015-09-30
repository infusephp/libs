<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace infuse\Model\Relation;

use infuse\Model;
use infuse\Model\Query;

abstract class Relation
{
    /**
     * @var string
     */
    protected $model;

    /**
     * @var string
     */
    protected $foreignKey;

    /**
     * @var string
     */
    protected $localKey;

    /**
     * @var \infuse\Model\Query
     */
    protected $query;

    /**
     * @var \infuse\Model
     */
    protected $relation;

    public function __construct($model, $foreignKey, $localKey, Model $relation)
    {
        $this->model = $model;

        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;

        $this->relation = $relation;

        $this->query = new Query($this->model);
        $this->initQuery();
    }

    /**
     * Gets the model class this relation retrieves.
     *
     * @return string
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Gets the name of the foreign key of the foreign model.
     *
     * @return string
     */
    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    /**
     * Gets the name of the foreign key of the relation model.
     *
     * @return string
     */
    public function getLocalKey()
    {
        return $this->localKey;
    }

    /**
     * Gets the relation model.
     *
     * @return \infuse\Model
     */
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * Returns the query instance for this relation.
     *
     * @return \infuse\Model\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Called to initialize the query.
     */
    abstract protected function initQuery();

    /**
     * Called to get the results of the relation query.
     *
     * @return mixed
     */
    abstract public function getResults();

    public function __call($method, $arguments)
    {
        // try calling any unkown methods on the query
        return call_user_func_array([$this->query, $method], $arguments);
    }
}
