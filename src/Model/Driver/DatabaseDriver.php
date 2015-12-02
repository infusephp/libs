<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse\Model\Driver;

use ICanBoogie\Inflector;
use Infuse\Model;
use Infuse\Model\Query;
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
        $tablename = $this->getTablename($model);

        try {
            return $this->app['db']->insert($values)
                ->into($tablename)
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

            return $this->unserializeValue($model::getProperty($propertyName), $id);
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }
    }

    public function loadModel(Model $model)
    {
        $tablename = $this->getTablename($model);

        try {
            $row = $this->app['db']->select('*')
                ->from($tablename)
                ->where($model->ids())
                ->one();

            if (is_array($row)) {
                $row = $this->unserialize($row, $model::getProperties());
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
        $tablename = $this->getTablename($model);

        try {
            return $this->app['db']->update($tablename)
                ->values($values)
                ->where($model->ids())
                ->execute() instanceof PDOStatement;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function deleteModel(Model $model)
    {
        $tablename = $this->getTablename($model);

        try {
            return $this->app['db']->delete($tablename)
                ->where($model->ids())
                ->execute() instanceof PDOStatement;
        } catch (PDOException $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function queryModels(Query $query)
    {
        $model = $query->getModel();
        $tablename = $this->getTablename($model);

        // build a DB query from the model query
        $dbQuery = $this->app['db']
            ->select($this->prefixSelect('*', $tablename))
            ->from($tablename)
            ->where($this->prefixWhere($query->getWhere(), $tablename))
            ->limit($query->getLimit(), $query->getStart())
            ->orderBy($this->prefixSort($query->getSort(), $tablename));

        try {
            $data = $dbQuery->all();

            $properties = $model::getProperties();
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
        $model = $query->getModel();
        $tablename = $this->getTablename($model);

        try {
            return (int) $this->app['db']->select('count(*)')
                ->from($tablename)
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
        if ($type == Model::TYPE_ARRAY && is_string($value)) {
            return (array) json_decode($value, true);
        }

        // decode JSON into an object
        if ($type == Model::TYPE_OBJECT && is_string($value)) {
            return (object) json_decode($value);
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

    /**
     * Returns a prefixed select statement.
     *
     * @param string $columns
     * @param string $tablename
     *
     * @return string
     */
    private function prefixSelect($columns, $tablename)
    {
        $prefixed = [];
        foreach (explode(',', $columns) as $column) {
            $prefixed[] = $this->prefixColumn($column, $tablename);
        }

        return implode(',', $prefixed);
    }

    /**
     * Returns a prefixed where statement.
     *
     * @param string $columns
     * @param string $tablename
     *
     * @return string
     */
    private function prefixWhere(array $where, $tablename)
    {
        $return = [];
        foreach ($where as $key => $condition) {
            // handles $where[property] = value
            if (!is_numeric($key)) {
                $return[$this->prefixColumn($key, $tablename)] = $condition;
            // handles $where[] = [property, value, '=']
            } elseif (is_array($condition)) {
                $condition[0] = $this->prefixColumn($condition[0], $tablename);
                $return[] = $condition;
            // handles raw SQL - do nothing
            } else {
                $return[] = $condition;
            }
        }

        return $return;
    }

    /**
     * Returns a prefixed sort statement.
     *
     * @param string $columns
     * @param string $tablename
     *
     * @return string
     */
    private function prefixSort(array $sort, $tablename)
    {
        foreach ($sort as &$condition) {
            $condition[0] = $this->prefixColumn($condition[0], $tablename);
        }

        return $sort;
    }

    /**
     * Prefix columns with tablename that contains only
     * alphanumeric/underscores/*.
     *
     * @param string $column
     * @param string $tablename
     *
     * @return string prefixed column
     */
    private function prefixColumn($column, $tablename)
    {
        if ($column === '*' || preg_match('/^[a-z0-9_]+$/i', $column)) {
            return "$tablename.$column";
        }

        return $column;
    }
}
