<?php

namespace infuse\Model\Driver;

use infuse\Model;
use JAQB\QueryBuilder;
use PDOStatement;
use Pimple\Container;

class DatabaseDriver implements DriverInterface
{
    /**
     * @var QueryBuilder
     */
    private $db;

    /**
     * @var Container
     */
    private $app;

    /**
     * @param QueryBuilder $db
     * @param Container    $app
     */
    public function __construct(QueryBuilder $db, Container $app = null)
    {
        $this->db = $db;
        $this->app = $app;
    }

    public function getDatabase()
    {
        return $this->db;
    }

    public function serializeValue(array $property, $value)
    {
        // encode JSON
        if ($property['type'] == Model::TYPE_JSON && !is_string($value)) {
            $value = json_encode($value);
        }

        return $value;
    }

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

    public function createModel(Model $model, array $parameters)
    {
        try {
            return $this->db->insert($parameters)
                ->into($model::tablename())
                ->execute();
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function updateModel(Model $model, array $parameters)
    {
        if (count($parameters) == 0) {
            return true;
        }

        try {
            return $this->db->update($model::tablename())
                ->values($parameters)
                ->where($model->id(true))
                ->execute() instanceof PDOStatement;
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    public function deleteModel(Model $model)
    {
        try {
            return $this->db->delete($model::tablename())
                ->where($model->id(true))
                ->execute();
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }
}
