<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.15.3
 * @copyright 2013 Jared King
 * @license MIT
 */

/**
 *
 * The following properties are available:
 
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
  		enum:
  			A key-value map of acceptable values if property type is `enum`
  			Array
  			Required if specifying `enum` type
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
  			
  	Admin Dashboard Settings:
  		
  		title:
  			Title of the property that shows up in admin panel
  			String
  			Default: Derived from property `name`
  			Optional
  		filter:
  			An HTML string that will have values from the model injected. Only used in the admin dashboard.
  			String
  			Example: <a href="/users/profile/{uid}">{username}</a>
  			Optional
  		no_sort:
  			Prevents the column from being sortable in the admin dashboard
  			Boolean
  			Default: false
  			Optional
  		no_wrap:
  			Prevents the column from wrapping in the admin dashboard
  			Boolean
  			Default: false
  			Optional
  		truncate:
  			Prevents the column from truncating values in the admin dashboard
  			Boolean
  			Default: true
  			Optional
  	)
 *
 *
 * The model caching strategies are executed in this order:
 	- Memcache (if enabled)
 	- Local
 *
 *
 */

namespace infuse;

abstract class Model extends Acl
{
	/////////////////////////////
	// Model properties
	/////////////////////////////

	public static $properties = array();
	public static $idProperty = 'id';

	/////////////////////////////
	// Protected class variables
	/////////////////////////////

	protected static $escapedProperties = array(); // specifies fields that should be escaped with htmlspecialchars()
	protected static $tablename = false;
	protected static $hasSchema = true;

	/////////////////////////////
	// Private class variables
	/////////////////////////////

	private static $excludePropertyTypes = array( 'custom', 'html' );
	
	private $cache;

	/**
	 * Creates a new model object
	 *
	 * @param array|string $id ordered array of ids or comma-separated id string
	 */
	public function __construct( $id = false )
	{
		if( $id )
			$this->id = implode( ',', (array)$id );
	}
	
	private function cache()
	{
		if( !$this->cache )
		{
			// generate caching prefix for this model
			$class = strtolower( str_replace( '\\', '', get_class($this) ) );		
			$cachePrefix = $class . '.' . $this->id . '.';
	
			$strategies = array();
			
			// memcache strategy
			if( Config::get( 'memcache', 'enabled' ) )
			{
				$strategies[] = 'memcache';
				$parameters[ 'memcache' ] = array_replace(
					Config::get( 'memcache' ),
					array( 'prefix' => Config::get( 'memcache', 'prefix' ) . '.' . $cachePrefix ) );
			}
			
			// local strategy fallback
			$strategies[] = 'local';
			$parameters[ 'local' ] = array( 'prefix' => $cachePrefix );
			
			// setup our cache with the appropriate strategies
			$this->cache = new Cache( $strategies, $parameters );
		}

		return $this->cache;
	}
		
	/////////////////////////////
	// GETTERS
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
	 * Gets some basic info about the model
	 *
	 * @return array
	 */
	static function info()
	{
		$class_name = get_called_class();
		
		// strip namespacing
		$paths = explode( '\\', $class_name );
		$modelName = end( $paths );
				
		$singularKey = Inflector::underscore( $modelName );
		$pluralKey = Inflector::pluralize( $singularKey );

		return array(
			'model' => $modelName,
			'class_name' => $class_name,
			'singular_key' => $singularKey,
			'plural_key' => $pluralKey,
			'proper_name' => Inflector::humanize( $singularKey ),
			'proper_name_plural' => Inflector::humanize( $pluralKey ) );
	}	

	/**
	 * Gets the tablename for the model
	 *
	 * @return string
	 */
	static function tablename()
	{
		// get model name
		$modelClassName = get_called_class();
		
		// strip namespacing
		$paths = explode( '\\', $modelClassName );
		$modelName = end( $paths );
		
		// pluralize and camelize model name
		return Inflector::camelize( Inflector::pluralize( $modelName ) );
	}
	
	/**
	 * Checks if the model has not been supplied with an id
	 *
	 * @return boolean
	 */
	function hasNoId()
	{
		return $this->id !== ACL_NO_ID;
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
	 * Fetches properties from the model. If caching is enabled, then look there first. When
	 * properties are not found in the cache then it will fall through to the Database layer.
	 *
	 * @param string|array $properties
	 *
	 * @return array|string|null requested info or not found
	 */
	function get( $properties )
	{
		$properties = (is_string( $properties )) ? explode(',', $properties) : (array)$properties;

		$return = array();

		// look up values in cache
		$cached = $this->cache()->get( $properties, true );
		
		foreach( $cached as $property => $value )
		{
			// do not hit the database for cached values by removing key from search
			$index = array_search( $property, $properties );
			unset( $properties[ $index ] );
			
			$return[ $property ] = $value;
		}

		// find remaining values in database
		if( count( $return ) < count( $properties ) )
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
				
				$return[ $property ] = $value;
				$this->cacheProperty( $property, $value );
			}
		}

		return ( count( $return ) == 1 ) ? reset( $return ) : $return;
	}
	
	/**
	 * @deprecated
	 */
	function getProperty( $whichProperties )
	{
		return $this->get( $whichProperties );
	}
	
	/**
	 * Checks if the model has a property.
	 *
	 * @param string $property property
	 *
	 * @return boolean has property
	 */
	static function hasProperty( $property )
	{
		return isset( static::$properties[ $property ] );
	}
	
	/**
	 * Converts the modelt to an array
	 *
	 * @param array $exclude properties to exclude
	 *
	 * @return array properties
	 */
	function toArray( $exclude = array() )
	{
		$properties = array();
		
		// get the names of all the properties
		foreach( static::$properties as $name => $property )
		{
			if( !empty( $name ) && !in_array( $name, $exclude ) && !in_array( $property[ 'type' ], self::$excludePropertyTypes ) )
				$properties[] = $name;
		}
				
		// get the values of all the properties
		return array_replace( (array)$this->get( $properties ), $this->id( true ) );
	}
	
	/**
	 * Converts the object to JSON format
	 *
	 * @param array $exclude properties to exclude
	 *
	 * @return string json
	 */
	function toJson( $exclude = array() )
	{
		return json_encode( $this->toArray( $exclude ) );
	}
	
	/**
	 * Fetches models with pagination support
	 *
	 * @param array key-value parameters
	 *
	 * @param int $start record number to start at
	 * @param int $limit max results to return
	 * @param string $sort sort (i.e. name asc, year asc)
	 * @param string $search search query
	 * @param array $where criteria
	 *
	 * @return array array( 'models' => models, 'count' => 'total found' )
	 */ 
	static function find( $start = 0, $limit = 100, $sort = '', $search = '', $where = array() )
	{
		// unpack parameters
		if( is_array( $start ) )
		{
			$where = (array)Util::array_value( $start, 'where' );
			$search = Util::array_value( $start, 'search' );
			$sort = Util::array_value( $start, 'sort' );
			$limit = Util::array_value( $start, 'limit' );
			$start = Util::array_value( $start, 'start' ); // must be last
		}
	
		if( empty( $start ) || !is_numeric( $start ) || $start < 0 )
			$start = 0;
		if( empty( $limit ) || !is_numeric( $limit ) || $limit > 1000 )
			$limit = 100;

		$modelName = get_called_class();
		
		$return = array('models'=>array());
		
		// WARNING: using MYSQL LIKE for search, this is very inefficient
		
		if( !empty( $search ) )
		{
			$w = array();
			foreach( static::$properties as $name => $property )
			{
				if( !in_array( Util::array_value( $property, 'type' ), self::$excludePropertyTypes ) )
					$w[] = "$name LIKE '%$search%'";
			}
			
			$where[] = '(' . implode( ' OR ', $w ) . ')';
		}

		// verify sort		
		$sortParams = array();

		$columns = explode( ',', $sort );
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
		
		$count = (int)Database::select(
			static::tablename(),
			'count(*)',
			array(
				'where' => $where,
				'single' => true ) );
		
		$return['count'] = $count;
		
		$filter = array(
			'where' => $where,
			'limit' => "$start,$limit" );
		
		$sortStr = implode( ',', $sortParams );
		if( $sortStr )
			$filter[ 'orderBy' ] = $sortStr;

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
				$return['models'][] = $model;
			}
		}
		
		return $return;
	}
	
	/**
	 * Fetches a single model according to criteria
	 *
	 * @param array $params array( start, limit, sort, search, where )
	 *
	 * @return Model|false
	 */
	static function findOne( $params = array() )
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
	static function totalRecords( $where = array() )
	{
		return (int)Database::select(
			static::tablename(),
			'count(*)',
			array(
				'where' => $where,
				'single' => true ) );
	}
	
	/**
	 * Checks if the model exists in the database
	 *
	 * @return boolean
	 */
	function exists()
	{
		return Database::select(
			static::tablename(),
			'count(*)',
			array(
				'where' => $this->id( true ),
				'single' => true ) ) == 1;
	}

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
		if( !static::hasSchema() )
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
	 * The output of this follows the same format as Database::listColumns( 'tablename' )
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
				if( !$length ) $length = 11;
				
				$column[ 'Type' ] = "$type($length)";
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
			return Database::sql( $sql );
		}
		catch( \Exception $e )
		{
			ErrorStack::add( array( 'error' => 'update_schema_error', 'messages' => $e->getMessage() ) );
		}		
	}
	
	/////////////////////////////
	// SETTERS
	/////////////////////////////
	
	/**
	 * Loads and cahces all of the properties from the model inside of the database table
	 *
	 * @return null
	 */
	function load()
	{
		if( $this->hasNoId() )
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
	 * @deprecated see load()
	 */
	function loadProperties()
	{
		$this->load();
	}
	
	/**
	 * Updates the cache with the new value for a property
	 *
	 * @param string $property property name
	 * @param string $value new value
	 *
	 * @return null
	 */
	function cacheProperty( $property, $value )
	{
		$this->cache()->set( $property, $value );
	}
	
	/**
	 * Cache data inside of the model cache
	 *
	 * @param array $data data to be cached
	 *
	 * @return null
	 */
	function cacheProperties( $data )
	{
		foreach( (array)$data as $property => $value )
			$this->cacheProperty( $property, $value );
	}
	
	/**
	 * Invalidates a single property in the cache
	 *
	 * @param string $property property name
	 *
	 * @return null
	 */
	function invalidateCachedProperty( $property )
	{
		$this->cache()->delete( $property );
	}
	
	/**
	 * Invalidates all cached properties for this model
	 *
	 * @return null
	 */
	function emptyCache()
	{
		foreach( static::$properties as $property => $info )
			$this->invalidateCachedProperty( $property );
	}
	
	/**
	 * Creates a new model
	 *
	 * @param array $data key-value properties
	 *
	 * @return boolean
	 */
	static function create( $data )
	{
		$modelName = get_called_class();
		$modelNameLocal = str_replace( 'infuse\\models\\', '', $modelName );

		ErrorStack::setContext( strtolower( $modelNameLocal ) . '.create' );

		$model = new $modelName();
		
		// permission?
		if( !$model->can( 'create' ) )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}

		// pre-hook
		if( !$model->preCreateHook( $data ) )
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
		foreach( $data as $field => $field_info )
		{
			if( in_array( $field, $propertyNames ) )
				$value = $data[ $field ];
			else
				continue;

			$property = static::$properties[ $field ];

			// cannot insert keys, unless explicitly allowed
			if( self::isIdProperty( $field ) && !Util::array_value( $property, 'mutable' ) )
				continue;
			
			if( is_array( $property ) )
			{
				// null value
				if( Util::array_value( $property, 'null' ) && empty( $value ) )
				{
					$updateArray[ $field ] = null;
					continue;
				}
				
				$thisIsValid = true;
				
				// validate
				if( isset( $property[ 'validate' ] ) )
				{
					if( is_callable( $property[ 'validate' ] ) )
					{
						if( !call_user_func_array( $property[ 'validate' ], array( &$value ) ) )
							$thisIsValid = false;
					}
					else if( !Validate::is( $value, $property[ 'validate' ] ) )
						$thisIsValid = false;
					
					if( !$thisIsValid )
						ErrorStack::add( array(
							'error' => VALIDATION_FAILED,
							'params' => array(
								'field' => $field,
								'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );
				}
				
				// check for uniqueness
				if( $thisIsValid && Util::array_value( $property, 'unique' ) )
				{
					if( Database::select(
						static::tablename(),
						'count(*)',
						array(
							'where' => array(
								$field => $value ),
							'single' => true ) ) > 0 )
					{
						ErrorStack::add( array(
							'error' => VALIDATION_NOT_UNIQUE,
							'params' => array(
								'field' => $field,
								'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );					
					
						$thisIsValid = false;
					}
				}
				
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

		if( Database::insert(
			static::tablename(),
			$insertArray ) )
		{
			// create new model
			$newModel = new $modelName(Database::lastInsertID());
			
			// cache
			$newModel->cacheProperties( $insertArray );
			
			// post-hook
			$newModel->postCreateHook();
			
			return $newModel;
		}
		
		return false;
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
		$modelNameLocal = str_replace( 'infuse\\models\\', '', $modelName );

		ErrorStack::setContext( strtolower( $modelNameLocal ) . '.set' );
	
		// permission?
		if( !$this->can( 'edit' ) )
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
		if( !$this->preSetHook( $data ) )
			return false;

		$validated = true;
		$updateArray = $this->id( true );
		$updateKeys = array_keys( $updateArray );
		
		// get the property names
		$propertyNames = array();
		foreach( static::$properties as $name => $property )
		{
			if( empty( $name ) )
				continue;
			$propertyNames[] = $name;
		}
		
		// loop through each supplied field and validate, if setup
		foreach ($data as $field => $field_info)
		{
			// cannot change keys
			if( in_array( $field, $updateKeys ) )
				continue;
		
			if( in_array( $field, $propertyNames ) )
				$value = $data[ $field ];
			else
				continue;

			$property = static::$properties[ $field ];

			if( is_array( $property ) )
			{
				// null values
				if( Util::array_value( $property, 'null' ) && empty( $value ) )
				{
					$updateArray[ $field ] = null;
					continue;
				}

				$thisIsValid = true;
				
				// validate
				if( isset( $property[ 'validate' ] ) )
				{
					if( is_callable( $property[ 'validate' ] ) )
					{
						if( !call_user_func_array( $property[ 'validate' ], array( &$value ) ) )
							$thisIsValid = false;
					}
					else if( !Validate::is( $value, $property[ 'validate' ] ) )
						$thisIsValid = false;
					
					if( !$thisIsValid )
						ErrorStack::add( array(
							'error' => VALIDATION_FAILED,
							'params' => array(
								'field' => $field,
								'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );
				}
				
				// check for uniqueness
				if( $thisIsValid && Util::array_value( $property, 'unique' ) && $value != $this->get( $field ) )
				{
					if( Database::select(
						static::tablename(),
						'count(*)',
						array(
							'where' => array(
								$field => $value ),
							'single' => true ) ) > 0 )
					{
						ErrorStack::add( array(
							'error' => VALIDATION_NOT_UNIQUE,
							'params' => array(
								'field' => $field,
								'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );					
					
						$thisIsValid = false;
					}
				}
				
				$validated = $validated && $thisIsValid;
				
				$updateArray[ $field ] = $value;
			}
		}

		if( !$validated )
			return false;

		if( Database::update(
			static::tablename(),
			$updateArray,
			$updateKeys ) )
		{
			// update the cache with our new values
			$this->cacheProperties( $updateArray );
				
			// post-hook
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
		$modelNameLocal = str_replace( 'infuse\\models\\', '', $modelName );

		ErrorStack::setContext( strtolower( $modelNameLocal ) . '.delete' );

		// permission?
		if( !$this->can( 'delete' ) )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}
		
		// pre-hook
		if( !$this->preDeleteHook() )
			return false;
		
		// delete the model
		if( Database::delete(
			static::tablename(),
			$this->id( true ) ) )
		{
			// post-hook
			$this->postDeleteHook();
			
			return true;
		}
		else
			return false;
	}
	
	/////////////////////////////
	// HOOKS
	/////////////////////////////
	
	protected function preCreateHook( &$data )
	{ return true; }
	
	protected function postCreateHook()
	{ }
	
	protected function preSetHook( &$data )
	{ return true; }
	
	protected function postSetHook()
	{ }
	
	protected function preDeleteHook()
	{ return true; }
	
	protected function postDeleteHook()
	{ }
}