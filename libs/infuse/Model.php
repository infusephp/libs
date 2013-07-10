<?php
/**
 * Base class for models
 * 
 * @package Infuse
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 1.0
 * @copyright 2013 Jared King
 * @license MIT
	Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
	associated documentation files (the "Software"), to deal in the Software without restriction,
	including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense,
	and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so,
	subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in all copies or
	substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT
	LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
	IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
	SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
/**
 *
 * The properties array looks like this:
 	'name' => array(
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
	  	id:
	  		The type of property when the property type = id
	  		String
	  		Default: int
	  		Optional
	  	number:
	  		The type of number when the property type = number
	  		String
	  		Default: int
	  		Required if specifying number type
  		enum:
  			A key-value map of acceptable values for the enum type.
  			Array
  			Required if specifying enum type
  		enumType:
  			Type of the database column for the enum
  			Default: varchar
  			Required if specifying enum type
  		length:
  			Overrides the default maximum length of the column values in the database. Use this when a different value is needed besides the one specified
  			Integer|String
  			Default: Chosen according to type
  			Optional
  		null:
  			Specifies whether the column is allowed to have null values.
  			Boolean
  			Default: false
  			Optional
  		filter:
  			An HTML string that will have values from the model injected. Only used in the admin panel.
  			String
  			Example: <a href="/users/profile/{uid}">{username}</a>
  			Optional
 		required:
 			Specifies whether the field is required
 			Boolean
 			Default: false
 			Optional
 		validation:
 			Function reference to validate the input of the field (i.e. user creation, editing a user), returns true if valid.
 			The function should look like: function validate_email( &$property_value, $parameters )
 			The validation function is allowed to modify the property value
 			Array
 			Optional
 		validation_params:
 			An array of extra parameters to pass to the validation function. Comes through the second argument in an array.
 			Array
 			Default: null
 			Optional
  		nosort:
  			Prevents the column from being sortable in the admin panel.
  			Boolean
  			Default: false
  			Optional
  		nowrap:
  			Prevents the column from wrapping in the admin panel.
  			Boolean
  			Default: false
  			Optional
  		truncate:
  			Prevents the column from truncating values in the admin panel.
  			Boolean
  			Default: true
  			Optional
  		title:
  			Title of the property that shows up in admin panel
  			String
  			Default: Derived from property name
  			Optional
  		autoincrement:
  			Auto increments the property when creating new models within the schema
  			Boolean
  			Default: true if property type is id, otherwise false
  			Optional
  	)
 *
 *
 * The model looks for data in this order Local Cache -> Memcache (if enabled) -> Database
 *
 * The local cache is just a static array laid out as follows:
 	<class_name> : array(
 		<id> : array(
 			<property_name> : <value>
 			<property_name> : <value>
 		)
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

	/////////////////////////////
	// Private class variables
	/////////////////////////////

	private static $excludePropertyTypes = array( 'custom', 'html' );

	// memcache
	private static $memcache;
	private static $memcacheConnectionAttempted;
	public $memcachePrefix;

	// local cache
	private static $globalCache = array(); // used 
	private $localCache = array();

	private $cacheInitialized = false;
	
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
	
	private function setupCache()
	{
		if( $this->cacheInitialized )
			return;
		
		// generate keys for caching this model
		$class = str_replace( '\\', '', get_class($this) );
		
		// initialize memcache if enabled
		if( class_exists('Memcache') && Config::value( 'memcache', 'enabled' ) )
		{
			$this->memcachePrefix = Config::value( 'memcache', 'prefix' ) . '-' . $class . '-' . $this->id . '-';

			// attempt to connect to memcache
			try
			{
				if( !self::$memcache && !self::$memcacheConnectionAttempted )
				{
					self::$memcache = new \Memcache;
					
					self::$memcache->connect( Config::value( 'memcache', 'host' ), Config::value( 'memcache', 'port' ) ) or (self::$memcache = false);
					
					self::$memcacheConnectionAttempted = true;
				}				
			}
			catch(\Exception $e)
			{
				self::$memcache = false;
			}
		}

		// fallback to local cache if no memcache
		if( !self::$memcache )
		{
			if( !isset( self::$globalCache[ $class ] ) )
				self::$globalCache[ $class ] = array();
			
			if( !isset( self::$globalCache[ $class ][ $this->id ] ) )
				self::$globalCache[ $class ][ $this->id ] = array();
			
			$this->localCache =& self::$globalCache[ $class ][ $this->id ];
		}

		$this->cacheInitialized = true;
	}
		
	/////////////////////////////
	// GETTERS
	/////////////////////////////

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
	 * @param string|array $whichProperties columns
	 *
	 * @return array|string|null requested info or not found
	 */
	function get( $whichProperties )
	{
		$this->setupCache();
	
		$properties = (is_string( $whichProperties )) ? explode(',', $whichProperties) : (array)$whichProperties;

		$return = array();

		// look to memcache first
		if( self::$memcache )
		{
			$mKeys = array_map( function ($str) { return $this->memcachePrefix . $str; }, $properties );
			
			$cache = self::$memcache->get( $mKeys );

			foreach( $cache as $property => $value )
			{
				// strip memcache prefix
				$property = str_replace( $this->memcachePrefix, '', $property );
			
				// remove from property search
				$k = array_search( $property, $properties );
				if( $k )
					unset( $properties[ $k ] );
				
				// add to return
				$return[ $property ] = $value;
			}
		}
		// fallback to local cache
		else
		{
			foreach( $properties as $key => $property )
			{
				if( isset( $this->localCache[ $property ] ) )
				{
					// remove from property search
					unset( $properties[ $key ] );

					// add to return
					$return[ $property ] = $this->localCache[ $property ];
				}
			}
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
	 * Gets the stats inside of the cache
	 *
	 * @return array memcache statistics
	 */	
	static function getCacheStats()
	{
		$this->setupCache();
	
		return (self::$memcache) ? self::$memcache->getStats() : false;
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
			$where = (array)val( $start, 'where' );
			$search = val( $start, 'search' );
			$sort = val( $start, 'sort' );
			$limit = val( $start, 'limit' );
			$start = val( $start, 'start' ); // must be last
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
				if( !in_array( val( $property, 'type' ), self::$excludePropertyTypes ) )
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
	
	/**
	 * Suggests a schema given the model's properties
	 *
	 * The output of this follows the same format as Database::listColumns( 'tablename' )
	 *
	 * @param array $currentSchema current schema
	 *
	 * @return array
	 */
	static function suggestSchema( $currentSchema )
	{
		$schema = array();
		
		foreach( static::$properties as $name => $property )
		{
			if( in_array( $property[ 'type' ], array( 'custom' ) ) )
				continue;
		
			$column = array(
				'Field' => $name,
				'Type' => 'varchar(255)',
				'Null' => (val( $property, 'null' )) ? 'YES' : 'NO',
				'Key' => '',
				'Default' => val( $property, 'default' ),
				'Extra' => ''
			);
						
			switch( $property[ 'type' ] )
			{
			case 'id':
				$type = (isset($property['id'])) ? $property['id'] : 'int';
				$length = (isset($property['length'])) ? $property['length'] : 11;
			
				$column[ 'Type' ] = "$type($length)";
			break;
			case 'boolean':
				$column[ 'Type' ] = 'tinyint(1)';
				
				$column[ 'Default' ] = (val($property, 'default')) ? '1' : 0;
			break;
			case 'date':
				$column[ 'Type' ] = 'int(11)';
			break;
			case 'number':
				$type = (isset($property['number'])) ? $property['number'] : 'int';
				$length = (isset($property['length'])) ? $property['length'] : 11;
				
				$column[ 'Type' ] = "$type($length)";
			break;
			case 'enum':
				$type = (isset($property['enumType'])) ? $property['enumType'] : 'varchar';
				$length = (isset($property['length'])) ? $property['length'] : 255;
				
				$column[ 'Type' ] = "$type($length)";
			break;
			case 'longtext':
				$length = (isset($property['length'])) ? $property['length'] : 65535;
			
				$column[ 'Type' ] = "text($length)";
			break;
			default:
				$length = (isset($property['length'])) ? $property['length'] : 255;
				$column[ 'Type' ] = "varchar($length)";
			break;
			}
			
			if( self::isIdProperty( $name ) )
			{
				$column[ 'Key' ] = 'PRI';
				
				if( !isset( $property[ 'autoincrement' ] ) )
					$property[ 'autoincrement' ] = true;
				
				if( $property[ 'type' ] == 'id' && strtolower( substr( $column[ 'Type' ], 0, 3 ) ) == 'int' && $property[ 'autoincrement' ] )
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

		return $schema;
	}

	/**
	 * Converts a schema into SQL statements
	 *
	 * @param array $schema
	 * @param boolean $newTable true if a new table should be created
	 *
	 * @return string sql
	 */
	static function schemaToSql( $schema, $newTable = true )
	{
		if( count( $schema ) == 0 )
			return false;

		$sql = '';

		$tablename = static::tablename();

		if( $newTable )
			$sql .= "CREATE TABLE IF NOT EXISTS `$tablename` (\n";
		else
			$sql .= "ALTER TABLE `$tablename`\n";

		$primaryKeys = array();

		$cols = array();
		foreach( $schema as $column )
		{
			$col = "\t";

			if( !$newTable )
				$col .= ( val( $column, 'Exists' ) ) ? 'MODIFY ' : 'ADD ';

			$col .= "`{$column['Field']}` {$column['Type']} ";

			$col .= ( strtolower( $column['Null'] ) == 'yes' ) ? 'NULL' : 'NOT NULL';
			
			if( $column['Default'] )
				$col .= " DEFAULT '{$column['Default']}'";

			if( $column['Extra'] )
				$col .= " {$column['Extra']}";

			if( $column['Key'] )
			{
				if( $column['Key'] == 'PRI' )
					$primaryKeys[] = $column[ 'Field' ];
				else if( $newTable )
					$col .= ' ' . $column['Key'];
			}

			$cols[] = $col;
		}

		// TODO
		// index
		// unique index
		
		// primary key
		if( $newTable )
		{
			$cols[] = "\t" . 'PRIMARY KEY(' . implode( ',', $primaryKeys ) . ')';		
		}
		else
		{
			$cols[] = "\t" . 'DROP PRIMARY KEY';
			$cols[] = "\t" . 'ADD PRIMARY KEY(' . implode( ',', $primaryKeys ) . ')';
		}

		$sql .= implode( ",\n", $cols);

		if( $newTable )
			$sql .= "\n) ;";
		else
			$sql .= "\n ;";

		return $sql;
	}
	
	/////////////////////////////
	// SETTERS
	/////////////////////////////
	
	/**
	 * Loads and cahces all of the properties from the model inside of the database table
	 *
	 * @return null
	 */
	function loadProperties()
	{
		if( $this->hasNoId() )
			return;
				
		$info = Database::select(
			static::tablename(),
			'*',
			array(
				'where' => $this->id( true ),
				'singleRow' => true ) );
		
		foreach( (array)$info as $property => $item )
			$this->cacheProperty( $property, $item );
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
		$this->setupCache();
	
		// cache in memcache
		if( self::$memcache )
			self::$memcache->set( $this->memcachePrefix . $property, $value );
		
		// cache locally
		else
			$this->localCache[ $property ] = $value;
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
		$this->setupCache();
		
		// use memcache
		if( self::$memcache )
			self::$memcache->delete( $this->memcachePrefix . $property );
		
		// fallback to local cache
		else
			unset( $this->localCache[ $property ] );
	}
	
	/**
	 * Clears the local cache
	 *
	 * @return null
	 */
	function clearCache()
	{
		$this->localCache = array();
		$this->cacheInitialized = false;
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
		ErrorStack::setContext( 'create' );

		$modelName = get_called_class();
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
			if( val( $property, 'required' ) )
				$requiredProperties[] = $name;
		}
		
		// loop through each supplied field and validate, if setup
		$insertArray = array();
		foreach( $data as $field => $field_info )
		{
			if( in_array( $field, $propertyNames ) )
				$value = $data[ $field ];
			else
				continue;

			$property = static::$properties[ $field ];

			// cannot insert keys, unless explicitly allowed
			if( self::isIdProperty( $field ) && !val( $property, 'canSetKey' ) )
				continue;
			
			if( is_array( $property ) )
			{
				// null value
				if( val( $property, 'null' ) && empty( $value ) )
				{
					$updateArray[ $field ] = null;
					continue;
				}
				
				// validate
				if( is_callable( val( $property, 'validation' ) ) )
				{
					$parameters = array();
					if( is_array( val( $property, 'validation_params' ) ) )
						$parameters = array_merge( $parameters, $property[ 'validation_params' ] );
					
					$args = array( &$value, $parameters );
					
					if( !call_user_func_array( $property[ 'validation' ], $args ) )
					{
						ErrorStack::add( array(
							'error' => VALIDATION_FAILED,
							'params' => array(
								'field' => $field,
								'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );
						
						$validated = false;
					}
				}
				
				// check for uniqueness
				if( val( $property, 'unique' ) )
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
					
						$validated = false;
					}
				}
				
				$insertArray[ $field ] = $value;
			}			
		}
		
		// add in default values
		foreach( static::$properties as $name => $fieldInfo )
		{
			if( isset( $fieldInfo[ 'default' ] ) && !isset( $insertArray[ $name ] ) ) {
				$insertArray[ $name ] = $fieldInfo[ 'default' ];
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
		ErrorStack::setContext( 'edit' );
	
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
				if( val( $property, 'null' ) && empty( $value ) )
				{
					$updateArray[ $field ] = null;
					continue;
				}

				if( is_callable( val( $property, 'validation' ) ) )
				{
					$parameters = array( 'model' => $this );
					if( is_array( val( $property, 'validation_params' ) ) )
						$parameters = array_merge( $parameters, $property[ 'validation_params' ] );
					
					$args = array( &$value, $parameters );
					
					if( call_user_func_array( $property[ 'validation' ], $args ) )
						$updateArray[ $field ] = $value;
					else
					{
						ErrorStack::add( array(
							'error' => VALIDATION_FAILED,
							'params' => array(
								'field' => $field,
								'field_name' => (isset($property['title'])) ? $property[ 'title' ] : Inflector::humanize( $field ) ) ) );

						$validated = false;
					}
				}
				
				if( val( $property, 'unique' ) && $value != $this->get( $field ) )
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
					
						$validated = false;
					}
				}
				
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
			// update the local cache
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
		ErrorStack::setContext( 'delete' );
		
		// permission?
		if( !$this->can( 'delete' ) )
		{
			ErrorStack::add( ERROR_NO_PERMISSION );
			return false;
		}
		
		// pre-hook
		if( !$this->preRemoveHook() )
			return false;
		
		// delete the model
		if( Database::delete(
			static::tablename(),
			$this->id( true ) ) )
		{
			// post-hook
			$this->postRemoveHook();
			
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
	
	protected function preRemoveHook()
	{ return true; }
	
	protected function postRemoveHook()
	{ }
}