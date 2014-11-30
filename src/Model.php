<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
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
    // Public variables
    /////////////////////////////

    public static $properties = [];

    /////////////////////////////
    // Protected variables
    /////////////////////////////

    protected $_id;
    protected $app;

    /* Property names that are excluded from the database */
    protected static $propertiesNotInDatabase = [];

    /* Default model configuration */
    protected static $config = [
        'cache' => [
            'strategies' => [
                'local', ],
            'prefix' => '',
            'expires' => 0, ],
        'requester' => false, ];

    /* Default parameters for Model::find() queries */
    protected static $defaultFindParameters = [
        'where' => [],
        'start' => 0,
        'limit' => 100,
        'search' => '',
        'sort' => '', ];

    protected static $injectedApp;

    /////////////////////////////
    // Private variables
    /////////////////////////////

    private static $idProperties = [
        'id' => [
            'type' => 'number',
            'mutable' => false,
            'admin_hidden_property' => true,
        ],
    ];
    private static $timestampProperties = [
        'created_at' => [
            'type' => 'date',
            'validate' => 'timestamp',
            'required' => true,
            'default' => 'now',
            'admin_hidden_property' => true,
            'admin_type' => 'datepicker',
        ],
        'updated_at' => [
            'type' => 'date',
            'validate' => 'timestamp',
            'null' => true,
            'admin_hidden_property' => true,
            'admin_type' => 'datepicker',
        ],
    ];
    private static $cachedProperties = [];

    private $localCache = [];
    private $sharedCache;
    private $relationModels;

    /////////////////////////////
    // GLOBAL CONFIGURATION
    /////////////////////////////

    /**
     * Changes the default model settings
     *
     * @param array $config
     */
    public static function configure(array $config)
    {
        static::$config = array_replace(static::$config, $config);
    }

    /**
     * Gets a config parameter
     *
     * @return mixed
     */
    public static function getConfigValue($key)
    {
        return Utility::array_value(static::$config, $key);
    }

    /**
     * Injects a DI container
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
     * Creates a new model object
     *
     * @param array|string $id ordered array of ids or comma-separated id string
     */
    public function __construct($id = false)
    {
        if (is_array($id)) {
            $id = implode(',', $id);
        }

        $this->_id = $id;

        $this->app = self::$injectedApp;
    }

    /**
     * Converts the model into a string
     *
     * @return string
     */
    public function __toString()
    {
        return get_called_class().'('.$this->_id.')';
    }

    /**
     * Gets an inaccessible property by looking it up via get().
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
     * Sets an inaccessible property by changing the locally cached value.
     * This method does not update the database or shared cache
     *
     * @param string $name
     * @param mixed  $value
     */
    public function __set($name, $value)
    {
        // if changing property, remove relation model
        if (isset($this->relationModels[ $name ])) {
            unset($this->relationModels[ $name ]);
        }

        $this->localCache[ $name ] = $value;
    }

    /**
     * Checks if an inaccessible property exists. Any property that is
     * in the schema or locally cached is considered to be set
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->localCache) || $this->hasProperty($name);
    }

    /**
     * Unsets an inaccessible property by invalidating it in the local cache.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->localCache)) {
            // if changing property, remove relation model
            if (isset($this->relationModels[ $name ])) {
                unset($this->relationModels[ $name ]);
            }

            unset($this->localCache[ $name ]);
        }
    }

    /////////////////////////////
    // MODEL PROPERTIES
    /////////////////////////////

    /**
     * Gets the model identifier(s)
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

        $idProperty = (array) static::idProperty();

        // get id(s) into key-value format
        $return = [];

        // match up id values from comma-separated id string with property names
        $ids = explode(',', $this->_id);
        $ids = array_reverse($ids);

        foreach ($idProperty as $f) {
            $id = (count($ids)>0) ? array_pop($ids) : false;

            $return[ $f ] = $id;
        }

        return $return;
    }

    /**
     * Checks if the model exists in the database
     *
     * @return boolean
     */
    public function exists()
    {
        return static::totalRecords($this->id(true)) == 1;
    }

    /**
     * Gets the model object corresponding to a relation
     * WARNING no check is used to see if the model returned actually exists
     *
     * @param string $property property
     *
     * @return Object|false model
     */
    public function relation($property)
    {
        $properties = static::properties();

        if (!static::hasProperty($property) || !isset($properties[ $property ][ 'relation' ])) {
            return false;
        }

        $relationModelName = $properties[ $property ][ 'relation' ];

        if (!isset($this->relationModels[ $property ])) {
            $this->relationModels[ $property ] = new $relationModelName($this->$property);
        }

        return $this->relationModels[ $property ];
    }

    /////////////////////////////
    // STATIC MODEL PROPERTIES
    /////////////////////////////

    /**
     * Returns the id propert(ies) for the model
     *
     * @return array|string
     */
    public static function idProperty()
    {
        return 'id';
    }

    /**
     * Gets the name of the model without namespacing
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
     * Generates metadata about the model
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
            'proper_name_plural' => $inflector->titleize($pluralKey) ];
    }

    /**
     * @deprecated
     */
    public static function info()
    {
        return static::metadata();
    }

    /**
     * Generates the tablename for the model
     *
     * @return string
     */
    public static function tablename()
    {
        $inflector = Inflector::get();

        return $inflector->camelize($inflector->pluralize(static::modelName()));
    }

    /**
     * Gets the properties for the model
     *
     * @param string $property property to lookup
     *
     * @return array
     */
    public static function properties($property = false)
    {
        $k = get_called_class();

        if (!isset(self::$cachedProperties[ $k ])) {
            self::$cachedProperties[ $k ] = array_replace(static::propertiesHook(), static::$properties);
        }

        if ($property) {
            return Utility::array_value(self::$cachedProperties[ $k ], $property);
        } else {
            return self::$cachedProperties[ $k ];
        }
    }

    /**
     * Adds extra properties that have not been explicitly defined.
     * If overriding, be sure to extend parent::propertiesHook()
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
     * Checks if the model has a property
     *
     * @param string $property property
     *
     * @return boolean has property
     */
    public static function hasProperty($property)
    {
        $properties = static::properties();

        return isset($properties[ $property ]);
    }

    /**
     * Checks if a property name is an id property
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
     * WARNING: requires 'create' permission from the requester
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
            if (Utility::array_value($property, 'required')) {
                $requiredProperties[] = $name;
            }
        }

        // add in default values
        foreach ($properties as $name => $fieldInfo) {
            if (isset($fieldInfo[ 'default' ]) && !isset($data[ $name ])) {
                $data[ $name ] = $fieldInfo[ 'default' ];
            }
        }

        // loop through each supplied field and validate
        $insertArray = [];
        foreach ($data as $field => $value) {
            if (!in_array($field, $propertyNames)) {
                continue;
            }

            $property = $properties[ $field ];

            // cannot insert keys, unless explicitly allowed
            if (isset($property[ 'mutable' ]) && !$property[ 'mutable' ]) {
                continue;
            }

            if (is_array($property)) {
                // assume empty string is a null value for properties
                // that are marked as optionally-null
                if (Utility::array_value($property, 'null') && empty($value)) {
                    $insertArray[ $field ] = null;
                    continue;
                }

                // validate
                $thisIsValid = $this->validate($property, $field, $value);

                // unique?
                if ($thisIsValid && Utility::array_value($property, 'unique')) {
                    $thisIsValid = $this->checkUniqueness($property, $field, $value);
                }

                $validated = $validated && $thisIsValid;

                // json
                if (Utility::array_value($property, 'type') == 'json' && !is_string($value)) {
                    $value = json_encode($value);
                }

                $insertArray[ $field ] = $value;
            }
        }

        // check for required fields
        foreach ($requiredProperties as $name) {
            if (!isset($insertArray[ $name ])) {
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
            if ($this->app['db']->insert($insertArray)
                ->into(static::tablename())->execute()) {
                $ids = [];
                $idProperty = (array) static::idProperty();
                foreach ($idProperty as $property) {
                    // attempt use the supplied value if the id property is mutable
                    $mutable = !isset($properties[$property]['mutable']) ||
                                $properties[$property ]['mutable'];

                    if ($mutable && isset($data[$property])) {
                        $ids[] = $data[$property];
                    } else {
                        $ids[] = $this->app['pdo']->lastInsertId();
                    }
                }

                // set id and cache properties
                $this->_id = implode(',', $ids);
                $this->cacheProperties($insertArray);

                // post-hook
                if (method_exists($this, 'postCreateHook')) {
                    $this->postCreateHook();
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
     * This method utilizes a local and shared caching layer (i.e. redis), a database layer,
     * and finally resorts to the default property value for the model.
     *
     * @param string|array $properties       list of properties to fetch values for
     * @param boolean      $skipLocalCache   skips local cache when true
     * @param boolean      $forceReturnArray always return an array when true
     *
     * @return mixed Returns value when only 1 found or an array when multiple values found
     */
    public function get($properties, $skipLocalCache = false, $forceReturnArray = false)
    {
        $show = $properties == 'relation';
        if (is_string($properties)) {
            $properties = explode(',', $properties);
        } else {
            $properties = (array) $properties;
        }

        /*
            Look up property values in this order:
            i) Local Cache (unless explicitly skipped)
            ii) Shared Cache
            iii) Database (if enabled)
            iv) Model Property Value Defaults
        */

        // Make a copy of properties to keep track of what's remaining.
        // Since this will be modified a copy must be made to prevent
        // functional side effects
        $remaining = $properties;

        $hasId = $this->_id !== false;

        $i = 1;
        $values = [];
        while ($i <= 4 && count($remaining) > 0) {
            if ($i == 1 && !$skipLocalCache) {
                $this->getFromLocalCache($remaining, $values);
            } elseif ($i == 2 && $hasId) {
                $this->getFromSharedCache($remaining, $values);
            } elseif ($i == 3 && $hasId) {
                $this->getFromDatabase($remaining, $values);
            } elseif ($i == 4) {
                $this->getFromDefaultValues($remaining, $values);
            }

            $i++;
        }

        if (count($properties) != count($values)) {
            // TODO should we throw a notice if one or more
            // properties were not found?
            if (!$forceReturnArray && count($properties) == 1) {
                return null;
            } else {
                return $values;
            }
        }

        return (!$forceReturnArray && count($values) == 1) ?
            reset($values) : $values;
    }

    /**
     * Converts the model to an array
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
            if (Utility::array_value($pData, 'hidden') &&
                !isset($namedInc[ $property ])) {
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
     * Converts the object to JSON format
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
     * WARNING: requires 'edit' permission from the requester
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

        $errorStack = $this->app[ 'errors' ];
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

        // update timestamp
        if (property_exists(get_called_class(), 'autoTimestamps')) {
            $data[ 'updated_at' ] = time();
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

            if (is_array($property)) {
                // cannot modify immutable properties
                if (isset($property['mutable']) && !$property['mutable']) {
                    continue;
                }

                // assume empty string is a null value for properties
                // that are marked as optionally-null
                if (Utility::array_value($property, 'null') && empty($value)) {
                    $updateArray[$field] = null;
                    continue;
                }

                // validate
                $thisIsValid = $this->validate($property, $field, $value);

                // unique?
                if ($thisIsValid && Utility::array_value($property, 'unique') && $value != $this->$field) {
                    $thisIsValid = $this->checkUniqueness($property, $field, $value);
                }

                $validated = $validated && $thisIsValid;

                // json
                if (Utility::array_value($property, 'type') == 'json' && !is_string($value)) {
                    $value = json_encode($value);
                }

                $updateArray[$field] = $value;
            }
        }

        if (!$validated) {
            return false;
        }

        try {
            if ($this->app['db']->update(static::tablename())
                ->values($updateArray)->where($this->id(true))->execute()) {
                // update the cache with our new values
                $this->cacheProperties($updateArray);

                // post-hook
                if (method_exists($this, 'postSetHook')) {
                    $this->postSetHook();
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
     * WARNING: requires 'delete' permission from the requester
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
                $this->emptyCache();

                // post-hook
                if (method_exists($this, 'postDeleteHook')) {
                    $this->postDeleteHook();
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
     * Fetches models with pagination support
     *
     * @param array key-value parameters
     *
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
                if (Utility::array_value($property, 'searchable')) {
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
            foreach ($models as $info) {
                $id = false;

                $idProperty = static::idProperty();
                if (is_array($idProperty)) {
                    $id = [];

                    foreach ($idProperty as $f) {
                        $id[] = $info[ $f ];
                    }
                } else {
                    $id = $info[ $idProperty ];
                }

                $model = new $modelName($id);
                $model->cacheProperties($info);
                $return[ 'models' ][] = $model;
            }
        }

        return $return;
    }

    public static function findAll(array $params = [])
    {
        return new Iterator(get_called_class(), $params);
    }

    /**
     * Fetches a single model according to criteria
     *
     * @param array $params array( start, limit, sort, search, where )
     *
     * @return Model|false
     */
    public static function findOne(array $params)
    {
        $models = static::find($params);

        return ($models[ 'count' ] > 0) ? reset($models[ 'models' ]) : false;
    }

    /**
     * Gets the toal number of records matching an optional criteria
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

    /////////////////////////////
    // CACHE
    /////////////////////////////

    /**
     * Loads and caches all of the properties from the database layer
     * IMPORTANT: this should be called before getting properties
     * any time a model *might* have been updated from an outside source
     *
     * @return Model
     */
    public function load()
    {
        if ($this->_id === false) {
            return;
        }

        $info = [];

        try {
            $info = (array) $this->app['db']->select('*')->from(static::tablename())
                ->where($this->id(true))->one();
        } catch (\Exception $e) {
            self::$injectedApp['logger']->error($e);
        }

        // marshal values from database
        foreach ($info as $k => $v) {
            $info[ $k ] = $this->marshalValue($k, $v);
        }

        $this->cacheProperties($info);

        return $this;
    }

    /**
     * Updates the local and shared cache with the new value for a property
     *
     * @param string $property property name
     * @param string $value    new value
     *
     * @return Model
     */
    public function cacheProperty($property, $value)
    {
        // if changing property, remove relation model
        if (isset($this->relationModels[ $property ])) {
            unset($this->relationModels[ $property ]);
        }

        /* Local Cache */
        $this->localCache[ $property ] = $value;

        /* Shared Cache */
        $this->cache()->set($property, $value, static::$config['cache']['expires']);

        return $this;
    }

    /**
     * Cache data inside of the local and shared cache
     *
     * @param array $data data to be cached
     *
     * @return Model
     */
    public function cacheProperties(array $data)
    {
        foreach ($data as $property => $value) {
            $this->cacheProperty($property, $value);
        }

        return $this;
    }

    /**
     * Invalidates a single property in the local and shared caches
     *
     * @param string $property property name
     *
     * @return Model
     */
    public function invalidateCachedProperty($property)
    {
        // if changing property, remove relation model
        if (isset($this->relationModels[ $property ])) {
            unset($this->relationModels[ $property ]);
        }

        /* Local Cache */
        unset($this->localCache[ $property ]);

        /* Shared Cache */
        $this->cache()->delete($property);

        return $this;
    }

    /**
     * Invalidates all cached properties for this model
     *
     * @return Model
     */
    public function emptyCache()
    {
        // explicitly clear all properties and any other values in cache
        $properties = array_unique(array_merge(
            array_keys(static::properties()),
            array_keys($this->localCache)));

        foreach ($properties as $property) {
            $this->invalidateCachedProperty($property);
        }

        return $this;
    }

    /////////////////////////////
    // PROTECTED METHODS
    /////////////////////////////

    protected function cache()
    {
        if (!$this->sharedCache) {
            $strategies = static::$config[ 'cache' ][ 'strategies' ];

            // generate cache prefix for this model
            $prefix = static::$config[ 'cache' ][ 'prefix' ].
                      strtolower(static::modelName()).'.'.$this->_id.'.';

            $this->sharedCache = new Cache($strategies, $prefix, $this->app);
        }

        return $this->sharedCache;
    }

    /////////////////////////////
    // PRIVATE METHODS
    /////////////////////////////

    private function getFromLocalCache(&$properties, &$values)
    {
        $idProperties = $this->id(true);
        $remove = [];

        foreach ($properties as $property) {
            if (array_key_exists($property, $this->localCache)) {
                $values[$property] = $this->marshalValue($property, $this->localCache[$property]);
            } elseif (static::isIdProperty($property)) {
                $values[$property] = $this->marshalValue($property, $idProperties[$property]);
            }

            // mark index of property to remove from list of properties
            if (array_key_exists($property, $values)) {
                $remove[] = $property;
            }
        }

        foreach ($remove as $property) {
            $index = array_search($property, $properties);
            unset($properties[$index]);
        }
    }

    private function getFromSharedCache(&$properties, &$values)
    {
        $cached = $this->cache()->get($properties, true);

        foreach ($cached as $property => $value) {
            $values[ $property ] = $this->marshalValue($property, $value);

            // remove property from list of remaining
            $index = array_search($property, $properties);
            unset($properties[ $index ]);
        }
    }

    private function getFromDatabase(&$properties, &$values)
    {
        try {
            $dbValues = $this->app['db']->select(implode(',', $properties))
                ->from(static::tablename())->where($this->id(true))->one();

            foreach ((array) $dbValues as $property => $value) {
                $values[$property] = $this->marshalValue($property, $value);
                $this->cacheProperty($property, $value);

                // remove property from list of remaining
                $index = array_search($property, $properties);
                unset($properties[$index]);
            }
        } catch (\Exception $e) {
            $this->app['logger']->error($e);
        }
    }

    private function getFromDefaultValues(&$properties, &$values)
    {
        $remove = [];

        $availableProperties = static::properties();

        foreach ($properties as $property) {
            if (isset($availableProperties[ $property ]) && isset($availableProperties[ $property ][ 'default' ])) {
                $values[ $property ] = $this->marshalValue($property, $availableProperties[ $property ][ 'default' ]);

                // mark index of property to remove from list of properties
                $remove[] = $property;
            }
        }

        foreach ($remove as $property) {
            $index = array_search($property, $properties);
            unset($properties[ $index ]);
        }
    }

    private function validate($property, $field, &$value)
    {
        $valid = true;

        if (isset($property[ 'validate' ]) && is_callable($property[ 'validate' ])) {
            $valid = call_user_func_array($property[ 'validate' ], [ $value ]);
        } elseif (isset($property[ 'validate' ])) {
            $valid = Validate::is($value, $property[ 'validate' ]);
        }

        if (!$valid) {
            $this->app[ 'errors' ]->push([
                'error' => VALIDATION_FAILED,
                'params' => [
                    'field' => $field,
                    'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::get()->titleize($field), ], ]);
        }

        return $valid;
    }

    private function checkUniqueness($property, $field, $value)
    {
        if (static::totalRecords([$field => $value]) > 0) {
            $this->app[ 'errors' ]->push([
                'error' => VALIDATION_NOT_UNIQUE,
                'params' => [
                    'field' => $field,
                    'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::get()->titleize($field), ], ]);

            return false;
        }

        return true;
    }

    private function marshalValue($property, $value)
    {
        // look up property (if it exists)
        $pData = static::properties($property);
        if (!$pData) {
            return $value;
        }

        if (Utility::array_value($pData, 'null') && $value == '') {
            return null;
        }

        $type = Utility::array_value($pData, 'type');

        if ($type == 'boolean') {
            return ($value == '1') ? true : false;
        }

        // ensure numbers/dates are cast as numbers
        // instead of strings by adding 0
        if (in_array($type, [ 'number', 'date' ])) {
            return $value + 0;
        }

        if ($type == 'json' && is_string($value)) {
            return (array) json_decode($value, true);
        }

        return $value;
    }
}
