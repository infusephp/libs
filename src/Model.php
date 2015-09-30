<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse;

use ICanBoogie\Inflector;
use Infuse\Model\ACLModel;
use Infuse\Model\ModelEvent;
use Infuse\Model\Driver\DatabaseDriver;
use Infuse\Model\Driver\DriverInterface;
use Infuse\Model\Query;
use Infuse\Model\Relation\HasOne;
use Infuse\Model\Relation\BelongsTo;
use Infuse\Model\Relation\HasMany;
use Infuse\Model\Relation\BelongsToMany;
use Pimple\Container;
use Stash\Pool;
use Stash\Item;
use Symfony\Component\EventDispatcher\EventDispatcher;

abstract class Model implements \ArrayAccess
{
    const IMMUTABLE = 0;
    const MUTABLE_CREATE_ONLY = 1;
    const MUTABLE = 2;

    const TYPE_STRING = 'string';
    const TYPE_NUMBER = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_JSON = 'json';

    const ERROR_REQUIRED_FIELD_MISSING = 'required_field_missing';
    const ERROR_VALIDATION_FAILED = 'validation_failed';
    const ERROR_NOT_UNIQUE = 'not_unique';

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
     * @staticvar \Pimple\Container
     */
    protected static $injectedApp;

    /**
     * @staticvar int
     */
    protected static $cacheTTL = 0;

    /**
     * @staticvar array
     */
    protected static $dispatchers;

    /**
     * @var number|string
     */
    protected $_id;

    /**
     * @var \Pimple\Container
     */
    protected $app;

    /**
     * @var \Stash\Pool
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
     * @staticvar array
     */
    private static $initialized = [];

    /**
     * @staticvar \Stash\Pool
     */
    private static $defaultCache;

    /**
     * @staticvar array
     */
    private static $cachedProperties = [];

    /**
     * @staticvar string
     */
    private static $cachePrefix = [];

    /**
     * @staticvar Model\Driver\DriverInterface
     */
    private static $driver;

    /**
     * @var array
     */
    private $_local = [];

    /**
     * @var array
     */
    private $_unsaved = [];

    /**
     * @var bool
     */
    private $_ignoreUnsaved;

    /**
     * @var array
     */
    private $_relationModels = [];

    /**
     * @var \Stash\Item
     */
    private $_cacheItem;

    /**
     * @var int
     */
    private $_cacheTTL;

    /**
     * Creates a new model object.
     *
     * @param array|string $id     ordered array of ids or comma-separated id string
     * @param array        $values optional key-value map to pre-seed model
     */
    public function __construct($id = false, array $values = [])
    {
        // call the initialize function once
        $k = static::modelName();
        if (!isset(self::$initialized[$k])) {
            $this->initialize();
            self::$initialized[$k] = true;
        }

        // TODO need to store the id as an array
        // instead of a string to maintain type integrity
        if (is_array($id)) {
            $id = implode(',', $id);
        } elseif (strpos($id, ',') === false) {
            $this->_id = $id;
        }

        $this->_id = $id;

        $this->app = self::$injectedApp;

        if (self::$defaultCache) {
            $this->_cache = &self::$defaultCache;
        }

        $this->setCacheTTL(static::$cacheTTL);

        // cache the loaded values
        if (count($values) > 0) {
            $this->loadFromStorage($values)->cache();
        }
    }

    /**
     * This method gets called once per model. It's used
     * to perform any one-off tasks before the model
     * can be created. It's only called if a model instance
     * is constructed.
     */
    protected function initialize()
    {
        // load the driver
        static::getDriver();
    }

    /**
     * @deprecated
     */
    public static function configure(array $config)
    {
        if (isset($config['cache']) && isset($config['cache']['expires'])) {
            static::$cacheTTL = $config['cache']['expires'];
        }

        if (isset($config['requester'])) {
            ACLModel::setRequester($config['requester']);
        }
    }

    /**
     * Injects a DI container.
     *
     * @param \Pimple\Container $app
     */
    public static function inject(Container $app)
    {
        self::$injectedApp = $app;
    }

    /**
     * Gets the DI container used for this model.
     *
     * @return Container
     */
    public function getApp()
    {
        return $this->app;
    }

    /**
     * Sets the driver for all models.
     *
     * @param Model\Driver\DriverInterface $driver
     */
    public static function setDriver(DriverInterface $driver)
    {
        self::$driver = $driver;
    }

    /**
     * Gets the driver for all models.
     *
     * @return Model\Driver\DriverInterface
     */
    public static function getDriver()
    {
        // use the database driver by default
        if (!self::$driver) {
            self::$driver = new DatabaseDriver(self::$injectedApp);
        }

        return self::$driver;
    }

    /////////////////////////////
    // Magic Methods
    /////////////////////////////

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
        return $this->get([$name]);
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
    // ArrayAccess Interface
    /////////////////////////////

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public static function __callStatic($name, $parameters)
    {
        // Any calls to unkown static methods should be deferred to
        // the query. This allows calls like User::where()
        // to replace User::query()->where().
        return call_user_func_array([static::query(), $name], $parameters);
    }

    /////////////////////////////
    // Model Properties
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
     * @return bool has property
     */
    public static function hasProperty($property)
    {
        $properties = static::properties();

        return isset($properties[$property]);
    }

    /**
     * Checks if a property name is an id property.
     *
     * @return bool
     */
    public static function isIdProperty($property)
    {
        $idProperty = static::idProperty();

        return (is_array($idProperty) && in_array($property, $idProperty)) ||
               $property == $idProperty;
    }

    /**
     * Gets the model identifier(s).
     *
     * @param bool $keyValue return key-value array of id
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

        // TODO need to store the id as an array
        // instead of a string to maintain type integrity
        foreach ($idProperties as $k => $f) {
            $id = (count($ids) > 0) ? array_pop($ids) : false;

            $return[$f] = $id;
        }

        return $return;
    }

    /////////////////////////////
    // CRUD Operations
    /////////////////////////////

    /**
     * Saves the model.
     *
     * @return bool
     */
    public function save()
    {
        if ($this->_id === false) {
            return $this->create($this->_unsaved);
        }

        return $this->set($this->_unsaved);
    }

    /**
     * Creates a new model.
     *
     * @param array $data key-value properties
     *
     * @return bool
     */
    public function create(array $data = [])
    {
        if ($this->_id !== false) {
            return false;
        }

        $this->_unsaved = $data;

        // call the before create hook
        if (!$this->beforeCreate()) {
            return false;
        }

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
            if (array_key_exists('default', $fieldInfo) && !array_key_exists($name, $this->_unsaved)) {
                $this->_unsaved[$name] = $fieldInfo['default'];
            }
        }

        // loop through each supplied field and validate
        $validated = true;
        $insertArray = [];
        foreach ($this->_unsaved as $field => $value) {
            if (!in_array($field, $propertyNames)) {
                continue;
            }

            $property = $properties[$field];

            // cannot insert keys, unless explicitly allowed
            if ($property['mutable'] == self::IMMUTABLE && !array_key_exists('default', $property)) {
                continue;
            }

            $validated = $validated && $this->filterAndValidate($property, $field, $value);
            $insertArray[$field] = $value;
        }

        // check for required fields
        foreach ($requiredProperties as $name) {
            if (!isset($insertArray[$name])) {
                $this->app['errors']->push([
                    'error' => self::ERROR_REQUIRED_FIELD_MISSING,
                    'params' => [
                        'field' => $name,
                        'field_name' => (isset($properties[$name]['title'])) ? $properties[$name]['title'] : Inflector::get()->titleize($name), ], ]);

                $validated = false;
            }
        }

        if (!$validated) {
            return false;
        }

        $created = self::$driver->createModel($this, $insertArray);

        if ($created) {
            // determine the model's new ID
            $this->_id = $this->getNewID();

            // clear the cache
            // NOTE the cache is cleared before the model.created
            // event so that fetching values forces a reload
            // from the persistent storage layer
            $this->clearCache();

            // call the after create hook
            if (!$this->afterCreate()) {
                return false;
            }
        }

        return $created;
    }

    /**
     * Ignores unsaved values when fetching the next value.
     *
     * @return self
     */
    public function ignoreUnsaved()
    {
        $this->_ignoreUnsaved = true;

        return $this;
    }

    /**
     * Fetches property values from the model.
     *
     * This method looks up values in this order:
     * unsaved values, local cache, cache, database, defaults
     *
     * @param string|array $properties       list of properties to fetch values for
     * @param bool         $skipLocalCache   skips local cache when true
     * @param bool         $forceReturnArray always return an array when true
     *
     * @return mixed Returns value when only 1 found or an array when multiple values found
     */
    public function get($properties, $skipCache = false, $forceReturnArray = false)
    {
        if (!is_array($properties)) {
            $properties = explode(',', $properties);
        }

        $values = array_replace($this->id(true), $this->_local);

        $ignoreUnsaved = $this->_ignoreUnsaved;
        $this->_ignoreUnsaved = false;

        if (!$ignoreUnsaved) {
            $values = array_replace($values, $this->_unsaved);
        }

        $numMissing = count(array_diff($properties, array_keys($values)));
        if ($numMissing > 0 || $skipCache) {
            $this->load($skipCache);
            $values = array_replace($values, $this->_local);

            if (!$ignoreUnsaved) {
                $values = array_replace($values, $this->_unsaved);
            }
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
     * Gets the ID for a newly created model.
     *
     * @return string
     */
    protected function getNewID()
    {
        $ids = [];
        $idProperties = (array) static::idProperty();
        foreach ($idProperties as $k) {
            // attempt use the supplied value if the ID property is mutable
            $id = null;
            $property = static::properties($k);
            if (in_array($property['mutable'], [self::MUTABLE, self::MUTABLE_CREATE_ONLY]) && isset($this->_unsaved[$k])) {
                $ids[] = $this->_unsaved[$k];
            } else {
                $ids[] = self::$driver->getCreatedID($this, $k);
            }
        }

        // TODO need to store the id as an array
        // instead of a string to maintain type integrity
        return (count($ids) > 1) ? implode(',', $ids) : $ids[0];
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
            if (isset($namedExc[$property]) && !is_array($namedExc[$property])) {
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
            if (!isset($result[$k]) || !$result[$k]) {
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
                $result[$k] = $relation->toArray($flatExc, $flatInc, $flatExp);
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
     * Updates the model.
     *
     * @param array|string $data  key-value properties or name of property
     * @param string new   $value value to set if name supplied
     *
     * @return bool
     */
    public function set($data, $value = false)
    {
        if (!is_array($data)) {
            return $this->set([$data => $value]);
        }

        if ($this->_id === false) {
            return false;
        }

        // not updating anything?
        if (count($data) == 0) {
            return true;
        }

        // call the before update hook
        if (!$this->beforeUpdate($data)) {
            return false;
        }

        $validated = true;
        $updateArray = [];
        $properties = static::properties();

        // get the property names
        $propertyNames = array_keys($properties);

        // loop through each supplied field and validate
        foreach ($data as $propertyName => $value) {
            // exclude if field does not map to a property
            if (!in_array($propertyName, $propertyNames)) {
                continue;
            }

            $property = $properties[$propertyName];

            // can only modify mutable properties
            if ($property['mutable'] != self::MUTABLE) {
                continue;
            }

            $validated = $validated && $this->filterAndValidate($property, $propertyName, $value);
            $updateArray[$propertyName] = $value;
        }

        if (!$validated) {
            return false;
        }

        $updated = self::$driver->updateModel($this, $updateArray);

        if ($updated) {
            // clear the cache
            // NOTE the cache is cleared before the model.updated
            // event so that fetching values forces a reload
            // from the persistent storage layer
            $this->clearCache();

            // call the after update hook
            if (!$this->afterUpdate()) {
                return false;
            }
        }

        return $updated;
    }

    /**
     * Delete the model.
     *
     * @return bool success
     */
    public function delete()
    {
        if ($this->_id === false) {
            return false;
        }

        // call the before delete hook
        if (!$this->beforeDelete()) {
            return false;
        }

        $deleted = self::$driver->deleteModel($this);

        if ($deleted) {
            // call the after delete hook
            if (!$this->afterDelete()) {
                return false;
            }

            // clear the cache
            // NOTE clear the cache after the model.deleted
            // event is fired so that cached model values
            // are still available
            $this->clearCache();
        }

        return $deleted;
    }

    /////////////////////////////
    // Queries
    /////////////////////////////

    /**
     * Generates a new query instance.
     *
     * @return Model\Query
     */
    public static function query()
    {
        return new Query(get_called_class());
    }

    /**
     * @deprecated
     */
    public static function findOne(array $parameters)
    {
        $query = static::query();

        if (isset($parameters['where'])) {
            $query->where($parameters['where']);
        }

        if (isset($parameters['start'])) {
            $query->start($parameters['start']);
        }

        if (isset($parameters['sort'])) {
            $query->sort($parameters['sort']);
        }

        return $query->first();
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
        $query = static::query();
        $query->where($where);

        return self::getDriver()->totalRecords($query);
    }

    /**
     * Checks if the model exists in the database.
     *
     * @return bool
     */
    public function exists()
    {
        return static::totalRecords($this->id(true)) == 1;
    }

    /**
     * Loads the model from the cache or database.
     * First, attempts to load the model from the caching layer.
     * If that fails, then attempts to load the model from the
     * database layer.
     *
     * @param bool $skipCache
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

                $this->loadFromStorage()->cache();
            } else {
                $this->_local = $values;
            }
        } else {
            $this->loadFromStorage()->cache();
        }

        // clear any relations
        $this->_relationModels = [];

        return $this;
    }

    /**
     * Loads the model from the database and caches it.
     *
     * @param array $values optional values (if already loaded from DB)
     *
     * @return self
     */
    public function loadFromStorage($values = false)
    {
        if (!is_array($values)) {
            $values = self::$driver->loadModel($this);
        }

        if (is_array($values)) {
            $this->_local = [];
            foreach ($values as $k => $v) {
                $this->_local[$k] = $v;
            }
        }

        return $this;
    }

    /////////////////////////////
    // Relationships
    /////////////////////////////

    /**
     * Gets the model object corresponding to a relation
     * WARNING no check is used to see if the model returned actually exists.
     *
     * @param string $propertyName property
     *
     * @return \Infuse\Model model
     */
    public function relation($propertyName)
    {
        // TODO deprecated
        $property = static::properties($propertyName);

        if (!isset($this->_relationModels[$propertyName])) {
            $relationModelName = $property['relation'];
            $this->_relationModels[$propertyName] = new $relationModelName($this->$propertyName);
        }

        return $this->_relationModels[$propertyName];
    }

    /**
     * Creates the parent side of a One-To-One relationship.
     *
     * @param string $model      foreign model class
     * @param string $foriegnKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation
     */
    public function hasOne($model, $foreignKey = false, $localKey = false)
    {
        // the default local key would look like `user_id`
        // for a model named User
        if (!$foreignKey) {
            $inflector = Inflector::get();
            $foreignKey = strtolower($inflector->underscore(static::modelName())).'_id';
        }

        if (!$localKey) {
            $localKey = 'id';
        }

        return new HasOne($model, $foreignKey, $localKey, $this);
    }

    /**
     * Creates the child side of a One-To-One or One-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foriegnKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation
     */
    public function belongsTo($model, $foreignKey = false, $localKey = false)
    {
        if (!$foreignKey) {
            $foreignKey = 'id';
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!$localKey) {
            $inflector = Inflector::get();
            $localKey = strtolower($inflector->underscore($model::modelName())).'_id';
        }

        return new BelongsTo($model, $foreignKey, $localKey, $this);
    }

    /**
     * Creates the parent side of a Many-To-One or Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foriegnKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation
     */
    public function hasMany($model, $foreignKey = false, $localKey = false)
    {
        // the default local key would look like `user_id`
        // for a model named User
        if (!$foreignKey) {
            $inflector = Inflector::get();
            $foreignKey = strtolower($inflector->underscore(static::modelName())).'_id';
        }

        if (!$localKey) {
            $localKey = 'id';
        }

        return new HasMany($model, $foreignKey, $localKey, $this);
    }

    /**
     * Creates the child side of a Many-To-Many relationship.
     *
     * @param string $model      foreign model class
     * @param string $foriegnKey identifying key on foreign model
     * @param string $localKey   identifying key on local model
     *
     * @return Relation
     */
    public function belongsToMany($model, $foreignKey = false, $localKey = false)
    {
        if (!$foreignKey) {
            $foreignKey = 'id';
        }

        // the default local key would look like `user_id`
        // for a model named User
        if (!$localKey) {
            $inflector = Inflector::get();
            $localKey = strtolower($inflector->underscore($model::modelName())).'_id';
        }

        return new BelongsToMany($model, $foreignKey, $localKey, $this);
    }

    /////////////////////////////
    // Events
    /////////////////////////////

    /**
     * Gets the event dispatcher.
     *
     * @return \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public static function getDispatcher($ignoreCache = false)
    {
        $class = get_called_class();
        if ($ignoreCache || !isset(self::$dispatchers[$class])) {
            self::$dispatchers[$class] = new EventDispatcher();
        }

        return self::$dispatchers[$class];
    }

    /**
     * Subscribes to a listener to an event.
     *
     * @param string   $event    event name
     * @param callable $listener
     * @param int      $priority optional priority, higher #s get called first
     */
    public static function listen($event, callable $listener, $priority = 0)
    {
        static::getDispatcher()->addListener($event, $listener, $priority);
    }

    /**
     * Adds a listener to the model.creating event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function creating(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::CREATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.created event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function created(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::CREATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updating event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function updating(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::UPDATING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.updated event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function updated(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::UPDATED, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleting event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function deleting(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::DELETING, $listener, $priority);
    }

    /**
     * Adds a listener to the model.deleted event.
     *
     * @param callable $listener
     * @param int      $priority
     */
    public static function deleted(callable $listener, $priority = 0)
    {
        static::listen(ModelEvent::DELETED, $listener, $priority);
    }

    /**
     * Dispatches an event.
     *
     * @param string $eventName
     *
     * @return Model\ModelEvent
     */
    protected function dispatch($eventName)
    {
        $event = new ModelEvent($this);

        return static::getDispatcher()->dispatch($eventName, $event);
    }

    /**
     * Dispatches the model.creating event.
     *
     * @return bool
     */
    private function beforeCreate()
    {
        $event = $this->dispatch(ModelEvent::CREATING);
        if ($event->isPropagationStopped()) {
            return false;
        }

        // TODO deprecated
        if (method_exists($this, 'preCreateHook') && !$this->preCreateHook($this->_unsaved)) {
            return false;
        }

        return true;
    }

    /**
     * Dispatches the model.created event.
     *
     * @return bool
     */
    private function afterCreate()
    {
        $event = $this->dispatch(ModelEvent::CREATED);
        if ($event->isPropagationStopped()) {
            return false;
        }

        // TODO deprecated
        if (method_exists($this, 'postCreateHook') && $this->postCreateHook() === false) {
            return false;
        }

        return true;
    }

    /**
     * Dispatches the model.updating event.
     *
     * @param array $data
     *
     * @return bool
     */
    private function beforeUpdate(array &$data)
    {
        $event = $this->dispatch(ModelEvent::UPDATING);
        if ($event->isPropagationStopped()) {
            return false;
        }

        // TODO deprecated
        if (method_exists($this, 'preSetHook') && !$this->preSetHook($data)) {
            return false;
        }

        return true;
    }

    /**
     * Dispatches the model.updated event.
     *
     * @return bool
     */
    private function afterUpdate()
    {
        $event = $this->dispatch(ModelEvent::UPDATED);
        if ($event->isPropagationStopped()) {
            return false;
        }

        // TODO deprecated
        if (method_exists($this, 'postSetHook') && $this->postSetHook() === false) {
            return false;
        }

        return true;
    }

    /**
     * Dispatches the model.deleting event.
     *
     * @return bool
     */
    private function beforeDelete()
    {
        $event = $this->dispatch(ModelEvent::DELETING);
        if ($event->isPropagationStopped()) {
            return false;
        }

        // TODO deprecated
        if (method_exists($this, 'preDeleteHook') && !$this->preDeleteHook()) {
            return false;
        }

        return true;
    }

    /**
     * Dispatches the model.created event.
     *
     * @return bool
     */
    private function afterDelete()
    {
        $event = $this->dispatch(ModelEvent::DELETED);
        if ($event->isPropagationStopped()) {
            return false;
        }

        // TODO deprecated
        if (method_exists($this, 'postDeleteHook') && $this->postDeleteHook() === false) {
            return false;
        }

        return true;
    }

    /////////////////////////////
    // Caching
    /////////////////////////////

    /**
     * Sets the default cache instance used by new models.
     *
     * @param \Stash\Pool $pool
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
     * @param \Stash\Pool $pool
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
     * @return \Stash\Pool|false
     */
    public function getCache()
    {
        return $this->_cache;
    }

    /**
     * Sets the default cache TTL.
     *
     * @param int $expires
     */
    public static function setDefaultCacheTTL($expires)
    {
        static::$cacheTTL = $expires;
    }

    /**
     * Sets the cache TTL.
     *
     * @param int $expires
     *
     * @return self
     */
    public function setCacheTTL($expires)
    {
        $this->_cacheTTL = $expires;

        return $this;
    }

    /**
     * Returns the cache TTL.
     *
     * @return int|null
     */
    public function getCacheTTL()
    {
        return ($this->_cacheTTL < 1) ? null : $this->_cacheTTL;
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
     * @return \Stash\Item|null
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
    // Validation
    /////////////////////////////

    /**
     * Validates and marshals a value to storage.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return bool
     */
    private function filterAndValidate(array $property, $propertyName, &$value)
    {
        // assume empty string is a null value for properties
        // that are marked as optionally-null
        if ($property['null'] && empty($value)) {
            $value = null;

            return true;
        }

        // filter
        $value = $this->filter($property, $value);

        // validate
        list($valid, $value) = $this->validate($property, $propertyName, $value);

        // unique?
        if ($valid && $property['unique'] && ($this->_id === false || $value != $this->ignoreUnsaved()->$propertyName)) {
            $valid = $this->checkUniqueness($property, $propertyName, $value);
        }

        return $valid;
    }

    /**
     * Filter a value for a property.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return bool
     */
    private function filter(array $property, $value)
    {
        if (isset($property['filter'])) {
            $filter = $property['filter'];

            return $this->$filter($value);
        }

        return $value;
    }

    /**
     * Validates a value for a property.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return bool
     */
    private function validate(array $property, $propertyName, $value)
    {
        $valid = true;

        if (isset($property['validate']) && is_callable($property['validate'])) {
            $valid = call_user_func_array($property['validate'], [$value]);
        } elseif (isset($property['validate'])) {
            $valid = Validate::is($value, $property['validate']);
        }

        if (!$valid) {
            $this->app['errors']->push([
                'error' => self::ERROR_VALIDATION_FAILED,
                'params' => [
                    'field' => $propertyName,
                    'field_name' => (isset($property['title'])) ? $property['title'] : Inflector::get()->titleize($propertyName), ], ]);
        }

        return [$valid, $value];
    }

    /**
     * Checks if a value is unique for a property.
     *
     * @param array  $property
     * @param string $propertyName
     * @param mixed  $value
     *
     * @return bool
     */
    private function checkUniqueness(array $property, $propertyName, $value)
    {
        if (static::totalRecords([$propertyName => $value]) > 0) {
            $this->app['errors']->push([
                'error' => self::ERROR_NOT_UNIQUE,
                'params' => [
                    'field' => $propertyName,
                    'field_name' => (isset($property['title'])) ? $property['title'] : Inflector::get()->titleize($propertyName), ], ]);

            return false;
        }

        return true;
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

        return $property['default'];
    }
}
