<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.20
 * @copyright 2013 Jared King
 * @license MIT
 */
 
namespace infuse;
 
class Database
{
	/////////////////////////////
	// Private class variables
	/////////////////////////////
	
	private static $config = array(
		'type' => '',
		'host' => '',
		'name' => '',
		'user' => '',
		'password' => '',
		'productionLevel' => false
	);
	
	private static $DBH;
	private static $numrows;
	private static $queryCount;
	private static $batch = false;
	private static $batchQueue;
	private static $initializeAttempted;
	
	/**
	 * Sets up the settings used to interact with database
	 *
	 * @param array $config
	 */
	static function configure( $config )
	{
		self::$config = array_replace( self::$config, (array)$config );

		self::$initializeAttempted = false;
		self::$DBH = null;
	}

	/**
	* Initializes the connection with the database. Only needs to be called once.
	*
	* @return boolean true if successful
	*/
	static function initialize()
	{
		if( self::$initializeAttempted )
			return self::$DBH instanceof \PDO;
		
		self::$initializeAttempted = true;
		
		try
		{
			// Initialize database
			if( self::$DBH == null )
			{
				$dsn = '';

				if( strpos( self::$config[ 'type' ], 'sqlite' ) === 0 )
					// i.e. sqlite:memory:
					$dsn = self::$config[ 'type' ] . ':' . self::$config[ 'host' ];
				else
					// i.e. mysql:host=localhost;dbname=test
					$dsn = self::$config[ 'type' ] . ':host=' . self::$config[ 'host' ] . ';dbname=' . self::$config[ 'name' ];

				self::$DBH = new \PDO( $dsn, Util::array_value( self::$config, 'user' ), Util::array_value( self::$config, 'password' ) );
			}
		}
		catch(PDOException $e)
		{
			Logger::alert( Logger::formatException( $e ) );
			die( 'Could not connect to database.' );
			return false;
		}
		
		// Set error level
		if( self::$config[ 'productionLevel' ] )
			self::$DBH->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING );
		else
			self::$DBH->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		
		// Set counters
		self::$queryCount = array(
			'select' => 0,
			'sql' => 0,
			'insert' => 0,
			'update' => 0,
			'delete' => 0
		);
				
		return true;
	}

	/**
	 * Gets the type of database we are connecting to
	 *
	 * @return string
	 */
	static function type()
	{
		return Util::array_value( self::$config, 'type' );
	}
	
	/**
	* Generates and executes a select query.
	*
	* Parameters:
	* <ul>
	* <li>where: Array of where parameters. Key => value translates into key = value. If no key is supplied then the value is treated as its own parameter.
	* <code>'where' => array( 'first_name' => 'John', 'last_name' => 'Doe', 'created > 10405833' )</code></li>
	* <li>single: returns a single value</li>
	* <li>singleRow: returns a single row</li>
	* <li>fetchStyle: see PDO manual</li>
	* <li>join</li>
	* <li>orderBy</li>
	* <li>groupBy</li>
	* </ul>
	*
	* @param string $tableName table name
	* @param string $fields fields, comma-seperated
	* @param array $parameters parameters
	* @param boolean $showQuery echoes the generated query if true
	*
	* @return boolean success?
	*/
	static function select( $tableName, $fields, $parameters = array(), $showQuery = false )
	{
		if( !self::initialize() )
			return false;
	
		if( isset( $parameters[ 'single' ] ) && $parameters[ 'single' ] )
		{
			$parameters[ 'singleRow' ] = true;
			$parameters[ 'fetchStyle' ] = 'singleColumn';
		}
		
		$where = null;
		$where_other = array(); // array of parameters which do not contain an equal sign or is too complex for our implode function
		
		// store the original where parameters
		$originalWhere = array();
		
		if( isset( $parameters[ 'where' ] ) )
		{
			$originalWhere = $parameters[ 'where' ];
			
			if( is_string( $parameters[ 'where' ] ) )
			{ // deprecating building where strings, use named parameters instead
				$where = ' WHERE ' . $parameters['where'];
				exit( "Deprecated: $where" );
			}
			else
			{ // use named parameters, its safer
				foreach( (array)$parameters['where'] as $key=>$value )
				{
					if( is_numeric( $key ) )
					{ // should not be parameterized
						if( $value != '' )
							$where_other[] = $value;
							
						unset( $parameters['where'][$key] );
					}
				}
				
				$where_arr = array();
				$where_other_implode = implode(' AND ', $where_other );
				if( $where_other_implode  != '' ) // add to where clause
					$where_arr[] = $where_other_implode;
				
				$where_parameterized = implode(' AND ', array_map(create_function('$key, $value', 'return $key.\' = :\'.str_replace(".","",$key);'), array_keys($parameters['where']), array_values($parameters['where'])) );
				foreach( (array)$parameters['where'] as $parameter=>$value )
				{ // strip periods from named parameters, MySQL does not like this
					unset($parameters['where'][$parameter]);
					$parameters['where'][str_replace('.','',$parameter)] = $value;
				}

				if( $where_parameterized != '' )
					$where_arr[] = $where_parameterized;
					
				if( count( $where_arr ) > 0 )
					$where = ' WHERE ' . implode(' AND ', $where_arr );
			}
		}
		else
			$parameters[ 'where' ] = null;

		if( isset( $parameters[ 'join' ] ) ) // joins cannot be included in where due to the use of named parameters
			$where .= (( strlen( $where) > 0 ) ? ' AND ' : '' ) . $parameters[ 'join' ];
			
		$orderBy = null;
		if( isset($parameters['orderBy']) )
			$orderBy = ' ORDER BY ' . $parameters['orderBy'];
			
		$groupBy = null;
		if( isset($parameters['groupBy']) )
			$groupBy = ' GROUP BY ' . $parameters['groupBy'];
			
		$limit = null;
		if( isset($parameters['limit']) )
			$limit = ' LIMIT ' . $parameters['limit'];
			
		$fetchStyle = \PDO::FETCH_ASSOC;
		if( isset($parameters['fetchStyle']) )
		{
			switch( $parameters['fetchStyle'] )
			{
				case 'assoc':			$fetchStyle = \PDO::FETCH_ASSOC; 	break;
				case 'num':				$fetchStyle = \PDO::FETCH_NUM; 		break;
				case 'singleColumn':	$fetchStyle = \PDO::FETCH_COLUMN; 	break;
				default:				$fetchStyle = \PDO::FETCH_ASSOC; 	break;
			}
		}
		
		try
		{
			$query = 'SELECT ' . implode(',', (array)$fields) . ' FROM ' . $tableName . $where . $groupBy . $orderBy . $limit;
			
			if( $showQuery || false )
			{
				global $selectQueries;
				$selectQueries .= $query . "\n";
				echo $query . "\n";
			}
			
        	// execute query
			$STH = self::$DBH->prepare( $query );
			$STH->execute( $parameters[ 'where' ] );

			$result = null;
			if( isset($parameters['singleRow']) && $parameters['singleRow'] )
				$result = $STH->fetch( $fetchStyle );
			else
				$result = $STH->fetchAll( $fetchStyle );
			
	        // store the number of rows
			self::$numrows = $STH->rowCount();

			// increment the select count
			self::$queryCount['select']++;

	        return $result;
		}
		catch(PDOException $e)
		{
			Logger::error( Logger::formatException( $e ) );

			return false;
		}
	}
	
	/**
	* Executes a SQL query on the database
	*
	* WARNING: this could be dangerous so use with caution, no checking is performed
	*
	* @param string $query query
	*
	* @return mixed result
	*/
	static function sql($query)
	{
		if( !self::initialize() )
			return false;

		// increment the sql counter
		self::$queryCount['sql']++;
		
		return self::$DBH->query($query);
	}
	
	/**
	* Gets the number of rows affected by the last query
	*
	* @return int number of rows affected by last query
	*/
	static function numrows()
	{
		if( !self::initialize() )
			return false;

		return (int)self::$numrows;
	}

	/**
	* Gets the ID of the last inserted row
	*
	* @return int last inserted ID
	*/
	static function lastInsertId()
	{
		if( !self::initialize() )
			return false;

		try
		{
			return self::$DBH->lastInsertId();
		}
		catch(PDOException $e)
		{
			Logger::error( Logger::formatException( $e ) );

			return null;
		}
	}
	
	/**
	* Gets a listing of the tables in the database
	*
	* @return array tables
	*/
	static function listTables()
	{
		if( !self::initialize() )
			return false;
	
		$result = self::$DBH->query("show tables");
		
		return ($result) ? $result->fetchAll( \PDO::FETCH_COLUMN ) : array();
	}
	
	/**
	* Gets a listing of the columns in a table
	*
	* @return array columns
	*/
	static function listColumns( $table )
	{
		if( !self::initialize() )
			return false;
	
		$result = self::$DBH->query("SHOW COLUMNS FROM `$table`");
		
		return ($result) ? $result->fetchAll( \PDO::FETCH_ASSOC ) : array();
	}
		
	/**
	* Gets the number of a type of statements exectued
	*
	* @param string $key type of query counter to load (all,select,insert,delete,update,sql)
	*
	* @return int count
	*/
	static function queryCounter( $key = 'all' )
	{
		if( !self::initialize() )
			return false;
	
		if( $key == 'all' || !isset( self::$queryCount[ $key ] ) )
			return self::$queryCount;
		else
			return self::$queryCount[ $key ];
	}
	
	/**
	 * Converts a schema into SQL statements
	 *
	 * @param array $schema
	 * @param boolean $newTable true if a new table should be created
	 *
	 * @return string sql
	 */
	static function schemaToSql( $tablename, $schema, $newTable = true )
	{
		if( !$schema || count( $schema ) == 0 )
			return false;
			
		$sql = '';

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
				$col .= ( Util::array_value( $column, 'Exists' ) ) ? 'MODIFY ' : 'ADD ';

			$col .= "`{$column['Field']}` {$column['Type']} ";

			$col .= ( strtolower( $column['Null'] ) == 'yes' ) ? 'NULL' : 'NOT NULL';
			
			if( $column[ 'Default' ] )
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
		
	////////////////////////////////
	// SETTERS	
	////////////////////////////////
	
	/**
	 * Notifies the class to start batching insert, update, delete queries
	 *
	 * @return boolean success
	 */
	static function startBatch()
	{
		if( !self::initialize() )
			return false;
	
		return self::$DBH->beginTransaction();
	}
	
	/**
	 * Executes all of the queries in the batch queue
	 *
	 * @return boolean success
	 */
	static function executeBatch()
	{
		if( !self::initialize() )
			return false;
	
		return self::$DBH->commit();
	}
	
	/**
	* Inserts a row into the database
	*
	* @param string $tableName table name
	* @param array $data data to be inserted
	*
	* @return boolean true if successful
	*/
	static function insert( $tableName, $data )
	{
		if( !self::initialize() )
			return false;
	
		try
		{
			// prepare and execute the statement
			$STH = self::$DBH->prepare('INSERT INTO ' . $tableName . ' (' . self::implode_key( ',', (array)$data ) . ') VALUES (:' . self::implode_key( ',:', (array)$data ) . ')');
			
			if( $STH->execute($data) )
			{
				// update the insert counter
				self::$queryCount[ 'insert' ]++;

				return true;
			}
		}
		catch(PDOException $e)
		{
			Logger::error( Logger::formatException() );

			return false;
		}
	}
	
	/**
	 * Inserts multiple rows at a time
	 *
	 * NOTE: The input data array must be a multi-dimensional array of rows with each entry in the row corresponding to the same entry in the fields
	 *
	 * @param string $tableName table name
	 * @param array $fields field names
	 * @param array $data data to be inserted
	 *
	 * @return boolean succeess
	 */
	static function insertBatch( $tableName, $fields, $data )
	{
		if( !self::initialize() )
			return false;
	
		if( count( $data ) == 0 )
			return true;

		$success = true;
	
		try
		{
			// start the transaction
			self::$DBH->beginTransaction();
			
			// prepare the values to be inserted
			$insert_values = array();
			$question_marks = array();
			foreach( $data as $d )
			{
				// build the question marks
			    $result = array();
		        for($x=0; $x < count($d); $x++)
		            $result[] = '?';
				$question_marks[] = '(' . implode(',', $result) . ')';
				
				// get the insert values
				$insert_values = array_merge( $insert_values, array_values($d) );
			}
			
			// generate the SQL
			$sql = "INSERT INTO $tableName (" . implode( ",", $fields ) . ") VALUES " . implode( ',', $question_marks );
			
			// prepare and execute the statement
			$stmt = self::$DBH->prepare( $sql );
			
			$stmt->execute( $insert_values );
			
			// commit the transaction
			if( self::$DBH->commit() )
			{
				// increment the insert counter
				self::$queryCount[ 'insert' ]++;

				return true;
			}
			else
				return false;
		}
		catch(PDOException $e)
		{
			Logger::error( Logger::formatException( $e ) );

			return false;
		}
	}
	
	/**
	* Builds and executes an update query
	*
	* @param string $tableName table name
	* @param array $data data to be updated
	* @param array $where array of keys in $data which will be used to match the rows to be updated
	* @param bool $showQuery echoes the query if true
	*
	* @return boolean true if successful
	*/
	static function update( $tableName, $data, $where = null, $showQuery = false )
	{
		if( !self::initialize() )
			return false;
	
		try
		{
			$sql = 'UPDATE ' . $tableName . ' SET ';
			foreach( (array)$data as $key=>$value )
			 	$sql .= $key . ' = :' . $key . ',';
			$sql = substr_replace($sql,'',-1);
			if( $where == null )
				$sql .= ' WHERE id = :id';
			else
				$sql .= ' WHERE ' . implode(' AND ', array_map(create_function('$key, $value', 'return $value.\' = :\'.$value;'), array_keys($where), array_values($where)) );

			if( $showQuery ) {
				echo $sql;
			}
				
			$STH = self::$DBH->prepare($sql);

			if( $STH->execute($data) )
			{
				self::$queryCount[ 'update' ]++;

				return true;
			}
			else
				return false;
		}
		catch(PDOException $e)
		{
			Logger::error( Logger::formatException( $e ) );

			return false;
		}
	}
	
	/**
	* Builds and executes a delete query
	*
	* @param string $tableName table name
	* @param array $where values used to match rows to be deleted
	*
	* @return boolean true if successful
	*/
	static function delete( $tableName, $where, $showQuery = false )
	{
		if( !self::initialize() )
			return false;
	
		try
		{
			$where_other = array(); // array of parameters which do not contain an equal sign or is too complex for our implode function
			$where_arr = array(); // array that will be used to concatenate all where clauses together
			
			foreach( $where as $key=>$value )
			{
				if( is_numeric( $key ) )
				{ // should not be parameterized
					$where_other[] = $value;
					unset( $where[$key] );
				}
				else
					$where[$key] = self::$DBH->quote($value);
			}
			
			$where_other_implode = implode(' AND ', $where_other );
			if( $where_other_implode  != '' ) // add to where clause
				$where_arr[] = $where_other_implode;
				
			$where_parameterized = implode(' AND ', array_map(create_function('$key, $value', 'return $key.\'=\'.$value;'), array_keys($where), array_values($where) ) );
			if( $where_parameterized != '' )
				$where_arr[] = $where_parameterized;
				
			$query = 'DELETE FROM ' . $tableName . ' WHERE ' . implode(' AND ', $where_arr );

			if( $showQuery )
				echo $query;

			self::$DBH->exec( $query );

			self::$queryCount[ 'delete' ]++;

			return true;
		}
		catch(PDOException $e)
		{
			Logger::error( Logger::formatException( $e ) );

			return false;
		}
	}
	
	////////////////////////////
	// Private Class Functions
	////////////////////////////
	
	private static function implode_key($glue = '', $pieces = array())
	{
	    $arrK = array_keys($pieces);
	    return implode($glue, $arrK);
	}
	
	private static function multi_implode($array = array(), $glue = '') {
	    $ret = '';
	
	    foreach ($array as $item) {
	        if (is_array($item)) {
	            $ret .= self::multi_implode($item, $glue) . $glue;
	        } else {
	            $ret .= $item . $glue;
	        }
	    }
	
	    $ret = substr($ret, 0, 0-strlen($glue));
	
	    return $ret;
	}
}