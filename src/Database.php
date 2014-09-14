<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.25
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

use Pimple\Container;

class Database
{
    /////////////////////////////
    // Private class variables
    /////////////////////////////

    private static $config = [
        'type' => '',
        'host' => '',
        'name' => '',
        'user' => '',
        'password' => '',
        'productionLevel' => false
    ];

    private static $PDO;
    private static $numrows;
    private static $batch = false;
    private static $batchQueue;
    private static $initializeAttempted;
    private static $injectedApp;

    /**
	 * Sets up the settings used to interact with database
	 *
	 * @param array $config
	 */
    public static function configure(array $config)
    {
        self::$config = array_replace( self::$config, $config );

        self::$initializeAttempted = false;
        self::$PDO = null;
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

    /**
	* Initializes the connection with the database. Only needs to be called once.
	*
	* @return boolean true if successful
	*/
    public static function initialize()
    {
        if( self::$initializeAttempted )

            return self::$PDO instanceof \PDO;

        self::$initializeAttempted = true;

        try {
            // Initialize database
            if (self::$PDO == null) {
                $dsn = '';

                if( strpos( self::$config[ 'type' ], 'sqlite' ) === 0 )
                    // i.e. sqlite:memory:
                    $dsn = self::$config[ 'type' ] . ':' . self::$config[ 'host' ];
                else
                    // i.e. mysql:host=localhost;dbname=test
                    $dsn = self::$config[ 'type' ] . ':host=' . self::$config[ 'host' ] . ';dbname=' . self::$config[ 'name' ];

                self::$PDO = new \PDO( $dsn, Utility::array_value( self::$config, 'user' ), Utility::array_value( self::$config, 'password' ) );
            }
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->alert( $e );

            die( 'Could not connect to database.' );

            return false;
        }

        // Set error level
        if( self::$config[ 'productionLevel' ] )
            self::$PDO->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING );
        else
            self::$PDO->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );

        return true;
    }

    /**
	 * Gets the type of database we are connecting to
	 *
	 * @return string
	 */
    public static function type()
    {
        return Utility::array_value( self::$config, 'type' );
    }

    /**
	* Generates and executes a select query.
	*
	* Parameters:
	* <ul>
	* <li>where: Array of where parameters. Key => value translates into key = value. If no key is supplied then the value is treated as its own parameter.
	* <code>'where' => [ 'first_name' => 'John', 'last_name' => 'Doe', 'created > 10405833' ]</code></li>
	* <li>single: returns a single value</li>
	* <li>singleRow: returns a single row</li>
	* <li>fetchStyle: see PDO manual</li>
	* <li>orderBy</li>
	* <li>groupBy</li>
	* </ul>
	*
	* @param string $tablename table name
	* @param string $fields fields, comma-seperated
	* @param array $parameters parameters
	*
	* @return boolean success
	*/
    public static function select($tablename, $fields, $parameters = [])
    {
        if( !self::initialize() )

            return false;

        if ( Utility::array_value( $parameters, 'single' ) ) {
            $parameters[ 'singleRow' ] = true;
            $parameters[ 'fetchStyle' ] = 'singleColumn';
        } elseif( Utility::array_value( $parameters, 'singleColumn' ) )
            $parameters[ 'fetchStyle' ] = 'singleColumn';

        // escape identifiers in field list
        $escapedFields = implode( ',', array_map( function ($field) {
            return self::escapeIdentifier( $field );
        }, explode( ',', $fields ) ) );

        // add backticks to table name, unless a space is found
        // in which case, the developer is responsible for adding backticks where needed
        $tablename = self::escapeIdentifier( $tablename );

        $sql = "SELECT $escapedFields FROM $tablename";

        $whereData = (isset($parameters['where'])) ? $parameters[ 'where' ] : [];
        $sql .= ' ' . self::generateWhereString( $whereData );

        if( isset( $parameters[ 'groupBy' ] ) )
            $sql .= ' GROUP BY ' . $parameters[ 'groupBy' ];

        if( isset( $parameters[ 'orderBy' ] ) )
            $sql .= ' ORDER BY ' . $parameters[ 'orderBy' ];

        if( isset( $parameters[ 'limit' ] ) )
            $sql .= ' LIMIT ' . $parameters[ 'limit' ];

        $fetchStyle = \PDO::FETCH_ASSOC;
        if ( isset( $parameters[ 'fetchStyle' ] ) ) {
            switch ($parameters[ 'fetchStyle' ]) {
                case 'assoc':            $fetchStyle = \PDO::FETCH_ASSOC;    break;
                case 'num':                $fetchStyle = \PDO::FETCH_NUM;        break;
                case 'singleColumn':    $fetchStyle = \PDO::FETCH_COLUMN;    break;
                default:                $fetchStyle = \PDO::FETCH_ASSOC;    break;
            }
        }

        try {
            $statement = self::$PDO->prepare( $sql );
            $statement->execute( $whereData );

            $result = null;
            if( isset( $parameters[ 'singleRow' ] ) && $parameters[ 'singleRow' ] )
                $result = $statement->fetch( $fetchStyle );
            else
                $result = $statement->fetchAll( $fetchStyle );

            self::$numrows = $statement->rowCount();

            return $result;
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Executes a SQL query on the database
	*
	* WARNING: this could be dangerous so use with caution, no sanitation will be performed
	*
	* @param string $sql SQL query
	*
	* @return mixed result
	*/
    public static function sql($sql)
    {
        if( !self::initialize() )

            return false;

        try {
            return self::$PDO->query( $sql );
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Gets the number of rows affected by the last query
	*
	* @return int number of rows affected by last query
	*/
    public static function numrows()
    {
        if( !self::initialize() )

            return false;

        return (int) self::$numrows;
    }

    /**
	* Gets the ID of the last inserted row
	*
	* @return int last inserted ID
	*/
    public static function lastInsertId()
    {
        if( !self::initialize() )

            return false;

        try {
            return self::$PDO->lastInsertId();
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Gets a listing of the tables in the database
	*
	* @return array tables
	*/
    public static function listTables()
    {
        if( !self::initialize() )

            return false;

        $sql = 'SHOW TABLES';

        try {
            $result = self::$PDO->query( $sql );

            return ($result) ? $result->fetchAll( \PDO::FETCH_COLUMN ) : [];
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Gets a listing of the columns in a table
	*
	* @return array columns
	*/
    public static function listColumns($tablename)
    {
        if( !self::initialize() )

            return false;

        $tablename = self::escapeIdentifier( $tablename );

        $sql = "SHOW COLUMNS FROM $tablename";

        try {
            $result = self::$PDO->query( $sql );

            return ($result) ? $result->fetchAll( \PDO::FETCH_ASSOC ) : [];
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	 * Converts a schema into SQL statements
	 *
	 * @param string $tablename
	 * @param array $schema
	 * @param boolean $newTable true if a new table should be created
	 *
	 * @return string sql
	 */
    public static function schemaToSql($tablename, $schema, $newTable = true)
    {
        if( !$schema || count( $schema ) == 0 )

            return false;

        $tablename = self::escapeIdentifier( $tablename );

        $sql = "ALTER TABLE $tablename\n";

        if( $newTable )
            $sql = "CREATE TABLE IF NOT EXISTS $tablename (\n";

        $primaryKeys = [];

        $cols = [];
        foreach ($schema as $column) {
            $col = "\t";

            if( !$newTable )
                $col .= ( Utility::array_value( $column, 'Exists' ) ) ? 'MODIFY ' : 'ADD ';

            $col .= $tablename = self::escapeIdentifier( $column[ 'Field' ] ) . ' ' . $column[ 'Type' ] . ' ';

            $col .= ( strtolower( $column['Null'] ) == 'yes' ) ? 'NULL' : 'NOT NULL';

            if( $column[ 'Default' ] )
                $col .= " DEFAULT '{$column['Default']}'";

            if( $column['Extra'] )
                $col .= " {$column['Extra']}";

            if ($column['Key']) {
                if( $column['Key'] == 'PRI' )
                    $primaryKeys[] = $column[ 'Field' ];
                elseif( $newTable )
                    $col .= ' ' . $column['Key'];
            }

            $cols[] = $col;
        }

        // TODO
        // index
        // unique index

        // quote primary keys
        $primaryKeys = array_map( function ($field) { return self::escapeIdentifier( $field ); }, $primaryKeys );

        // primary key
        if ($newTable) {
            $cols[] = "\t" . 'PRIMARY KEY(' . implode( ',', $primaryKeys ) . ')';
        } else {
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
    public static function startBatch()
    {
        if( !self::initialize() )

            return false;

        try {
            return self::$PDO->beginTransaction();
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	 * Executes all of the queries in the batch queue
	 *
	 * @return boolean success
	 */
    public static function executeBatch()
    {
        if( !self::initialize() )

            return false;

        try {
            return self::$PDO->commit();
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Inserts a row into the database
	*
	* @param string $tablename table name
	* @param array $data data to be inserted
	*
	* @return boolean true if successful
	*/
    public static function insert($tablename, array $data)
    {
        if( !self::initialize() )

            return false;

        $tablename = self::escapeIdentifier( $tablename );

        $sql = "INSERT INTO $tablename";

        $sql .= ' (' . self::implodeKeys( ',', $data, true ) . ')';
        $sql .= ' VALUES (:' . self::implodeKeys( ',:', $data, false, true ) . ')';

        // strip periods from named parameters, MySQL does not like this
        // i.e. 'u.uid' => 'uuid'
        $data = self::stripCharactersFromKeys( [ '.' ], $data );

        try {
            $statement = self::$PDO->prepare( $sql );

            return $statement->execute($data);
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	 * Inserts multiple rows at a time
	 *
	 * NOTE: The input data array must be a 2-D array of rows with each entry in the row corresponding to the same entry in the fields
	 *
	 * @param string $tablename table name
	 * @param array $fields field names
	 * @param array $data data to be inserted
	 *
	 * @return boolean succeess
	 */
    public static function insertBatch($tablename, array $fields, array $data)
    {
        if( !self::initialize() )

            return false;

        if( count( $data ) == 0 )

            return true;

        $success = true;

        // quote fields
        $fields = array_map( function ($field) { return self::escapeIdentifier( $field ); }, $fields );

        // prepare the values to be inserted
        $insert_values = [];
        $question_marks = [];
        foreach ($data as $d) {
            // build the question marks
            $result = [];
            for( $x=0; $x < count( $d ); $x++ )
                $result[] = '?';
            $question_marks[] = '(' . implode( ',', $result ) . ')';

            // get the insert values
            $insert_values = array_merge( $insert_values, array_values( $d ) );
        }

        // generate the SQL
        $tablename = self::escapeIdentifier( $tablename );
        $sql = "INSERT INTO $tablename";
        $sql .= ' (' . implode( ",", $fields ) . ')';
        $sql .= ' VALUES ' . implode( ',', $question_marks );

        try {
            self::$PDO->beginTransaction();

            $statement = self::$PDO->prepare( $sql );
            $statement->execute( $insert_values );

            return self::$PDO->commit();
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Builds and executes an update query
	*
	* @param string $tablename table name
	* @param array $data data to be updated
	* @param array $where array of keys in $data which will be used to match the rows to be updated
	*
	* @return boolean true if successful
	*/
    public static function update($tablename, array $data, array $where = [])
    {
        if( !self::initialize() )

            return false;

        $tablename = self::escapeIdentifier( $tablename );

        $sql = "UPDATE $tablename";

        // generate named update parameters from input data
        $sql .= ' SET ' . implode( ',', self::generateNamedParametersStrings( $data ) );

        // generate where string using named parameters
        // TODO this is a hack to format the where parameters to look like
        // the input generateWhereString() expects
        // the values are not used, which is why they are set to empty strings
        $whereData = [];
        foreach( $where as $key )
            $whereData[ $key ] = '';
        $sql .= ' ' . self::generateWhereString( $whereData );

        // strip periods from named parameters, MySQL does not like this
        // i.e. 'u.uid' => 'uuid'
        $data = self::stripCharactersFromKeys( [ '.' ], $data );

        try {
            $statement = self::$PDO->prepare( $sql );

            return $statement->execute( $data );
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    /**
	* Builds and executes a delete query
	*
	* @param string $tablename table name
	* @param array $where values used to match rows to be deleted
	*
	* @return boolean true if successful
	*/
    public static function delete($tablename, array $where)
    {
        if( !self::initialize() )

            return false;

        $sql = "DELETE FROM $tablename";

        // generate where string using named parameters
        $sql .= ' ' . self::generateWhereString( $where );

        try {
            $statement = self::$PDO->prepare( $sql );

            return $statement->execute( $where );
        } catch ( \PDOException $e ) {
            self::$injectedApp[ 'logger' ]->error( 'PDOException with query: ' . $sql );
            self::$injectedApp[ 'logger' ]->error( $e );

            return false;
        }
    }

    ////////////////////////////
    // Private Class Functions
    ////////////////////////////

    /**
	 * Produces a SQL where string using PDO named parameters from input data
	 *
	 * @param array $where input data
	 *
	 * @return string SQL
	 */
    private static function generateWhereString(array &$where)
    {
        $whereComposition = [];

        foreach ($where as $key => $value) {
            // If the index is numeric, then it is not a named parameter. Instead,
            // it must be a SQL string containing other operators besides equality.
            //		i.e. 'uid > 5'
            // Thus, it should not be parameterized like the other where parameters
            if ( is_numeric( $key ) ) {
                if( !empty( $value ) )
                    $whereComposition[] = $value;

                unset( $where[ $key ] );
            }
        }

        $namedParameters = self::generateNamedParametersStrings( $where );
        if ( count( $namedParameters ) > 0 ) {
            $whereComposition = array_merge( $whereComposition, $namedParameters );

            // strip periods from named parameters, MySQL does not like this
            // i.e. 'u.uid' => 'uuid'
            $where = self::stripCharactersFromKeys( [ '.' ], $where );
        }

        if( count( $whereComposition ) > 0 )

            return 'WHERE ' . implode( ' AND ', $whereComposition );

        return '';
    }

    /**
	 * Generates an array mapping of named parameter strings for PDO
	 * from an array of input data
	 *
	 * @param array $data input data
	 *
	 * @return array named parameters
	 */
    private static function generateNamedParametersStrings(array $data)
    {
        return array_map( function ($key) {
            $escapedKey = self::escapeIdentifier( $key );
            $sanitizedKey = str_replace( '.', '', $key );

            return "$escapedKey = :$sanitizedKey";
        }, array_keys( $data ) );
    }

    /**
	 * Strips a collection of input characters from the keys of all the input data
	 *
	 * @param array $characters characters to remove
	 * @param array $data input data
	 *
	 * @return array data with modified keys
	 */
    private static function stripCharactersFromKeys(array $characters, array $data)
    {
        foreach ($data as $oldKey => $value) {
            unset( $data[ $oldKey ] );
            $newKey = str_replace( $characters, array_fill( 0, count( $characters ), '' ), $oldKey );
            $data[ $newKey ] = $value;
        }

        return $data;
    }

    /**
	 * Implodes the keys of input data
	 *
	 * @param string $glue character(s) to glue keys back together with
	 * @param array $data input data
	 * @param bool $escapeIdentifier when true, escapes the keys as identifiers
	 * @param bool $stripPeriods when true, strips periods from the keys
	 *
	 * @return string flattened keys
	 */
    private static function implodeKeys($glue, array $data, $escapeIdentifier = false, $stripPeriods = false)
    {
        $keys = array_keys( $data );

        if( $escapeIdentifier )
            $keys = array_map( function ($k) {
                return self::escapeIdentifier( $k );
            }, $keys );

        if( $stripPeriods )
            $keys = self::stripCharactersFromKeys( [ '.' ], $keys );

        return implode( $glue, $keys );
    }

    /**
	 * Escapes potentially reserved keywords in identifiers by wrapping them
	 * with the escape character as necessary
	 *
	 * @param string $word
	 * @param string $escapeChar
	 *
	 * @return string escaped identifier
	 */
    private static function escapeIdentifier($word, $escapeChar = '`')
    {
        // currently this only wraps words containing only letters
        // anything else (i.e. '.' or ' ') will not be touched
        if( preg_match( '/^[A-Za-z]*$/', $word ) )

            return $escapeChar . $word . $escapeChar;

        return $word;
    }
}
