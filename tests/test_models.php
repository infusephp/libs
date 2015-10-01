<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
use Infuse\Model;
use Infuse\Model\ACLModel;
use Infuse\Model\Query;

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

    public static $query;

    protected function initialize()
    {
        self::$properties['test_hook'] = [
            'type' => Model::TYPE_STRING,
            'null' => true,
        ];

        parent::initialize();
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
        if (!isset($exclude['toArray'])) {
            $result['toArray'] = true;
        }
    }

    public static function query()
    {
        if ($query = self::$query) {
            self::$query = false;

            return $query;
        }

        return parent::query();
    }

    public static function setQuery(Query $query)
    {
        self::$query = $query;
    }

    protected function uppercase($value)
    {
        return strtoupper($value);
    }
}

function validate()
{
    return false;
}

class TestModel2 extends Model
{
    public static $ids = ['id', 'id2'];

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

    public static $query;

    public function toArrayHook(array &$result, array $exclude, array $include, array $expand)
    {
        if (isset($include['toArrayHook'])) {
            $result['toArrayHook'] = true;
        }
    }

    public static function query()
    {
        if ($query = self::$query) {
            self::$query = false;

            return $query;
        }

        return parent::query();
    }

    public static function setQuery(Query $query)
    {
        self::$query = $query;
    }
}

class TestModelNoPermission extends ACLModel
{
    protected function hasPermission($permission, Model $requester)
    {
        return false;
    }
}

class TestModelHookFail extends Model
{
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

class Person extends ACLModel
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
        'name' => [
            'searchable' => true,
        ],
    ];
}

class AclObject extends ACLModel
{
    public $first = true;

    protected function hasPermission($permission, Model $requester)
    {
        if ($permission == 'whatever') {
            // always say no the first time
            if ($this->first) {
                $this->first = false;

                return false;
            }

            return true;
        } elseif ($permission == 'do nothing') {
            return $requester->id() == 5;
        }
    }
}
