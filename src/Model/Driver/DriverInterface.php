<?php

namespace infuse\Model\Driver;

use infuse\Model;
use infuse\Model\Query;

interface DriverInterface
{
    /**
     * Creates a model.
     *
     * @param \infuse\Model $model
     * @param array         $parameters
     *
     * @return mixed result
     */
    public function createModel(Model $model, array $parameters);

    /**
     * Gets the last inserted ID. Used for drivers that generate
     * IDs for models after creation.
     *
     * @param \infuse\Model $model
     * @param string        $propertyName
     *
     * @return mixed
     */
    public function getCreatedID(Model $model, $propertyName);

    /**
     * Loads a model.
     *
     * @param \infuse\Model $model
     *
     * @return array
     */
    public function loadModel(Model $model);

    /**
     * Updates a model.
     *
     * @param \infuse\Model $model
     * @param array         $parameters
     *
     * @return bool
     */
    public function updateModel(Model $model, array $parameters);

    /**
     * Deletes a model.
     *
     * @param \infuse\Model $model
     *
     * @return bool
     */
    public function deleteModel(Model $model);

    /**
     * Gets the toal number of records matching an optional criteria.
     *
     * @param string $modelClass
     * @param array  $criteria   parameters to match
     *
     * @return int total
     */
    public function totalRecords($modelClass, array $criteria);

    /**
     * Performs a query to find models of the given type.
     *
     * @param string              $modelClass
     * @param \infuse\Model\Query $query
     *
     * @return array raw data from storage
     */
    public function queryModels($modelClass, Query $query);
}
