<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */

/*
    The following properties (of model properties) are available:

    Schema:

        type:
            The type of the property.
            Accepted Types:
                string
                number
                boolean
                date
                json
            String
            Required
        default:
            The default value to be used when creating new models.
            String
            Optional

    Validation:

        mutable:
            Specifies whether the property can be set (mutated)
            Boolean
            Default: true
            Optional
        validate:
            Validation string passed to Validate::is() or validation function
            String or callable
            Optional
        required:
            Specifies whether the field is required
            Boolean
            Default: false
            Optional
        unique:
            Specifies whether the field is required to be unique
            Boolean
            Default: false
            Optional
        null:
            Specifies whether the column is allowed to have null values
            Boolean
            Default: false
            Optional

    Find:

        searchable:
            Specifies whether the property should be searched when
            querying models
            Boolean
            Default: false
            Optional

    Meta:

        title:
            Title of the property that shows up in admin panel
            String
            Default: Derived from property `name`
            Optional
        relation:
            Model class name (including namespace) the property is related to
            String
            Optional
        hidden:
            Hides a property when expanding the model, i.e. toArray()
            Boolean
            Default: false
            Optional
 */

namespace infuse;

use ICanBoogie\Inflector;
use infuse\Model\Iterator;
use Pimple\Container;
use Stash\Pool;
use Stash\Item;

if (!defined('ERROR_NO_PERMISSION')) {
    define('ERROR_NO_PERMISSION', 'no_permission');
}
if (!defined('VALIDATION_REQUIRED_FIELD_MISSING')) {
    define('VALIDATION_REQUIRED_FIELD_MISSING', 'required_field_missing');
}
if (!defined('VALIDATION_FAILED')) {
    define('VALIDATION_FAILED', 'validation_failed');
}
if (!defined('VALIDATION_NOT_UNIQUE')) {
    define('VALIDATION_NOT_UNIQUE', 'not_unique');
}

abstract class Model extends Acl
{
    /////////////////////////////
    // CONSTANTS
    /////////////////////////////

    const IMMUTABLE = 0;
    const MUTABLE_CREATE_ONLY = 1;
    const MUTABLE = 1;

    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_JSON = 'json';

    /////////////////////////////
    // Public variables
    /////////////////////////////

    /**
     * @staticvar array
     */
    public static $properties = [];

    /////////////////////////////
    // Protected variables
    /////////////////////////////

    /**
     * @var number|string
     */
    protected $_id;

    /**
     * @var App
     */
    protected $app;

    /**
     * @staticvar array
     * Property names that are excluded from the database
     */
    protected static $propertiesNotInDatabase = [];

    /**
     * @staticvar array
     * Default model configuration
     */
    protected static $config = [
        'cache' => [
            'expires' => 0, ],
        'requester' => false, ];

    /**
     * @staticvar array
     * Default parameters for Model::find() queries
     */
    protected static $defaultFindParameters = [
        'where' => [],
        'start' => 0,
        'limit' => 100,
        'search' => '',
        'sort' => '', ];

    /**
     * @staticvar App
     */
    protected static $injectedApp;

    /**
     * @var Stash\Pool
     */
    protected $_cache;

    /////////////////////////////
    // Private variables
    /////////////////////////////

    /**
     * @staticvar array
     */
    private static $propertyBase = [
        'type' => self::TYPE_STRING,
        'mutable' => self::MUTABLE,
        'null' => false,
        'unique' => false,
        'required' => false,
        'searchable' => false,
        'hidden' => false,
    ];

    /**
     * @staticvar array
     */
    private static $idProperties = [
        'id' => [
            'type' => self::TYPE_NUMBER,
            'mutable' => self::IMMUTABLE,
            'admin_hidden_property' => true,
        ],
    ];

    /**
     * @staticvar array
     */
    private static $timestampProperties = [
        'created_at' => [
            'type' => self::TYPE_DATE,
            'default' => null,
            'null' => true,
            'validate' => 'timestamp|db_timestamp',
            'admin_hidden_property' => true,
            'admin_type' => 'datepicker',
        ],
        'updated_at' => [
            'type' => self::TYPE_DATE,
            'validate' => 'timestamp|db_timestamp',
            'admin_hidden_property' => true,
            'admin_type' => 'datepicker',
        ],
    ];

    /**
     * @staticvar Stash\Pool
     */
    private static $defaultCache;

    /**
     * @staticvar array
     */
    private static $cachedProperties = [];

    /**
     * @var string
     */
    private static $cachePrefix = [];

    /**
     * @var array
     */
    private $_local = [];

    /**
     * @var array
     */
    private $_unsaved = [];

    /**
     * @var array
     */
    private $_relationModels = [];

    /**
     * @var Stash\Item
     */
    private $_cacheItem;

    /////////////////////////////
    // GLOBAL CONFIGURATION
    /////////////////////////////

    /**
     * Changes the default model settings.
     *
     * @param array $config
     */
    public static function configure(array $config)
    {
        static::$config = array_replace(static::$config, $config);
    }

    /**
     * Gets a config parameter.
     *
     * @return mixed
     */
    public static function getConfigValue($key)
    {
        return Utility::array_value(static::$config, $key);
    }

    /**
     * Injects a DI container.
     *
     * @param Container $app
     */
    public static function inject(Container $app)
    {
        self::$injectedApp = $app;
    }

    /////////////////////////////
    // MAGIC METHODS
    /////////////////////////////

    /**
     * Creates a new model object.
     *
     * @param array|string $id ordered array of ids or comma-separated id string
     */
    public function __construct($id = false)
    {
        if (is_array($id)) {
            $id = implode(',', $id);
        } elseif (strpos($id, ',') === false) {
            // ensure id has the right type
            $idProperties = (array) static::idProperty();
            $this->_id = $this->marshalFromStorage(static::properties($idProperties[0]), $id);
        }

        $this->_id = $id;

        $this->app = self::$injectedApp;

        if (self::$defaultCache) {
            $this->_cache = &self::$defaultCache;
        }
    }

    /**
     * Converts the model into a string.
     *
     * @return string
     */
    public function __toString()
    {
        return get_called_class().'('.$this->_id.')';
    }

    /**
     * Shortcut to a get() call for a given property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Sets an unsaved value.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        // if changing property, remove relation model
        if (isset($this->_relationModels[$name])) {
            unset($this->_relationModels[$name]);
        }

        $this->_unsaved[$name] = $value;
    }

    /**
     * Checks if an unsaved value or property exists by this name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->_unsaved) || $this->hasProperty($name);
    }

    /**
     * Unsets an unsaved value.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_unsaved)) {
            // if changing property, remove relation model
            if (isset($this->_relationModels[$name])) {
                unset($this->_relationModels[$name]);
            }

            unset($this->_unsaved[$name]);
        }
    }

    /////////////////////////////
    // MODEL PROPERTIES
    /////////////////////////////

    /**
     * Gets the model identifier(s).
     *
     * @param boolean $keyValue return key-value array of id
     *
     * @return array|string key-value if specified, otherwise comma-separated id string
     */
    public function id($keyValue = false)
    {
        if (!$keyValue) {
            return $this->_id;
        }

        $idProperties = (array) static::idProperty();

        // get id(s) into key-value format
        $return = [];

        // match up id values from comma-separated id string with property names
        $ids = explode(',', $this->_id);
        $ids = array_reverse($ids);

        foreach ($idProperties as $k => $f) {
            $id = (count($ids)>0) ? array_pop($ids) : false;

            // enforce the type by marshaling (otherwise it would always return a string)
            if ($id && isset($idProperties[$k])) {
                $property = $idProperties[$k];
                $id = $this->marshalFromStorage(static::properties($property), $id);
            }

            $return[$f] = $id;
        }

        return $return;
    }

    /**
     * Checks if the model exists in the database.
     *
     * @return boolean
     */
    public function exists()
    {
        return static::totalRecords($this->id(true)) == 1;
    }

    /**
     * Gets the model object corresponding to a relation
     * WARNING no check is used to see if the model returned actually exists.
     *
     * @param string $property property
     *
     * @return Object|false model
     */
    public function relation($property)
    {
        $properties = static::properties();

        if (!static::hasProperty($property) || !isset($properties[$property]['relation'])) {
            return false;
        }

        $relationModelName = $properties[$property]['relation'];

        if (!isset($this->_relationModels[$property])) {
            $this->_relationModels[$property] = new $relationModelName($this->$property);
        }

        return $this->_relationModels[$property];
    }

    /////////////////////////////
    // STATIC MODEL PROPERTIES
    /////////////////////////////

    /**
     * Returns the id propert(ies) for the model.
     *
     * @return array|string
     */
    public static function idProperty()
    {
        return 'id';
    }

    /**
     * Gets the name of the model without namespacing.
     *
     * @return string
     */
    public static function modelName()
    {
        $class_name = get_called_class();

        // strip namespacing
        $paths = explode('\\', $class_name);

        return end($paths);
    }

    /**
     * Generates metadata about the model.
     *
     * @return array
     */
    public static function metadata()
    {
        $class_name = get_called_class();
        $modelName = static::modelName();

        $inflector = Inflector::get();
        $singularKey = $inflector->underscore($modelName);
        $pluralKey = $inflector->pluralize($singularKey);

        return [
            'model' => $modelName,
            'class_name' => $class_name,
            'singular_key' => $singularKey,
            'plural_key' => $pluralKey,
            'proper_name' => $inflector->titleize($singularKey),
            'proper_name_plural' => $inflector->titleize($pluralKey), ];
    }

    /**
     * @deprecated
     */
    public static function info()
    {
        return static::metadata();
    }

    /**
     * Generates the tablename for the model.
     *
     * @return string
     */
    public static function tablename()
    {
        $inflector = Inflector::get();

        return $inflector->camelize($inflector->pluralize(static::modelName()));
    }

    /**
     * Gets the properties for the model.
     *
     * @param string $property property to lookup
     *
     * @return array
     */
    public static function properties($property = false)
    {
        $k = get_called_class();

        if (!isset(self::$cachedProperties[$k])) {
            self::$cachedProperties[$k] = array_replace(static::propertiesHook(), static::$properties);

            foreach (self::$cachedProperties[$k] as &$cachedProperty) {
                $cachedProperty = array_replace(self::$propertyBase, $cachedProperty);
            }
        }

        if ($property !== false) {
            return Utility::array_value(self::$cachedProperties[$k], $property);
        } else {
            return self::$cachedProperties[$k];
        }
    }

    /**
     * Adds extra properties that have not been explicitly defined.
     * If overriding, be sure to extend parent::propertiesHook().
     *
     * @return array
     */
    protected static function propertiesHook()
    {
        $properties = [];

        $idProperty = static::idProperty();
        if ($idProperty == 'id') {
            $properties = self::$idProperties;
        }

        if (property_exists(get_called_class(), 'autoTimestamps')) {
            return array_replace(self::$timestampProperties, $properties);
        }

        return $properties;
    }

    /**
     * Checks if the model has a property.
     *
     * @param string $property property
     *
     * @return boolean has property
     */
    public static function hasProperty($property)
    {
        $properties = static::properties();

        return isset($properties[$property]);
    }

    /**
     * Checks if a property name is an id property.
     *
     * @return boolean
     */
    public static function isIdProperty($property)
    {
        $idProperty = static::idProperty();

        return (is_array($idProperty) && in_array($property, $idProperty)) ||
               $property == $idProperty;
    }

    public static function hasSchema()
    {
        return true;
    }

    /////////////////////////////
    // CRUD OPERATIONS
    /////////////////////////////

    /**
     * Creates a new model
     * WARNING: requires 'create' permission from the requester.
     *
     * @param array $data key-value properties
     *
     * @return boolean
     */
    public function create(array $data = [])
    {
        $errorStack = $this->app[ 'errors' ];
        $errorStack->setCurrentContext(static::modelName().'.create');

        if ($this->_id !== false) {
            return false;
        }

        // permission?
        if (!$this->can('create', static::$config[ 'requester' ])) {
            $errorStack->push([ 'error' => ERROR_NO_PERMISSION ]);

            return false;
        }

        // pre-hook
        if (method_exists($this, 'preCreateHook') && !$this->preCreateHook($data)) {
            return false;
        }

        $validated = true;

        $properties = static::properties();

        // get the property names, and required properties
        $propertyNames = [];
        $requiredProperties = [];
        foreach ($properties as $name => $property) {
            $propertyNames[] = $name;
            if ($property['required']) {
                $requiredProperties[] = $name;
            }
        }

        // add in default values
        foreach ($properties as $name => $fieldInfo) {
            if (array_key_exists('default', $fieldInfo) && !array_key_exists($name, $data)) {
                $data[$name] = $fieldInfo['default'];
            }
        }

        // loop through each supplied field and validate
        $insertArray = [];
        foreach ($data as $field => $value) {
            if (!in_array($field, $propertyNames)) {
                continue;
            }

            $property = $properties[$field];

            // cannot insert keys, unless explicitly allowed
            if ($property['mutable'] == self::IMMUTABLE && !array_key_exists('default', $property)) {
                continue;
            }

            $validated = $validated && $this->marshalToStorage($property, $field, $value);
            $insertArray[$field] = $value;
        }

        // check for required fields
        foreach ($requiredProperties as $name) {
            if (!isset($insertArray[$name])) {
                $this->app[ 'errors' ]->push([
                    'error' => VALIDATION_REQUIRED_FIELD_MISSING,
                    'params' => [
                        'field' => $name,
                        'field_name' => (isset($properties[$name]['title'])) ? $properties[$name][ 'title' ] : Inflector::get()->titleize($name), ], ]);

                $validated = false;
            }
        }

        if (!$validated) {
            return false;
        }

        try {
            $inserted = $this->app['db']->insert($insertArray)
                ->into(static::tablename())->execute();
            if ($inserted) {
                // set new id(s)
                $ids = [];
                $idProperties = (array) static::idProperty();
                foreach ($idProperties as $property) {
                    // attempt use the supplied value if the id property is mutable
                    $id = null;
                    if ($properties[$property ]['mutable'] == self::MUTABLE && isset($data[$property])) {
                        $id = $data[$property];
                    } else {
                        $id = $this->app['pdo']->lastInsertId();
                    }

                    $ids[] = $this->marshalFromStorage(static::properties($property), $id);
                }

                $this->_id = (count($ids) > 1) ? implode(',', $ids) : $ids[0];

                // post-hook
                if (method_exists($this, 'postCreateHook')) {
                    return $this->postCreateHook() !== false;
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    /**
     * Fetches property values from the model.
     *
     * This method looks up values in this order:
     * unsaved values, local cache, cache, database, defaults
     *
     * @param string|array $properties       list of properties to fetch values for
     * @param boolean      $skipLocalCache   skips local cache when true
     * @param boolean      $forceReturnArray always return an array when true
     *
     * @return mixed Returns value when only 1 found or an array when multiple values found
     */
    public function get($properties, $skipCache = false, $forceReturnArray = false)
    {
        if (!is_array($properties)) {
            $properties = explode(',', $properties);
        }

        $values = array_replace($this->id(true), $this->_local, $this->_unsaved);

        $numMissing = count(array_diff($properties, array_keys($values)));
        if ($numMissing > 0 || $skipCache) {
            $this->load($skipCache);
            $values = array_replace($values, $this->_local, $this->_unsaved);
        }

        // only return requested properties
        $return = [];
        foreach ($properties as $key) {
            if (array_key_exists($key, $values)) {
                $return[$key] = $values[$key];
            } else {
                // set any missing values to the default value
                $return[$key] = $this->getDefaultValueFor($key);
                $this->_local[$key] = $return[$key];
            }
        }

        if (!$forceReturnArray && count($return) == 1) {
            return reset($return);
        }

        return $return;
    }

    /**
     * Converts the model to an array.
     *
     * @param array $exclude properties to exclude
     * @param array $include properties to include
     * @param array $expand  properties to expand
     *
     * @return array properties
     */
    public function toArray(array $exclude = [], array $include = [], array $expand = [])
    {
        // TODO this method is ripe for some performance improvements

        $properties = [];

        // apply namespacing to $exclude
        $namedExc = [];
        foreach ($exclude as $e) {
            Utility::array_set($namedExc, $e, true);
        }

        // apply namespacing to $include
        $namedInc = [];
        foreach ($include as $e) {
            Utility::array_set($namedInc, $e, true);
        }

        // apply namespacing to $expand
        $namedExp = [];
        foreach ($expand as $e) {
            Utility::array_set($namedExp, $e, true);
        }

        // get the list of appropriate properties
        foreach (static::properties() as $property => $pData) {
            // skip excluded properties
            if (isset($namedExc[ $property ]) && !is_array($namedExc[ $property ])) {
                continue;
            }

            // skip hidden properties that are not explicitly included
            if ($pData['hidden'] && !isset($namedInc[$property])) {
                continue;
            }

            $properties[] = $property;
        }

        // make sure each property key at least has a null value
        // and then get the value for each property
        $result = array_replace(array_fill_keys($properties, null),
                                $this->get($properties, false, true));

        // expand properties
        foreach ($namedExp as $k => $subExp) {
            // if the property is null, excluded, or not included
            // then we are not going to expand it
            if (!isset($result[ $k ]) || !$result[ $k ]) {
                continue;
            }

            $subExc = Utility::array_value($namedExc, $k);
            $subInc = Utility::array_value($namedInc, $k);

            // convert exclude, include, and expand into dot notation
            // then take the keys for a flattened dot notation
            $flatExc = is_array($subExc) ? array_keys(Utility::array_dot($subExc)) : [];
            $flatInc = is_array($subInc) ? array_keys(Utility::array_dot($subInc)) : [];
            $flatExp = is_array($subExp) ? array_keys(Utility::array_dot($subExp)) : [];

            $relation = $this->relation($k);
            if ($relation) {
                $result[ $k ] = $relation->toArray($flatExc, $flatInc, $flatExp);
            }
        }

        // apply hooks, if available
        if (method_exists($this, 'toArrayHook')) {
            $this->toArrayHook($result, $namedExc, $namedInc, $namedExp);
        }

        return $result;
    }

    /**
     * Converts the object to JSON format.
     *
     * @param array $exclude properties to exclude
     * @param array $include properties to include
     * @param array $expand  properties to expand
     *
     * @return string json
     */
    public function toJson(array $exclude = [], array $include = [], array $expand = [])
    {
        return json_encode($this->toArray($exclude, $include, $expand));
    }

    /**
     * Updates the model
     * WARNING: requires 'edit' permission from the requester.
     *
     * @param array|string $data  key-value properties or name of property
     * @param string new   $value value to set if name supplied
     *
     * @return boolean
     */
    public function set($data, $value = false)
    {
        if (!is_array($data)) {
            return $this->set([$data => $value]);
        }

        if ($this->_id === false) {
            return false;
        }

        $errorStack = $this->app['errors'];
        $errorStack->setCurrentContext(static::modelName().'.set');

        // permission?
        if (!$this->can('edit', static::$config['requester'])) {
            $errorStack->push([ 'error' => ERROR_NO_PERMISSION ]);

            return false;
        }

        // not updating anything?
        if (count($data) == 0) {
            return true;
        }

        // pre-hook
        if (method_exists($this, 'preSetHook') && !$this->preSetHook($data)) {
            return false;
        }

        $validated = true;
        $updateArray = [];
        $properties = static::properties();

        // get the property names
        $propertyNames = array_keys($properties);

        // loop through each supplied field and validate
        foreach ($data as $field => $value) {
            // exclude if field does not map to a property
            if (!in_array($field, $propertyNames)) {
                continue;
            }

            $property = $properties[$field];

            // can only modify mutable properties
            if ($property['mutable'] != self::MUTABLE) {
                continue;
            }

            $validated = $validated && $this->marshalToStorage($property, $field, $value);
            $updateArray[$field] = $value;
        }

        if (!$validated) {
            return false;
        }

        try {
            $updated = (count($updateArray) == 0) ||
                $this->app['db']->update(static::tablename())
                                ->values($updateArray)
                                ->where($this->id(true))
                                ->execute();

            if ($updated) {
                // clear the cache
                $this->clearCache();

                // post-hook
                if (method_exists($this, 'postSetHook')) {
                    return $this->postSetHook() !== false;
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    /**
     * Delete the model
     * WARNING: requires 'delete' permission from the requester.
     *
     * @return boolean success
     */
    public function delete()
    {
        if ($this->_id === false) {
            return false;
        }

        $errorStack = $this->app[ 'errors' ];
        $errorStack->setCurrentContext(static::modelName().'.delete');

        // permission?
        if (!$this->can('delete', static::$config[ 'requester' ])) {
            $errorStack->push([ 'error' => ERROR_NO_PERMISSION ]);

            return false;
        }

        // pre-hook
        if (method_exists($this, 'preDeleteHook') && !$this->preDeleteHook()) {
            return false;
        }

        try {
            // delete the model
            if ($this->app['db']->delete(static::tablename())
                ->where($this->id(true))->execute()) {
                // clear the cache
                $this->clearCache();

                // post-hook
                if (method_exists($this, 'postDeleteHook')) {
                    return $this->postDeleteHook() !== false;
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }

        return false;
    }

    /////////////////////////////
    // MODEL LOOKUPS
    /////////////////////////////

    /**
     * Fetches models with pagination support.
     *
     * @param array key-value parameters
     * @param array $params optional parameters [ 'where', 'start', 'limit', 'search', 'sort' ]
     *
     * @return array array( 'models' => models, 'count' => 'total found' )
     */
    public static function find(array $params = [])
    {
        $params = array_replace(static::$defaultFindParameters, $params);

        $modelName = get_called_class();
        $properties = static::properties();

        // WARNING: using MYSQL LIKE for search, this is very inefficient

        if (!empty($params['search'])) {
            $w = [];
            $search = addslashes($params['search']);
            foreach ($properties as $name => $property) {
                if ($property['searchable']) {
                    $w[] = "`$name` LIKE '%$search%'";
                }
            }

            if (count($w) > 0) {
                $params['where'][] = '('.implode(' OR ', $w).')';
            }
        }

        // verify sort
        $sortParams = [];

        $columns = explode(',', $params['sort']);
        foreach ($columns as $column) {
            $c = explode(' ', trim($column));

            if (count($c) != 2) {
                continue;
            }

            $propertyName = $c[ 0 ];

            // validate property
            if (!isset($properties[ $propertyName ])) {
                continue;
            }

            // validate direction
            $direction = strtolower($c[ 1 ]);
            if (!in_array($direction, [ 'asc', 'desc' ])) {
                continue;
            }

            $sortParams[] = [$propertyName, $direction];
        }

        $return = [
            'count' => static::totalRecords($params['where']),
            'models' => [], ];

        $limit = min($params['limit'], 1000);
        $offset = max($params['start'], 0);

        // load models
        $models = false;

        try {
            $models = self::$injectedApp['db']->select('*')
                ->from(static::tablename())->where($params['where'])
                ->limit($limit, $offset)->orderBy($sortParams)->all();
        } catch (\Exception $e) {
            self::$injectedApp['logger']->error($e);
        }

        if (is_array($models)) {
            foreach ($models as $values) {
                // determine the model id
                $id = false;
                $idProperty = static::idProperty();
                if (is_array($idProperty)) {
                    $id = [];

                    foreach ($idProperty as $f) {
                        $id[] = $values[$f];
                    }
                } else {
                    $id = $values[$idProperty];
                }

                // create the model and cache the loaded values
                $model = new $modelName($id);
                $model->loadFromDb($values)->cache();

                $return['models'][] = $model;
            }
        }

        return $return;
    }

    public static function findAll(array $params = [])
    {
        return new Iterator(get_called_class(), $params);
    }

    /**
     * Fetches a single model according to criteria.
     *
     * @param array $params array( start, limit, sort, search, where )
     *
     * @return Model|false
     */
    public static function findOne(array $params)
    {
        $models = static::find($params);

        return ($models['count'] > 0) ? reset($models['models']) : false;
    }

    /**
     * Gets the toal number of records matching an optional criteria.
     *
     * @param array $where criteria
     *
     * @return int total
     */
    public static function totalRecords(array $where = [])
    {
        try {
            return (int) self::$injectedApp['db']->select('count(*)')
                ->from(static::tablename())->where($where)->scalar();
        } catch (\Exception $e) {
            self::$injectedApp['logger']->error($e);
        }

        return 0;
    }

    /**
     * Loads the model from the cache or database.
     * First, attempts to load the model from the caching layer.
     * If that fails, then attempts to load the model from the
     * database layer.
     *
     * @param boolean $skipCache
     *
     * @return Model
     */
    public function load($skipCache = false)
    {
        if ($this->_id === false) {
            return $this;
        }

        if ($this->_cache && !$skipCache) {
            // attempt load from the cache first
            $item = $this->cacheItem();
            $values = $item->get();

            if ($item->isMiss()) {
                // If the cache was a miss, then lock the item down,
                // attempt to load from the database, and update it.
                // Stash calls this Stampede Protection.
                $item->lock();

                $this->loadFromDb()->cache();
            } else {
                $this->_local = $values;
            }
        } else {
            $this->loadFromDb()->cache();
        }

        // clear any relations
        $this->_relationModels = [];

        return $this;
    }

    /////////////////////////////
    // CACHE
    /////////////////////////////

    /**
     * Sets the default cache instance used by new models.
     *
     * @param Stash\Pool $pool
     */
    public static function setDefaultCache(Pool $pool)
    {
        self::$defaultCache = $pool;
    }

    /**
     * Clears the default cache instance.
     */
    public static function clearDefaultCache()
    {
        self::$defaultCache = false;
    }

    /**
     * Sets the cache instance.
     *
     * @param Stash/Pool $pool
     *
     * @return self
     */
    public function setCache(Pool $pool)
    {
        $this->_cache = $pool;

        return $this;
    }

    /**
     * Returns the cache instance.
     *
     * @return Stash/Pool|false
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Returns the cache TTL.
     *
     * @return number|null
     */
    public function getCacheTTL()
    {
        $expires = static::$config['cache']['expires'];

        return ($expires < 1) ? null : $expires;
    }

    /**
     * Returns the cache key for this model.
     *
     * @return string
     */
    public function cacheKey()
    {
        $k = get_called_class();
        if (!isset(self::$cachePrefix[$k])) {
            self::$cachePrefix[$k] = 'models/'.strtolower(static::modelName());
        }

        return self::$cachePrefix[$k].'/'.$this->_id;
    }

    /**
     * Returns the cache item for this model.
     *
     * @return Stash\Item|null
     */
    public function cacheItem()
    {
        if (!$this->_cache) {
            return;
        }

        if (!$this->_cacheItem) {
            $this->_cacheItem = $this->_cache->getItem($this->cacheKey());
        }

        return $this->_cacheItem;
    }

    /**
     * Caches the entire model.
     *
     * @return self
     */
    public function cache()
    {
        if (!$this->_cache || count($this->_local) == 0) {
            return $this;
        }

        // cache the local properties
        $this->cacheItem()->set($this->_local, $this->getCacheTTL());

        return $this;
    }

    /**
     * Clears the cache for this model.
     *
     * @return self
     */
    public function clearCache()
    {
        $this->_unsaved = [];
        $this->_local = [];
        $this->_relationModels = [];

        if ($this->_cache) {
            $this->cacheItem()->clear();
        }

        return $this;
    }

    /////////////////////////////
    // PRIVATE METHODS
    /////////////////////////////

    /**
     * Marshals a value for a given property to storage, and
     * checks the validity of a value.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return boolean|null true: is valid, false: is invalid
     */
    private function marshalToStorage(array $property, $propertyName, &$value)
    {
        // assume empty string is a null value for properties
        // that are marked as optionally-null
        if ($property['null'] && empty($value)) {
            $value = null;

            return true;
        }

        // json
        if ($property['type'] == self::TYPE_JSON && !is_string($value)) {
            $value = json_encode($value);
        }

        // validate
        $valid = $this->validate($property, $propertyName, $value);

        // unique?
        if ($valid && $property['unique'] && ($this->_id === false || $value != $this->$propertyName)) {
            $valid = $this->checkUniqueness($property, $propertyName, $value);
        }

        return $valid;
    }

    /**
     * Validates a value for a property.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return boolean
     */
    private function validate(array $property, $propertyName, &$value)
    {
        $valid = true;

        if (isset($property['validate']) && is_callable($property['validate'])) {
            $valid = call_user_func_array($property['validate'], [ $value ]);
        } elseif (isset($property['validate'])) {
            $valid = Validate::is($value, $property['validate']);
        }

        if (!$valid) {
            $this->app['errors']->push([
                'error' => VALIDATION_FAILED,
                'params' => [
                    'field' => $propertyName,
                    'field_name' => (isset($property['title'])) ? $property['title'] : Inflector::get()->titleize($propertyName), ], ]);
        }

        return $valid;
    }

    /**
     * Checks if a value is unique for a property.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return boolean
     */
    private function checkUniqueness(array $property, $propertyName, $value)
    {
        if (static::totalRecords([$propertyName => $value]) > 0) {
            $this->app['errors']->push([
                'error' => VALIDATION_NOT_UNIQUE,
                'params' => [
                    'field' => $propertyName,
                    'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::get()->titleize($propertyName), ], ]);

            return false;
        }

        return true;
    }

    /**
     * Loads the model from the database and caches it.
     *
     * @param array $values optional values (if already loaded from DB)
     *
     * @return self
     */
    private function loadFromDb($values = false)
    {
        if (!is_array($values)) {
            try {
                $values = $this->app['db']->select('*')
                    ->from(static::tablename())->where($this->id(true))
                    ->one();
            } catch (\Exception $e) {
                self::$injectedApp['logger']->error($e);
            }
        }

        if (is_array($values)) {
            // marshal values from database
            $this->_local = [];
            foreach ($values as $k => &$v) {
                $property = static::properties($k);
                if (is_array($property)) {
                    $this->_local[$k] = $this->marshalFromStorage($property, $v);
                }
            }

            return $this;
        }

        return $this;
    }

    /**
     * Gets the marshaled default value for a property (if set).
     *
     * @param string $property
     *
     * @return mixed
     */
    private function getDefaultValueFor($property)
    {
        $property = static::properties($property);

        if (!is_array($property) || !isset($property['default'])) {
            return;
        }

        return $this->marshalFromStorage($property, $property['default']);
    }

    /**
     * Marshals a value for a given property from storage.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed
     */
    private function marshalFromStorage(array $property, $value)
    {
        if ($property['null'] && $value == '') {
            return;
        }

        $type = $property['type'];

        if ($type == self::TYPE_BOOLEAN && is_string($value)) {
            return ($value == '1') ? true : false;
        }

        // cast numbers as....numbers
        if ($type == self::TYPE_NUMBER) {
            return $value + 0;
        }

        // also cast dates as numbers
        if ($type == self::TYPE_DATE) {
            if (!is_numeric($value)) {
                return strtotime($value);
            } else {
                return $value + 0;
            }
        }

        if ($type == self::TYPE_JSON && is_string($value)) {
            return (array) json_decode($value, true);
        }

        return $value;
    }
}
