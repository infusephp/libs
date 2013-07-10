<?php
/*
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

namespace infuse;

abstract class Controller extends Acl
{
	/*
	 * Constructor
	*/
	function __construct()
	{
		static::initialize();
	}
	
	/*
	 * Initializes the module (only called once)
	*/
	static function initialize()
	{
		// register autoloader
		spl_autoload_register( get_called_class() . '::loadClass' );
	}
	
	/////////////////////////
	// GETTERS
	/////////////////////////
	
	/**
	 * Gets the name of the module
	 *
	 * @return string name
	*/
	static function name()
	{
		return strtolower( str_replace( 'infuse\\controllers\\', '', get_called_class() ) );
	}
	
	/**
	 * Class autoloader
	 *
	 * @param string $class class
	 *
	 * @return null
	*/
	public static function loadClass( $class )
	{
		// look in modules/MODULE/CLASS.php
		// i.e. /infuse/models/User -> modules/users/models/User.php
		
		$module = static::name();
		if( $module != 'Module' || $module != '' )
		{
			$name = str_replace( '\\', '/', str_replace( 'infuse\\', '', $class ) );
			$path = Modules::$moduleDirectory . "$module/$name.php";

			if (file_exists($path) && is_readable($path))
			{
				include_once $path;
				return;
			}
		}
	}
	
	/**
	 * Checks permissions on the controller
	 *
	 * @param string $permission permission
	 * @param object $requester requester
	 *
	 * @param boolean
	 */
	function can( $permission, $requestor = null )
	{
		if( $requestor === null )
			$requestor = \infuse\models\User::currentUser();
		
		// everyone in ADMIN group can view admin panel
		if( $permission == 'view-admin' && $requestor->isMemberOf( ADMIN ) )
			return true;

		return parent::can( $permission, $requestor );
	}
	
	/**
	 * Allows the controller to perform middleware tasks before routing
	 *
	 * NOTE: Middleware only gets called on required modules. A module must be specified
	 * as required for middleware to work for every request.
	 *
	 * @param Request $request
	 * @param Response $response
	 *
	 */
	function middleware( $req, $res )
	{ }
	
	
	/**
	 * Finds all matching models. Only words when the automatic API feature is turned on
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
			$return->sEcho = intval( $sEcho );
		
		$res->setBodyJson( $return );
	}
	
	/**
	 * Finds a particular model. Only supported when automatic API turned on.
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
	 * Creates a new model. Only supported when automatic API turned on.
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
			$errors = ErrorStack::errorsWithContext( 'create' );
			$messages = array();
			foreach( $errors as $error )
				$messages[] = $error['message'];
			
			$res->setBodyJson( array(
				'error' => $messages ) );
		}
	}
	
	/**
	 * Edits a model. Requires that automatic API generation is enabled.
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
			$errors = ErrorStack::errorsWithContext( 'edit' );
			$messages = array();
			foreach( $errors as $error )
				$messages[] = $error['message'];
			
			$res->setBodyJson( array(
				'error' => $messages ) );
		}
	}
	
	/**
	 * Deletes a model. Requires that automatic API generation is eanbled.
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
	 * Displays an automatically generated admin view of a module
	 *
	 * @param Request $req
	 * @param Response $res	
	 *
	 */
	function routeAdmin( $req, $res )
	{
		$module = self::name();
		$moduleInfo = Modules::info( $module );		
		$models = Modules::models( $module );
				
		// check if automatic admin generation enabled
		if( !$moduleInfo[ 'admin' ] )
			return $res->setCode( 404 );

		// html only
		if( !$req->isHtml() )
			return $res->setCode( 406 );
		
		// must have permission to view admin section
		if( !$this->can( 'view-admin' ) )
			return $res->setCode( 401 );
		
		$selectedModel = val( $req->paths(), 2 );
		
		$params = array(
			'moduleName' => $module,
			'models' => $models
		);
		
		$paths = $req->paths();
		
		if( count( $paths ) >= 3 && $paths[ 2 ] == 'schema' )
		{
			$tablename = array();
			$currentSchemaSql = array();
			$suggestedSchema = array();
		
			foreach( $models as $model => $info )
			{
				$modelClassName = $info[ 'class_name' ];
				$modelObj = new $modelClassName();
			
				// get tablename for model
				$tablename[ $model ] = $modelObj::tablename();
			
				// look up current schema				
				try
				{
					$currentSchema[ $model ] = Database::listColumns( $tablename[ $model ] );
				}
				catch( \Exception $e )
				{
					$currentSchema[ $model ] = false;
				}

				// are we creating a new table or altering?
				$newTable = !$currentSchema[ $model ];
				
				// suggest a schema based on properties
				$suggestedSchema[ $model ] = $modelObj::schemaToSql( $modelObj::suggestSchema( $currentSchema[ $model ] ), $newTable );

				// convert to sql
				$currentSchema[ $model ] = ($currentSchema[ $model ]) ? $modelObj::schemaToSql( $currentSchema[ $model ], true ) : false;				
			}

			// update the schema?
			if( val( $paths, 3 ) == 'update' )
			{
				$model = val( $paths, 4 );
				
				try
				{
					$params[ 'success' ] = Database::sql( $suggestedSchema[ $model ] );

					if( $params[ 'success' ] )
						return $res->redirect( '/4dm1n/' . $module . '/schema?success=t' );
				}
				catch( \Exception $e )
				{
					$params[ 'error' ] = $e->getMessage();
				}
			}

			$params[ 'schema' ] = true;
			$params[ 'tablename' ] = $tablename;
			$params[ 'currentSchema' ] = $currentSchema;
			$params[ 'suggestedSchema' ] = $suggestedSchema;
			$params[ 'success' ] = $req->query( 'success' );
		}
		else
		{
			if( !$selectedModel )
			{
				$defaultModel = false;
				
				if( isset( $moduleInfo[ 'default-model' ] ) )
					$defaultModel = $moduleInfo[ 'default-model' ];
				
				if( count( $models ) > 0 )
					$defaultModel = reset( $models );
				
				if( $defaultModel )
					return $res->redirect( '/4dm1n/' . $module . '/' . $defaultModel[ 'model' ] );
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
		
		$res->render( 'admin/model.tpl', $params );
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
		$name = self::name();
		echo "$name\-\>cron($command) does not exist\n";
		return false;
	}
	
	/**
	 * Gets the module path
	 *
	 * @return string path
	*/
	protected function modulePath()
	{
		return Modules::$moduleDirectory . static::name() . '/';
	}
	
	/**
	 * Gets the template path
	 *
	 * @return string path
	*/
	protected function templateDir()
	{
		return static::modulePath() . 'views/';
	}
	
	/**
	 * Gets the admin template path
	 *
	 * @return string path
	*/
	protected function adminTemplateDir()
	{
		return static::templateDir() . 'admin/';
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
		// which module are we?
		$module = self::name();
		
		// get info about module
		$moduleInfo = Modules::info( $module );
		
		// fetch all model info for our module
		$modelsInfo = Modules::models( $module );
		
		// convert the route name to the pluralized name
		$modelName = Inflector::singularize( Inflector::camelize( $modelRouteName ) );
		
		// attempt to fetch the model info
		$modelInfo = val( $modelsInfo, $modelName );

		if( !$modelInfo )
		{
			// attempt to pick a default model
			if( count( $modelsInfo ) == 1 )
				$modelInfo = reset( $modelsInfo );
			else if( isset( $moduleInfo[ 'default-model' ] ) )
				$modelInfo = val( $modelsInfo, $moduleInfo[ 'default-model' ] );
		}
		
		return $modelInfo;
	}
}