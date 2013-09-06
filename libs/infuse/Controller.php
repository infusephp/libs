<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.15.1
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace infuse;

abstract class Controller extends Acl
{
	public static $properties = array(
		'title' => '',
		'version' => 0,
		'description' => '',
		'author' => array(
			'name' => '',
			'email' => '',
			'website' => '' ),
		'model' => false,
		'models' => false,
		'api' => false,
		'admin' => false,
		'routes' => array() );
	
	protected $models;

	/////////////////////////
	// GETTERS
	/////////////////////////
	
	/**
	 * Gets the name of the controller
	 *
	 * @return string name
	*/
	static function name()
	{
		return strtolower( str_replace( 'infuse\\controllers\\', '', get_called_class() ) );
	}
	
	/**
	 * Gets the properties of the controller
	 *
	 * @return array
	 */
	static function properties()
	{
		return array_replace( self::$properties, array( 'name' => static::name() ), static::$properties );
	}
	
	/**
	 * Gets info about the models associated with this controller
	 *
	 * @return array models
	 */
	function models()
	{
		if( !$this->models )
		{
			$properties = static::properties();
			
			$modelParams = array();
			
			if( $models = Util::array_value( $properties, 'models' ) )
				$modelNames = $models;
			else if( $model = Util::array_value( $properties, 'model' ) )
				$modelNames[] = $model;
			
			$this->models = array();
	
			foreach( $modelNames as $model )
			{
				$modelClassName = '\\infuse\\models\\' . $model;
			
				$info = $modelClassName::info();
	
				$this->models[ $model ] = array_replace( $info, array(
					'api' => Util::array_value( $properties, 'api' ),
					'admin' => Util::array_value( $properties, 'admin' ),
					'route_base' => '/' . $properties[ 'name' ] . '/' . $info[ 'plural_key' ] ) );
			}
		}
		
		return $this->models;
	}	
	
	/**
	 * Allows the controller to perform middleware tasks before routing. Must be explicitly called.
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 */
	function middleware( $req, $res )
	{ }
	
	
	/**
	 * Finds all matching models. Only works when API scaffolding is enabled.
	 *
	 * @param Request $req
	 * @param Response $res
	 *
	 */
	function findAll( $req, $res )
	{
		// which model are we talking about?
		$model = $this->fetchModelInfo( $req->params( 'model' ) );
		
		// check if automatic api generation enabled
		if( !$model || !$model[ 'api' ] )
			return $res->setCode( 404 );

		// json only
		if( !$req->isJson() )
			return $res->setCode( 406 );

		$modelClassName = $model[ 'class_name' ];
		$modelRouteName = $model[ 'plural_key' ];
		
		$modelObj = new $modelClassName();
		
		// permission?
		if( !$modelObj->can( 'view' ) )
			return $res->setCode( 401 );
		
		$return = new \stdClass;
		$return->$modelRouteName = array();
		
		// limit
		$limit = $req->query( 'limit' );
		if( $limit <= 0 || $limit > 1000 )
			$limit = 100;
		
		// start
		$start = $req->query( 'start' );
		if( $start < 0 || !is_numeric( $start ) )
			$start = 0;
		
		// sort
		$sort = $req->query( 'sort' );
		
		// search
		$search = $req->query( 'search' );
		
		// filter
		$filter = (array)$req->query( 'filter' );
		
		$models = $modelClassName::find( array(
			'start' => $start,
			'limit' => $limit,
			'sort' => $sort,
			'search' => $search,
			'where' => $filter ) );
		
		foreach( $models[ 'models' ] as $m )
			array_push( $return->$modelRouteName, $m->toArray() );
		
		// pagination
		$total = $modelClassName::totalRecords( $filter );
		$page = $start / $limit + 1;
		$page_count = max( 1, ceil( $models[ 'count' ] / $limit ) );
		
		$return->page = $page;
		$return->per_page = $limit;
		$return->page_count = $page_count;
		$return->filtered_count = $models[ 'count' ];
		$return->total_count = $total;
		
		// links
		$base = $model[ 'route_base' ] . "?sort=$sort&limit=$limit";
		$last = ($page_count-1) * $limit;
		$return->links = array(
			'self' => "$base&start=$start",
			'first' => "$base&start=0",
			'last' => "$base&start=$last",
		);
		if( $page > 1 )
			$return->links['previous'] = "$base&start=" . ($page-2) * $limit;
		if( $page < $page_count )
			$return->links['next'] = "$base&start=" . ($page) * $limit;
		
		// quirky datatables thing
		if( $sEcho = $req->query( 'sEcho' ) )
			$return->sEcho = intUtil::array_value( $sEcho );
		
		$res->setBodyJson( $return );
	}
	
	/**
	 * Finds a particular model. Only works when API scaffolding is enabled.
	 *
	 * @param Request $req
	 * @param Response $res
	 *
	 */
	function find( $req, $res )
	{
		// which model are we talking about?
		$model = $this->fetchModelInfo( $req->params( 'model' ) );
		
		// check if automatic api generation enabled
		if( !$model || !$model[ 'api' ] )
 			return $res->setCode( 404 );

		$modelClassName = $model[ 'class_name' ];
		$modelObj = new $modelClassName( $req->params( 'id' ) );
		
		// exists?
		if( !$modelObj->exists() )
			return $res->setCode( 404 );

		// json only
		if( !$req->isJson() )
			return $res->setCode( 406 );

		// permission?
		if( !$modelObj->can( 'view' ) )
			return $res->setCode( 401 );
				
		$res->setBodyJson( array(
			$model[ 'singular_key' ] => $modelObj->toArray() ) );
	}
	
	/**
	 * Creates a new model. Only works when API scaffolding is enabled.
	 *
	 * @param Request $req
	 * @param Response $res
	 *
	 */
	function create( $req, $res )
	{
		// which model are we talking about?
		$model = $this->fetchModelInfo( $req->params( 'model' ) );
		
		// check if automatic api generation enabled
		if( !$model || !$model[ 'api' ] )
			return $res->setCode( 404 );

		// json only
		if( !$req->isJson() )
			return $res->setCode( 406 );

		$modelClassName = $model[ 'class_name' ];
		$modelObj = new $modelClassName();
		
		// permission?
		if( !$modelObj->can( 'create' ) )
			return $res->setCode( 401 );		
				
		// create a new model
		$newModel = $modelClassName::create( $req->request() );
		
		if( $newModel )
			$res->setBodyJson( array(
				$model[ 'singular_key' ] => $newModel->toArray(),
				'success' => true ) );
		else
		{
			$res->setBodyJson( array( 'error' => ErrorStack::it()->messages() ) );
		}
	}
	
	/**
	 * Edits a model. Only works when API scaffolding is enabled.
	 *
	 * @param Request $req
	 * @param Response $res
	 *
	 */
	function edit( $req, $res )
	{
		// which model are we talking about?
		$model = $this->fetchModelInfo( $req->params( 'model' ) );
		
		// check if automatic api generation enabled
		if( !$model || !$model[ 'api' ] )
			return $res->setCode( 404 );
		
		// json only
		if( !$req->isJson() )
			return $res->setCode( 406 );
		
		$modelClassName = $model[ 'class_name' ];
		$modelObj = new $modelClassName( $req->params( 'id' ) );
		
		// permission?
		if( !$modelObj->can( 'edit' ) )
			return $res->setCode( 401 );
		
		// update the model
		$success = $modelObj->set( $req->request() );
		
		if( $success )
			$res->setBodyJson( array(
				'success' => true ) );
		else
		{
			$res->setBodyJson( array(
				'error' => ErrorStack::it()->messages() ) );
		}
	}
	
	/**
	 * Deletes a model. Only works when API scaffolding is enabled.
	 *
	 * @param Request $req
	 * @param Response $res	
	 *
	 */	
	function delete( $req, $res )
	{
		// which model are we talking about?
		$model = $this->fetchModelInfo( $req->params( 'model' ) );

		// check if automatic api generation enabled
		if( !$model || !$model[ 'api' ] )
			return $res->setCode( 404 );

		// json only
		if( !$req->isJson() )
			return $res->setCode( 406 );

		$modelClassName = $model[ 'class_name' ];
		$modelObj = new $modelClassName( $req->params( 'id' ) );
		
		// permission?
		if( !$modelObj->can( 'delete' ) )
			return $res->setCode( 401 );
		
		// delete the model
		if( $modelObj->delete() )
			$res->setBodyJson( array(
				'success' => true ) );
		else
			$res->setBodyJson( array(
				'error' => true ) );
	}

	/**
	 * Displays an automatically generated admin view of this controller when enabeld
	 *
	 * @param Request $req
	 * @param Response $res	
	 *
	 */
	function routeAdmin( $req, $res )
	{
		$properties = static::properties();
		
		$models = $this->models();
				
		// check if automatic admin generation enabled
		if( !$properties[ 'admin' ] )
			return $res->setCode( 404 );

		// html only
		if( !$req->isHtml() )
			return $res->setCode( 406 );
		
		// must have permission to view admin section
		if( !$this->can( 'view-admin' ) )
			return $res->setCode( 401 );
		
		$selectedModel = Util::array_value( $req->paths(), 2 );
		
		$params = array(
			'moduleName' => $properties[ 'name' ],
			'models' => $models
		);
		
		$paths = $req->paths();
		
		if( count( $paths ) >= 3 && $paths[ 2 ] == 'schema' )
		{
			// update the schema?
			if( in_array( Util::array_value( $paths, 3 ), array( 'update', 'cleanup' ) ) )
			{
				$modelClassName = '\\infuse\\models\\' . Util::array_value( $paths, 4 );
				$modelObj = new $modelClassName();
				
				if( $modelObj::updateSchema( Util::array_value( $paths, 3 ) == 'cleanup' ) )
					return $res->redirect( '/4dm1n/' . $properties[ 'name' ] . '/schema?success=t' );
			}

			$schema = array();
			
			// fetch the schema for all models under this controller
			foreach( $models as $model => $info )
			{
				$modelClassName = $info[ 'class_name' ];
				$modelObj = new $modelClassName();
				
				// suggest a schema based on properties
				$schema[ $model ] = $modelObj::suggestSchema();
			}

			$params[ 'schema' ] = $schema;
			$params[ 'success' ] = $req->query( 'success' );
		}
		else
		{
			if( !$selectedModel )
			{
				$defaultModel = false;
				
				if( isset( $properties[ 'default-model' ] ) )
					$defaultModel = $properties[ 'default-model' ];
				
				if( count( $models ) > 0 )
					$defaultModel = reset( $models );
				
				if( $defaultModel )
					return $res->redirect( '/4dm1n/' . $properties[ 'name' ] . '/' . $defaultModel[ 'model' ] );
			}
			
			// which model are we talking about?
			$model = $this->fetchModelInfo( $selectedModel );
			
			$modelClassName = $model[ 'class_name' ];
			$modelObj = new $modelClassName();
			
			$modelInfo = array_replace( $model, array(
				'permissions' => array(
					'create' => $modelObj->can('create'),
					'edit' => $modelObj->can('edit'),
					'delete' => $modelObj->can('delete') ),
				'idProperty' => $modelClassName::$idProperty,
				'properties' => array()
			) );
			$params[ 'modelInfo' ] = $modelInfo;		
		
			$default = array(
				'truncate' => true,
				'nowrap' => true
			);		
		
			foreach( $modelClassName::$properties as $name => $property )
			{
				$modelInfo[ 'properties' ][] = array_merge(
					$default,
					array(
						'name' => $name,
						'title' => Inflector::humanize( $name ) ),
					$property );
			}
			
			$params[ 'modelJSON' ] = json_encode( $modelInfo );
			$params[ 'ngApp' ] = 'models';
		}
		
		$res->render( 'admin/model', $params );
	}

	/**
	 * Executes a cron command
	 *
	 * @param string $command command
	 *
	 * @return boolean true if the command finished successfully
	*/
	function cron( $command )
	{
		$name = static::name();
		echo "$name\-\>cron($command) does not exist\n";
		return false;
	}
	
	///////////////////////////////////
	// PRIVATE FUNCTIONS
	///////////////////////////////////
	
	/** 
	 * Takes the pluralized model name from the route and gets info about the model
	 *
	 * @param string $modelRouteName the name that comes from the route (i.e. the route "/users" would supply "users")
	 *
	 * @return array|false model info
	 */
	private function fetchModelInfo( $modelRouteName )
	{
		// get info about this controller
		$properties = static::properties();
		
		// fetch all model info for our controller
		$modelsInfo = $this->models();
		
		// convert the route name to the pluralized name
		$modelName = Inflector::singularize( Inflector::camelize( $modelRouteName ) );
		
		// attempt to fetch the model info
		$modelInfo = Util::array_value( $modelsInfo, $modelName );

		if( !$modelInfo )
		{
			// attempt to pick a default model
			if( count( $modelsInfo ) == 1 )
				$modelInfo = reset( $modelsInfo );
			else if( isset( $properties[ 'default-model' ] ) )
				$modelInfo = Util::array_value( $modelsInfo, $properties[ 'default-model' ] );
		}
		
		return $modelInfo;
	}
}