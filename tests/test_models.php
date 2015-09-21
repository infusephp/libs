<?php

use infuse\Model;

class TestModel extends Model
{
    public static $properties = [
        'relation' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'TestModel2',
            'null' => true,
        ],
        'answer' => [
            'type' => Model::TYPE_STRING,
        ],
        'filter' => [
            'filter' => 'uppercase',
            'hidden' => true,
        ],
    ];
    public $preDelete;
    public $postDelete;

    protected static function propertiesHook()
    {
        $properties = parent::propertiesHook();

        $properties[ 'test_hook' ] = [
            'type' => Model::TYPE_STRING,
            'null' => true, ];

        return $properties;
    }

    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function preCreateHook()
    {
        $this->preCreate = true;

        return true;
    }

    public function postCreateHook()
    {
        $this->postCreate = true;
    }

    public function preSetHook()
    {
        $this->preSet = true;

        return true;
    }

    public function postSetHook()
    {
        $this->postSet = true;
    }

    public function preDeleteHook()
    {
        $this->preDelete = true;

        return true;
    }

    public function postDeleteHook()
    {
        $this->postDelete = true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if (!isset($exclude[ 'toArray' ])) {
            $result[ 'toArray' ] = true;
        }
    }

    protected function uppercase($value)
    {
        return strtoupper($value);
    }
}

function validate()
{
    return false;
};
class TestModel2 extends Model
{
    public static $properties = [
        'id' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'id2' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'default' => [
            'default' => 'some default value',
        ],
        'validate' => [
            'validate' => 'email',
            'null' => true,
        ],
        'validate2' => [
            'validate' => 'validate',
            'hidden' => true,
            'null' => true,
        ],
        'unique' => [
            'unique' => true,
        ],
        'required' => [
            'type' => Model::TYPE_NUMBER,
            'required' => true,
        ],
        'hidden' => [
            'type' => Model::TYPE_BOOLEAN,
            'default' => false,
            'hidden' => true,
        ],
        'person' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'Person',
            'default' => 20,
            'hidden' => true,
        ],
        'json' => [
            'type' => Model::TYPE_JSON,
            'default' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false,
            ],
            'hidden' => true,
        ],
        'mutable_create_only' => [
            'mutable' => Model::MUTABLE_CREATE_ONLY,
            'hidden' => true,
        ],
    ];

    public static $autoTimestamps;

    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if (isset($include['toArrayHook'])) {
            $result['toArrayHook'] = true;
        }
    }

    public static function idProperty()
    {
        return ['id', 'id2'];
    }
}

class TestModelNoPermission extends Model
{
    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class TestModelHookFail extends Model
{
    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public function preCreateHook()
    {
        return false;
    }

    public function preSetHook()
    {
        return false;
    }

    public function preDeleteHook()
    {
        return false;
    }
}

class Person extends Model
{
    public static $properties = [
        'id' => [
            'type' => Model::TYPE_STRING,
        ],
        'name' => [
            'type' => Model::TYPE_STRING,
            'default' => 'Jared',
        ],
        'email' => [
            'type' => Model::TYPE_STRING,
        ],
    ];

    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class IteratorTestModel extends Model
{
    public static $properties = [
        'id' => [
            'type' => 'number', ],
        'id2' => [
            'type' => 'number', ],
        'name' => [
            'type' => 'string',
            'searchable' => true, ],
    ];

    public static function idProperty()
    {
        return ['id', 'id2'];
    }
    protected function hasPermission($permission, Model $requester)
    {
        return true;
    }

    public static function totalRecords(array $where = [])
    {
        return 123;
    }

    public static function find(array $params = [])
    {
        if ($params[ 'sort' ] != 'id ASC,id2 ASC') {
            return ['models' => [], 'count' => 0];
        }

        $range = range($params[ 'start' ], $params[ 'start' ] + $params[ 'limit' ] - 1);
        $models = [];
        $modelClass = get_called_class();

        foreach ($range as $k) {
            $models[] = new $modelClass($k);
        }

        return [
            'models' => $models,
            'count' => self::totalRecords(), ];
    }
}
