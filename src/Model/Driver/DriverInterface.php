<?php

namespace infuse\Model\Driver;

use infuse\Model;
use infuse\Model\Query;

interface DriverInterface
{
    /**
     * Creates a model.
     *
     * @param Model $model
     * @param array $parameters
     *
     * @return mixed result
     */
    public function createModel(Model $model, array $parameters);

    /**
     * Loads a model.
     *
     * @param Model $model
     *
     * @return array
     */
    public function loadModel(Model $model);

    /**
     * Updates a model.
     *
     * @param Model $model
     * @param array $parameters
     *
     * @return bool
     */
    public function updateModel(Model $model, array $parameters);

    /**
     * Deletes a model.
     *
     * @param Model $model
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
     * @param string $modelClass
     * @param Query  $query
     *
     * @return array raw data from storage
     */
    public function queryModels($modelClass, Query $query);

    /**
     * Marshals a value for a given property to storage, and
     * checks the validity of a value.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed serialized value
     */
    public function serializeValue(array $property, $value);

    /**
     * Marshals a value for a given property from storage.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed unserialized value
     */
    public function unserializeValue(array $property, $value);
}
