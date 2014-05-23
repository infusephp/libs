<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2014 Jared King
 * @license MIT
 */

/*
	The following properties (of model properties) are available:
 
 	Schema:
 	
  		type:
  			The type of the property.
  			Accepted Types:
  				id
	  			text
	  			longtext
	  			number
	  			boolean
	  			enum
	  			password
	  			date
	  			hidden
	  			custom
	  			html
	  		String
	  		Required
	  	default:
	  		The default value to be used when creating new models.
	  		String
	  		Optional
	  	db_type:
	  		The type of field in the database, overrides the default type for a property, if a default exists
	  		String
	  		Default: int
	  		Required|Optional Required if property is `enum` or `number`
  		length:
  			Overrides the default maximum length of the column values in the database. Use this when a different
  			value is needed besides the one specified
  			Integer|String
  			Default: Chosen according to type
  			Optional
  		auto_increment:
  			Auto increments the property when creating new models within the schema
  			Boolean
  			Default: true if property type is `id`, otherwise false
  			Optional
  			
  	Validation:
  	
  		mutable:
  			The property can be set
  			Boolean
  			Default: true, unless property type is `id` or `auto_increment` is true
  			Optional
 		validate:
 			Validation string according to Validate::is()
 			String
 			Optional
 		required:
 			Specifies whether the field is required
 			Boolean
 			Default: false
 			Optional
 		unique:
 			Specified whether the field is required to be unique
 			Boolean
 			Default: false
 			Optional
  		null:
  			Specifies whether the column is allowed to have null values
  			Boolean
  			Default: false
  			Optional  			

	Meta:
	
	  	title:
  			Title of the property that shows up in admin panel
  			String
  			Default: Derived from property `name`
  			Optional
  		enum:
  			A key-value map of acceptable values if property type is `enum`
  			Array
  			Required if specifying `enum` type  		
  		relation:
			Model class name (including namespace) the property is related to
  			String
  			Optional
 */

namespace infuse;

if( !defined( 'ERROR_NO_PERMISSION' ) )
	define( 'ERROR_NO_PERMISSION', 'no_permission' );

abstract class Model extends Acl
{
	/////////////////////////////
	// Model properties
	/////////////////////////////

	static $properties = array();
	static $idProperty = 'id';

	/////////////////////////////
	// Protected class variables
	/////////////////////////////

	// specifies fields that should be escaped with htmlspecialchars()
	protected static $escapedProperties = array();
	protected static $tablename = false;
	protected static $hasSchema = true;

	protected static $config = array(
		'cache' => array(
			'strategies' => array(
				'local' => array(
					'prefix' => '' ) ) ),
		'database' => array(
			'enabled' => true ),
		'requester' => false );

	protected static $defaultFindParameters = array(
		'where' => array(),
		'start' => 0,
		'limit' => 100,
		'search' => '',
		'sort' => '' );

	/////////////////////////////
	// Private class variables
	/////////////////////////////

	private static $excludePropertyTypes = array( 'custom', 'html' );
	private $localCache = array();
	private $sharedCache;
	private $relationModels;

	/////////////////////////////
	// GLOBAL MODEL CONFIGURATION
	/////////////////////////////

	/**
	 * Changes the default model settings
	 *
	 * @param array $config
	 */
	static function configure( array $config )
	{
		static::$config = array_replace( static::$config, $config );
	}

	/**
	 * Gets a config parameter
	 *
	 * @return mixed
	 */
	static function getConfigValue( $key )
	{
		return Util::array_value( static::$config, $key );
	}

	/////////////////////////////
	// MAGIC METHODS
	/////////////////////////////

	/**
	 * Creates a new model object
	 *
	 * @param array|string $id ordered array of ids or comma-separated id string
	 */
	function __construct( $id = false )
	{
		if( is_array( $id ) )
			$id = implode( ',', $id );

		$this->id = $id;
	}

	/**
	 * Converts the model into a string
	 *
	 * @return string
	 */
	function __toString()
	{
		return get_called_class() . '(' . $this->id() . ')';
	}

	/**
	 * Gets an inaccessible property by looking it up via get().
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	function __get( $name )
	{
		return $this->get( $name );
	}

	/**
	 * Sets an inaccessible property by changing the locally cached value. 
	 * This method does not update the database or shared cache
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	function __set( $name, $value )
	{
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
	function __isset( $name )
	{
		return array_key_exists( $name, $this->localCache ) || $this->hasProperty( $name );
	}

	/**
	 * Unsets an inaccessible property by invalidating it in the local cache.
	 *
	 * @param string $name
	 */
	function __unset( $name )
	{
		if( array_key_exists( $name, $this->localCache ) )
			unset( $this->localCache[ $name ] );
	}
		
	/////////////////////////////
	// META METHODS
	/////////////////////////////

	/**
	 * Gets the name of the model without namespacing
	 *
	 * @return string
	 */
	static function modelName()
	{
		$class_name = get_called_class();
		
		// strip namespacing
		$paths = explode( '\\', $class_name );
		return end( $paths );
	}
	
	/**
	 * Generates metadata about the model
	 *
	 * @return array
	 */
	static function metadata()
	{
		$class_name = get_called_class();
		$modelName = static::modelName();
				
		$singularKey = Inflector::underscore( $modelName );
		$pluralKey = Inflector::pluralize( $singularKey );

		return array(
			'model' => $modelName,
			'class_name' => $class_name,
			'singular_key' => $singularKey,
			'plural_key' => $pluralKey,
			'proper_name' => Inflector::titleize( $singularKey ),
			'proper_name_plural' => Inflector::titleize( $pluralKey ) );
	}

	/**
	 * @deprecated
	 */
	static function info()
	{
		return static::metadata();
	}

	/**
	 * Generates the tablename for the model
	 *
	 * @return string
	 */
	static function tablename()
	{
		return Inflector::camelize( Inflector::pluralize( static::modelName() ) );
	}
	
	/**
	 * Checks if a property name is an id property
	 *
	 * @return boolean
	 */
	static function isIdProperty( $propertyName )
	{
		return ( is_array( static::$idProperty ) && in_array( $propertyName, static::$idProperty ) ) || $propertyName == static::$idProperty;
	}

	/**
	 * Checks if the model has a property
	 *
	 * @param string $property property
	 *
	 * @return boolean has property
	 */
	static function hasProperty( $property )
	{
		return isset( static::$properties[ $property ] );
	}

	/////////////////////////////
	// MODEL METHODS
	/////////////////////////////

	/**
	 * Gets the model identifier(s)
	 *
	 * @param boolean $keyValue return key-value array of id
	 *
	 * @return array|string key-value if specified, otherwise comma-separated id string
	 */
	function id( $keyValue = false )
	{
		if( !$keyValue )
			return $this->id;
	
		$idProperties = (array)static::$idProperty;
		
		// get id(s) into key-value format
		$return = array();
		
		// match up id values from comma-separated id string with property names
		$ids = explode( ',', $this->id );
		$ids = array_reverse( $ids );
		
		foreach( $idProperties as $f )
			$return[ $f ] = (count($ids)>0) ? array_pop( $ids ) : false;
		
		return $return;
	}

	/**
	 * Checks if the model exists in the database
	 *
	 * @return boolean
	 */
	function exists()
	{
		return static::totalRecords( $this->id( true ) ) == 1;
	}

	/**
	 * Gets the model object corresponding to a relation
	 * WARNING no check is used to see if the model returned actually exists
	 *
	 * @param string $property property
	 *
	 * @return Object|false model
	 */
	function relation( $property )
	{
		if( !static::hasProperty( $property ) || !isset( static::$properties[ $property ][ 'relation' ] ) )
			return false;

		$relationModelName = static::$properties[ $property ][ 'relation' ];

		if( !isset( $this->relationModels[ $relationModelName ] ) )
			$this->relationModels[ $relationModelName ] = new $relationModelName( $this->$property );

		return $this->relationModels[ $relationModelName ];
	}

	/////////////////////////////
	// CRUD OPERATIONS
	/////////////////////////////
	
	/**
	 * Creates a new model
	 *
	 * @param array $data key-value properties
	 *
	 * @return boolean
	 */
	static function create( array $data )
	{
		$modelName = get_called_class();

		ErrorStack::setContext( static::modelName() . '.create' );

		$model = new $modelName;
		
		// permission?
		if( !$model->can( 'create', static::$config[ 'requester' ] ) )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}

		// pre-hook
		if( method_exists( $model, 'preCreateHook' ) && !$model->preCreateHook( $data ) )
			return false;

		$validated = true;
		
		// get the property names, and required properties
		$propertyNames = array();
		$requiredProperties = array();
		foreach( static::$properties as $name => $property )
		{
			$propertyNames[] = $name;
			if( Util::array_value( $property, 'required' ) )
				$requiredProperties[] = $name;
		}
		
		// add in default values
		foreach( static::$properties as $name => $fieldInfo )
		{
			if( isset( $fieldInfo[ 'default' ] ) && !isset( $data[ $name ] ) )
				$data[ $name ] = $fieldInfo[ 'default' ];
		}
				
		// loop through each supplied field and validate
		$insertArray = array();
		foreach( $data as $field => $value )
		{
			if( !in_array( $field, $propertyNames ) )
				continue;

			$property = static::$properties[ $field ];

			// cannot insert keys, unless explicitly allowed
			if( self::isIdProperty( $field ) && !Util::array_value( $property, 'mutable' ) )
				continue;
			
			if( is_array( $property ) )
			{
				// assume empty string is a null value for properties
				// that are marked as optionally-null
				if( Util::array_value( $property, 'null' ) && empty( $value ) )
				{
					$insertArray[ $field ] = null;
					continue;
				}
				
				$thisIsValid = true;
				
				// validate
				if( isset( $property[ 'validate' ] ) )
					$thisIsValid = $this->validate( $property, $field, $value );

				// unique?
				if( $thisIsValid && Util::array_value( $property, 'unique' ) )
					$thisIsValid = $this->checkUniqueness( $property, $field, $value );
				
				$validated = $validated && $thisIsValid;
				
				$insertArray[ $field ] = $value;
			}
		}
				
		// check for required fields
		foreach( $requiredProperties as $name )
		{
			if( !isset( $insertArray[ $name ] ) )
			{
				ErrorStack::add( array(
					'error' => VALIDATION_REQUIRED_FIELD_MISSING,
					'params' => array(
						'field' => $name,
						'field_name' => (isset(static::$properties[$name]['title'])) ? static::$properties[$name][ 'title' ] : Inflector::humanize( $name ) ) ) );

				$validated = false;
			}
		}
		
		if( !$validated )
			return false;

		if( !static::$config[ 'database' ][ 'enabled' ] ||
			Database::insert( static::tablename(), $insertArray ) )
		{
			$ids = array();

			// derive the id for every property that is not auto_increment
			// NOTE this does not handle the case where there is > 1 auto_increment primary key
			$idProperties = (array)static::$idProperty;
			foreach( $idProperties as $property )
			{
				if( Util::array_value( static::$properties[ $property ], 'mutable' ) && isset( $data[ $property ] ) )
					$ids[] = Util::array_value( $data, $property );
				else
					$ids[] = (static::$config['database']['enabled']) ? Database::lastInsertID() : mt_rand();
			}

			// create new model
			$newModel = new $modelName( implode( ',', $ids ) );
			
			// cache
			$newModel->cacheProperties( $insertArray );
			
			// post-hook
			if( method_exists( $newModel, 'postCreateHook' ) )
				$newModel->postCreateHook();
			
			return $newModel;
		}
		
		return false;
	}

	/**
	 * Fetches property values from the model.
	 *
	 * This method utilizes a local and shared caching layer (i.e. redis), a database layer,
	 * and finally resorts to the default property value for the model.
	 *
	 * @param string|array $properties list of properties to fetch values for
	 * @param boolean $skipLocalCache skips local cache when true
	 * @param boolean $forceReturnArray always return an array when true
	 *
	 * @return mixed Returns value when only 1 found or an array when multiple values found
	 */
	function get( $properties, $skipLocalCache = false, $forceReturnArray = false )
	{
		$show = $properties == 'relation';
		if( is_string( $properties ) )
			$properties = explode( ',', $properties );
		else
			$properties = (array)$properties;

		$onlyOneProperty = count( $properties ) == 1;

		/*
			Look up values in this order from these places:
			i) Local Cache (unless skipped)
			ii) Shared Cache
			iii) Database
			iv) Default Model Property Values
		*/

		$i = 1;
		$values = array();
		while( $i <= 4 && count( $properties ) > 0 )
		{
			if( $i == 1 && !$skipLocalCache )
				$this->getFromLocalCache( $properties, $values );
			else if( $i == 2 )
				$this->getFromSharedCache( $properties, $values );
			else if( $i == 3 && static::$config[ 'database' ][ 'enabled' ] )
				$this->getFromDatabase( $properties, $values );
			else if( $i == 4 )
				$this->getFromDefaultValues( $properties, $values );

			$i++;
		}

		// TODO should we throw a notice if properties are remaining?
		return ( !$forceReturnArray && count( $values ) == 1 ) ? reset( $values ) : $values;
	}
	
	/**
	 * Updates the model
	 *
	 * @param array|string $data key-value properties or name of property
	 * @param string new $value value to set if name supplied
	 *
	 * @return boolean
	 */
	function set( $data, $value = false )
	{
		$modelName = get_called_class();

		ErrorStack::setContext( static::modelName() . '.set' );
	
		// permission?
		if( !$this->can( 'edit', static::$config[ 'requester' ] ) )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}
		
		if( !is_array( $data ) )
			$data = array( $data => $value );
		
		// not updating anything?
		if( count( $data ) == 0 )
			return true;
			
		// pre-hook
		if( method_exists( $this, 'preSetHook' ) && !$this->preSetHook( $data ) )
			return false;

		$validated = true;
		$updateArray = $this->id( true );
		$updateKeys = array_keys( $updateArray );
		
		// get the property names
		$propertyNames = array_keys( static::$properties );
		
		// loop through each supplied field and validate
		foreach( $data as $field => $value )
		{
			// cannot change keys
			if( in_array( $field, $updateKeys ) )
				continue;
			
			// exclude if field does not map to a property
			if( !in_array( $field, $propertyNames ) )
				continue;

			$property = static::$properties[ $field ];

			if( is_array( $property ) )
			{
				// assume empty string is a null value for properties
				// that are marked as optionally-null
				if( Util::array_value( $property, 'null' ) && empty( $value ) )
				{
					$updateArray[ $field ] = null;
					continue;
				}

				$thisIsValid = true;
				
				// validate
				if( isset( $property[ 'validate' ] ) )
					$thisIsValid = $this->validate( $property, $field, $value );

				// unique?
				if( $thisIsValid && Util::array_value( $property, 'unique' ) && $value != $this->$field )
					$thisIsValid = $this->checkUniqueness( $property, $field, $value );
				
				$validated = $validated && $thisIsValid;
				
				$updateArray[ $field ] = $value;
			}
		}

		if( !$validated )
			return false;

		if( !static::$config[ 'database' ][ 'enabled' ] ||
			Database::update( static::tablename(), $updateArray, $updateKeys ) )
		{
			// update the cache with our new values
			$this->cacheProperties( $updateArray );
			// post-hook
			if( method_exists( $this, 'postSetHook' ) )
				$this->postSetHook();

			return true;
		}
		
		return false;
	}
	
	/**
	 * Delete the model
	 *
	 * @return boolean success
	 */
	function delete()
	{
		$modelName = get_called_class();

		ErrorStack::setContext( static::modelName() . '.delete' );

		// permission?
		if( !$this->can( 'delete', static::$config[ 'requester' ] ) )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}
		
		// pre-hook
		if( method_exists( $this, 'preDeleteHook' ) && !$this->preDeleteHook() )
			return false;
		
		// delete the model
		if( !static::$config[ 'database' ][ 'enabled' ] ||
			Database::delete( static::tablename(), $this->id( true ) ) )
		{
			// clear the cache
			$this->emptyCache();

			// post-hook
			if( method_exists( $this, 'postDeleteHook' ) )
				$this->postDeleteHook();
			
			return true;
		}
		else
			return false;
	}
	
	/**
	 * Converts the modelt to an array
	 *
	 * @param array $exclude properties to exclude
	 *
	 * @return array properties
	 */
	function toArray( array $exclude = array() )
	{
		$properties = array();
		
		// get the names of all the properties
		foreach( static::$properties as $name => $property )
		{
			if( !empty( $name ) && !in_array( $name, $exclude ) && !in_array( $property[ 'type' ], self::$excludePropertyTypes ) )
				$properties[] = $name;
		}

		// make sure each property key at least has a null value
		// and then get the value for each property
		return array_replace(
			array_fill_keys( $properties, null ),
			(array)$this->get( $properties, false, true ) );
	}
	
	/**
	 * Converts the object to JSON format
	 *
	 * @param array $exclude properties to exclude
	 *
	 * @return string json
	 */
	function toJson( array $exclude = array() )
	{
		return json_encode( $this->toArray( $exclude ) );
	}
	
	/////////////////////////////
	// MODEL LOOKUP METHODS
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
	static function find( array $params = array() )
	{
		$params = array_replace( static::$defaultFindParameters, $params );
	
		$params[ 'start' ] = max( $params[ 'start' ], 0 );
		$params[ 'limit' ] = min( $params[ 'limit' ], 1000 );

		$modelName = get_called_class();
		
		// WARNING: using MYSQL LIKE for search, this is very inefficient
		
		if( !empty( $params[ 'search' ] ) )
		{
			$w = array();
			$search = addslashes( $params[ 'search' ] );
			foreach( static::$properties as $name => $property )
			{
				if( !in_array( Util::array_value( $property, 'type' ), self::$excludePropertyTypes ) )
					$w[] = "$name LIKE '%$search%'";
			}
			
			$params[ 'where' ][] = '(' . implode( ' OR ', $w ) . ')';
		}

		// verify sort
		$sortParams = array();

		$columns = explode( ',', $params[ 'sort' ] );
		foreach( $columns as $column )
		{
			$c = explode( ' ', trim( $column ) );
			
			if( count( $c ) != 2 )
				continue;
			
			$propertyName = $c[ 0 ];
			
			// validate property
			if( !isset( static::$properties[ $propertyName ] ) )
				continue;

			// validate direction
			$direction = strtolower( $c[ 1 ] );
			if( !in_array( $direction, array( 'asc', 'desc' ) ) )
				continue;
			
			$sortParams[] = "$propertyName $direction";
		}
		
		$return = array(
			'count' => static::totalRecords( $params[ 'where' ] ),
			'models' => array() );
		
		$filter = array(
			'where' => $params[ 'where' ],
			'limit' => $params[ 'start' ] . ',' . $params[ 'limit' ] );
		
		$sortStr = implode( ',', $sortParams );
		if( $sortStr )
			$filter[ 'orderBy' ] = $sortStr;

		// load models
		$models = Database::select(
			static::tablename(),
			'*',
			$filter );
		
		if( is_array( $models ) )
		{
			foreach( $models as $info )
			{
				$id = false;
				
				if( is_array( static::$idProperty ) )
				{
					$id = array();
					
					foreach( static::$idProperty as $f )
						$id[] = $info[ $f ];
				}
				else
				{
					$id = $info[ static::$idProperty ];
				}
				
				$model = new $modelName( $id );
				$model->cacheProperties( $info );
				$return[ 'models' ][] = $model;
			}
		}
		
		return $return;
	}

	static function findAll( array $params = array() )
	{
		return new ModelIterator( get_called_class(), $params );
	}
	
	/**
	 * Fetches a single model according to criteria
	 *
	 * @param array $params array( start, limit, sort, search, where )
	 *
	 * @return Model|false
	 */
	static function findOne( array $params )
	{
		$models = static::find( $params );
		
		return ( $models[ 'count' ] > 0 ) ? reset( $models[ 'models' ] ) : false;
	}
	
	/**
	 * Gets the toal number of records matching an optional criteria
	 *
	 * @param array $where criteria
	 *
	 * @return int total
	 */
	static function totalRecords( array $where = array() )
	{
		return (int)Database::select(
			static::tablename(),
			'count(*)',
			array(
				'where' => $where,
				'single' => true ) );
	}

	/////////////////////////////
	// DATABASE SCHEMA
	/////////////////////////////

	static function hasSchema()
	{
		return static::$hasSchema;
	}

	/**
	 * Looks up the current schema for the model
	 *
	 * @return array|false
	 */
	static function currentSchema()
	{
		if( !static::hasSchema() || !static::$config[ 'database' ][ 'enabled' ] )
			return false;

		try
		{
			return Database::listColumns( static::tablename() );
		}
		catch( \Exception $e )
		{
			return false;
		}
	}
	
	/**
	 * Suggests a schema based on the model's properties
	 *
	 * The output of this follows the same format as `SHOW COLUMNS FROM tablename`
	 *
	 * @return array (current, suggested)
	 */
	static function suggestSchema()
	{
		if( !static::hasSchema() )
			return false;

		// get the current schema
		$currentSchema = static::currentSchema();

		$schema = array();

		$different = true; // TODO not implemented
		
		// derive a database column from each property
		foreach( static::$properties as $name => $property )
		{
			if( in_array( $property[ 'type' ], array( 'custom' ) ) )
				continue;
		
			$column = array(
				'Field' => $name,
				'Type' => 'varchar(255)',
				'Null' => (Util::array_value( $property, 'null' )) ? 'YES' : 'NO',
				'Key' => '',
				'Default' => Util::array_value( $property, 'default' ),
				'Extra' => ''
			);
			
			$type = (isset($property['db_type'])) ? $property['db_type'] : null;
			$length = (isset($property['length'])) ? $property['length'] : null;
						
			switch( $property[ 'type' ] )
			{
			case 'id':
				if( !$type ) $type = 'int';
				if( !$length ) $length = 11;
			
				$column[ 'Type' ] = "$type($length)";
			break;
			case 'boolean':
				$column[ 'Type' ] = 'tinyint(1)';
				
				$column[ 'Default' ] = (Util::array_value($property, 'default')) ? '1' : 0;
			break;
			case 'date':
				$column[ 'Type' ] = 'int(11)';
				
				if( !is_numeric( $column[ 'Default' ] ) )
					$column[ 'Default' ] = '';
			break;
			case 'number':
				if( !$type ) $type = 'int';
				
				if ($length)
					$column[ 'Type' ] = "$type($length)";
				else
					$column[ 'Type' ] = "$type";
			break;
			case 'enum':
				if( !$type ) $type = 'varchar';
				if( !$length ) $length = 255;
				
				$column[ 'Type' ] = "$type($length)";
			break;
			case 'longtext':
				if( !$type ) $type = 'text';
				if( !$length ) $length = 65535;
			
				$column[ 'Type' ] = "$type($length)";
				
				$column[ 'Default' ] = '';
			break;
			default:
				if( !$type ) $type = 'varchar';
				if( !$length ) $length = 255;

				$column[ 'Type' ] = "$type($length)";
			break;
			}
			
			if( self::isIdProperty( $name ) )
			{
				$column[ 'Key' ] = 'PRI';
				
				if( !isset( $property[ 'auto_increment' ] ) )
					$property[ 'auto_increment' ] = true;
				
				if( $property[ 'type' ] == 'id' && strtolower( substr( $column[ 'Type' ], 0, 3 ) ) == 'int' && $property[ 'auto_increment' ] )
					$column[ 'Extra' ] = 'auto_increment';
			}

			// does the column exist in the current schema?
			if( $currentSchema )
			{
				foreach( $currentSchema as $c )
				{
					if( $column[ 'Field' ] == $c[ 'Field' ] )
					{
						$column[ 'Exists' ] = true;
						break;
					}
				}
			}
			
			$schema[] = $column;
		}

		// check if there are any extra fields in the current schema
		$extraFields = array();
		if( $currentSchema )
		{
			foreach( $currentSchema as $field )
			{
				$found = false;
				foreach( $schema as $field2 )
				{
					if( $field[ 'Field' ] == $field2[ 'Field' ] )
					{
						$found = true;
						break;
					}
				}
				
				if( !$found )
					$extraFields[] = $field[ 'Field' ];
	
				// TOOD compare schemas to look for differences
			}
		}

		$tablename = static::tablename();
		
		return array(
			'tablename' => $tablename,
			'current' => $currentSchema,
			'currentSql' => Database::schemaToSql( $tablename, $currentSchema, true ),
			'suggested' => $schema,
			'suggestedSql' => Database::schemaToSql( $tablename, $schema, !$currentSchema ),
			'extraFields' => $extraFields,
			'different' => $different );
	}

	/**
	 * Updates a schema
	 *
	 * @param boolean $cleanup when true, extra columns are deleted
	 *
	 * @return boolean success
	 */
	static function updateSchema( $cleanup = false )
	{
		if( !static::hasSchema() )
			return true;

		$sql = '';

		$suggested = static::suggestSchema();

		if( $cleanup )
		{
			$sql = 'ALTER TABLE ' . $suggested[ 'tablename' ];
			
			$drops = array();
			foreach( $suggested[ 'extraFields' ] as $field )
				$drops[] = ' DROP COLUMN ' . $field;
			
			$sql .= implode( ',', $drops ) . ';';
		}
		else
			$sql = $suggested[ 'suggestedSql' ];

		try
		{
			if( static::$config[ 'database' ][ 'enabled' ] )
				return Database::sql( $sql );
		}
		catch( \Exception $e )
		{
			ErrorStack::add( array( 'error' => 'update_schema_error', 'messages' => $e->getMessage() ) );
		}		
	}
	
	/////////////////////////////
	// CACHE
	/////////////////////////////
	
	/**
	 * Loads and caches all of the properties from the database layer
	 * IMPORTANT: this should be called before getting properties
	 * any time a model *might* have been updated from an outside source
	 *
	 * @return void
	 */
	function load()
	{
		if( $this->id === false || !static::$config[ 'database' ][ 'enabled' ] )
			return;
		
		$info = Database::select(
			static::tablename(),
			'*',
			array(
				'where' => $this->id( true ),
				'singleRow' => true ) );
		
		$this->cacheProperties( (array)$info );
	}
		
	/**
	 * Updates the local and shared cache with the new value for a property
	 *
	 * @param string $property property name
	 * @param string $value new value
	 *
	 * @return void
	 */
	function cacheProperty( $property, $value )
	{
		/* Local Cache */
		$this->localCache[ $property ] = $value;

		/* Shared Cache */
		$this->cache()->set( $property, $value );
	}
	
	/**
	 * Cache data inside of the local and shared cache
	 *
	 * @param array $data data to be cached
	 *
	 * @return void
	 */
	function cacheProperties( array $data )
	{
		foreach( $data as $property => $value )
			$this->cacheProperty( $property, $value );
	}
	
	/**
	 * Invalidates a single property in the local and shared caches
	 *
	 * @param string $property property name
	 *
	 * @return void
	 */
	function invalidateCachedProperty( $property )
	{
		if( $property == 'id' )
			return;
		
		/* Local Cache */
		unset( $this->localCache[ $property ] );

		/* Shared Cache */
		$this->cache()->delete( $property );
	}
	
	/**
	 * Invalidates all cached properties for this model
	 *
	 * @return void
	 */
	function emptyCache()
	{
		// explicitly clear all properties and any other values in cache
		$properties = array_unique( array_merge(
			array_keys( static::$properties ),
			array_keys( $this->localCache ) ) );

		foreach( $properties as $property )
			$this->invalidateCachedProperty( $property );
	}

	/////////////////////////////
	// PROTECTED METHODS
	/////////////////////////////

	protected function cache()
	{
		if( !$this->sharedCache )
		{
			// generate caching prefix for this model
			$class = strtolower( str_replace( '\\', '', get_class($this) ) );
			$cachePrefix = $class . '.' . $this->id . '.';
			
			$parameters = (array)static::$config[ 'cache' ][ 'strategies' ];
			$strategies = array_keys( $parameters );

			foreach( $parameters as $strategy => $properties )
			{
				$prefix = Util::array_value( $properties, 'prefix' );
				$parameters[ $strategy ][ 'prefix' ] = ((!empty($prefix))?$prefix.'.':'') . $cachePrefix;
			}
			
			// setup our cache with the appropriate strategies
			$this->sharedCache = new Cache( $strategies, $parameters );
		}

		return $this->sharedCache;
	}

	/////////////////////////////
	// PRIVATE METHODS
	/////////////////////////////

	private function getFromLocalCache( &$properties, &$values )
	{
		foreach( $properties as $property )
		{
			if( array_key_exists( $property, $this->localCache ) )
			{
				$values[ $property ] = $this->localCache[ $property ];

				// remove property from list of remaining
				$index = array_search( $property, $properties );
				unset( $properties[ $index ] );
			}
		}
	}

	private function getFromSharedCache( &$properties, &$values )
	{
		$cached = $this->cache()->get( $properties, true );
		
		foreach( $cached as $property => $value )
		{
			$values[ $property ] = $value;

			// remove property from list of remaining
			$index = array_search( $property, $properties );
			unset( $properties[ $index ] );
		}
	}

	private function getFromDatabase( &$properties, &$values )
	{
		$values = Database::select(
			static::tablename(),
			implode(',', $properties),
			array(
				'where' => $this->id( true ),
				'singleRow' => true ) );

		foreach( (array)$values as $property => $value )
		{
			// escape certain fields
			if( in_array( $property, static::$escapedProperties ) )
				$values[ $property ] = htmlspecialchars( $value );
			
			$values[ $property ] = $value;
			$this->cacheProperty( $property, $value );

			// remove property from list of remaining
			$index = array_search( $property, $properties );
			unset( $properties[ $index ] );
		}
	}

	private function getFromDefaultValues( &$properties, &$values )
	{
		foreach( $properties as $property )
		{
			if( isset( static::$properties[ $property ] ) && isset( static::$properties[ $property ][ 'default' ] ) )
			{
				$values[ $property ] = static::$properties[ $property ][ 'default' ];

				// remove property from list of remaining
				$index = array_search( $property, $properties );
				unset( $properties[ $index ] );
			}
		}
	}

	private function validate( $property, $field, $value )
	{
		$valid = true;

		if( is_callable( $property[ 'validate' ] ) )
		{
			if( !call_user_func_array( $property[ 'validate' ], array( &$value ) ) )
				$valid = false;
		}
		else if( !Validate::is( $value, $property[ 'validate' ] ) )
			$valid = false;
		
		if( !$valid )
			ErrorStack::add( array(
				'error' => VALIDATION_FAILED,
				'params' => array(
					'field' => $field,
					'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );

		return $valid;
	}

	private function checkUniqueness( $property, $field, $value )
	{
		if( static::totalRecords( array( $field => $value ) ) > 0 )
		{
			ErrorStack::add( array(
				'error' => VALIDATION_NOT_UNIQUE,
				'params' => array(
					'field' => $field,
					'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );
			
			return false;
		}

		return true;
	}
}