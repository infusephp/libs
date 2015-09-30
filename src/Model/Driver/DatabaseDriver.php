<?php

namespace infuse\Model\Driver;

use ICanBoogie\Inflector;
use infuse\Model;
use infuse\Model\Query;
use PDOException;
use PDOStatement;
use Pimple\Container;

class DatabaseDriver implements DriverInterface
{
    /**
     * @var \Pimple\Container
     */
    private $app;

    /**
     * @param \Pimple\Container $app
     */
    public function __construct(Container $app = null)
    {
        $this->app = $app;
    }

    public function createModel(Model $model, array $parameters)
    {
        $values = $this->serialize($parameters);

        try {
            return $this->app['db']->insert($values)
                ->into($this->getTablename($model))
                ->execute() instanceof PDOStatement;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function getCreatedID(Model $model, $propertyName)
    {
        try {
            $id = $this->app['db']->getPDO()->lastInsertId();

            return $this->unserializeValue($model::properties($propertyName), $id);
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }
    }

    public function loadModel(Model $model)
    {
        try {
            $row = $this->app['db']->select('*')
                ->from($this->getTablename($model))
                ->where($model->id(true))
                ->one();

            if (is_array($row)) {
                $row = $this->unserialize($row, $model::properties());
            }

            return $row;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return [];
    }

    public function updateModel(Model $model, array $parameters)
    {
        if (count($parameters) == 0) {
            return true;
        }

        $values = $this->serialize($parameters);

        try {
            return $this->app['db']->update($this->getTablename($model))
                ->values($values)
                ->where($model->id(true))
                ->execute() instanceof PDOStatement;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function deleteModel(Model $model)
    {
        try {
            return $this->app['db']->delete($this->getTablename($model))
                ->where($model->id(true))
                ->execute() instanceof PDOStatement;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function queryModels(Query $query)
    {
        $model = $query->getModel();

        try {
            $data = $this->app['db']->select('*')
                ->from($this->getTablename($model))
                ->where($query->getWhere())
                ->limit($query->getLimit(), $query->getStart())
                ->orderBy($query->getSort())
                ->all();

            $properties = $model::properties();
            foreach ($data as &$row) {
                $row = $this->unserialize($row, $properties);
            }

            return $data;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return [];
    }

    public function totalRecords(Query $query)
    {
        try {
            return (int) $this->app['db']->select('count(*)')
                ->from($this->getTablename($query->getModel()))
                ->where($query->getWhere())
                ->scalar();
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return 0;
    }

    /**
     * Generates the tablename for the model.
     *
     * @param string|Model $model
     *
     * @return string
     */
    public function getTablename($model)
    {
        $inflector = Inflector::get();

        return $inflector->camelize($inflector->pluralize($model::modelName()));
    }

    /**
     * Marshals a value to storage.
     *
     * @param mixed $value
     *
     * @return mixed serialized value
     */
    public function serializeValue($value)
    {
        // encode arrays/objects as JSON
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }

        return $value;
    }

    /**
     * Marshals a value for a given property from storage.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed unserialized value
     */
    public function unserializeValue(array $property, $value)
    {
        // handle empty strings as null
        if ($property['null'] && $value == '') {
            return;
        }

        $type = $property['type'];

        // handle boolean values, they might be strings
        if ($type == Model::TYPE_BOOLEAN && is_string($value)) {
            return ($value == '1') ? true : false;
        }

        // cast numbers as....numbers
        if ($type == Model::TYPE_NUMBER) {
            return $value + 0;
        }

        // cast dates as numbers also
        if ($type == Model::TYPE_DATE) {
            if (!is_numeric($value)) {
                return strtotime($value);
            } else {
                return $value + 0;
            }
        }

        // decode JSON into an array
        if ($type == Model::TYPE_JSON && is_string($value)) {
            return (array) json_decode($value, true);
        }

        return $value;
    }

    /**
     * Serializes an array of values.
     *
     * @param array $values
     *
     * @return array
     */
    private function serialize(array $values)
    {
        foreach ($values as &$value) {
            $value = $this->serializeValue($value);
        }

        return $values;
    }

    /**
     * Unserializes an array of values.
     *
     * @param array $values
     * @param array $properties model properties
     *
     * @return array
     */
    private function unserialize(array $values, array $properties)
    {
        foreach ($values as $k => &$value) {
            if (isset($properties[$k])) {
                $value = $this->unserializeValue($properties[$k], $value);
            }
        }

        return $values;
    }
}
