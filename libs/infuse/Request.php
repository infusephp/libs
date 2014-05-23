<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.21
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class Request
{
	private $params;
	private $query;
	private $request;
	private $cookies;
	private $session;
	private $files;
	private $server;
	private $headers;
	private $accept;
	private $charsets;
	private $languages;
	private $basePath;
	private $path;
	private $paths;
	
	/**
	 * Constructs a request.
	 *
	 * @param array $query defaults to $_GET
	 * @param array $request defaults to php://input
	 * @param array $cookies defaults to $_COOKIE
	 * @param array $files defaults to $_FILES
	 * @param array $server defaults to $_SERVER
	 * @param array $session defaults to $_SESSION
	 */
	function __construct( $query = null, $request = null, $cookies = null, $files = null, $server = null, $session = null )
	{
		$this->params = [];
		
		if( $query )
			$this->query = $query;
		else
			$this->query = $_GET;
				
		if( $cookies )
			$this->cookies = $cookies;
		else
			$this->cookies = $_COOKIE;

		if( $files )
			$this->files = $files;
		else
			$this->files = $_FILES;
		
		if( !$server )
			$server = $_SERVER;
		
		if( $session )
			$this->session = $session;
		else if( isset( $_SESSION ) )
			$this->session = $_SESSION;
		else
			$this->session = [];
		
		$this->server = array_replace( [
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'infuse/1.X',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => time()
        ], $server );

		// remove slash in front of requested url
		$this->server[ 'REQUEST_URI' ] = substr_replace( Util::array_value( $this->server, 'REQUEST_URI' ), '', 0, 1 );
		
		// figure out the base path and REQUEST_URI
		$this->basePath = '/';

		if( isset( $this->server[ 'DOCUMENT_URI' ] ) )
		{
			$docParts = explode( '/', substr_replace( $this->server[ 'DOCUMENT_URI' ], '', 0, 1 ) );
			$uriParts = explode( '/', $this->server[ 'REQUEST_URI' ] );
			
			$basePaths = [];

			// uriParts = uriParts - $docParts
			// basePaths = docParts - uriParts
			foreach( $uriParts as $key => $part )
			{
				if( isset( $docParts[ $key ] ) && $docParts[ $key ] == $part )
				{
					$basePaths[] = $uriParts[ $key ];
					unset( $uriParts[ $key ] );
				}
				
				if( strpos( $part, '.php' ) !== false )
					break;
			}
			
			// ignore a trailing "/"
			end( $uriParts );
			$key = key( $uriParts );
			if( empty( $uriParts[ $key ] ) )
				unset( $uriParts[ $key ] );
			
			// strip base path from REQUEST_URI
			$this->server[ 'REQUEST_URI' ] = implode( '/', $uriParts );

			$this->basePath .= implode( '/', $basePaths );
		}

		// parse url
		$this->setPath( Util::array_value( $this->server, 'REQUEST_URI' ) );
                
        // parse headers
        $this->headers = $this->parseHeaders( $this->server );
        
        // accept header
		$this->accept = $this->parseAcceptHeader( Util::array_value( $this->headers, 'ACCEPT' ) );
		
		// accept charsets header
		$this->charsets = $this->parseAcceptHeader( Util::array_value( $this->headers, 'ACCEPT_CHARSET' ) );
		
		// accept language header
		$this->languages = $this->parseAcceptHeader( Util::array_value( $this->headers, 'ACCEPT_LANGUAGE' ) );
				
		// parse request body
		$this->request = $request;

		if( !$request && in_array( $this->method(), [ 'POST', 'PUT' ] ) )
		{
			$contentType = $this->contentType();

			// content-type: multipart/form-data
			if( strpos( $contentType, 'multipart/form-data' ) !== false )
				$this->request = $_REQUEST;
			else
			{
				$body = file_get_contents( 'php://input' );

				// content-type: application/json
				if( strpos( $contentType, 'application/json') !== false )
					$this->request = json_decode( $body, true );
				// content-type: text/plain
				else if( strpos( $contentType, 'text/plain' ) !== false )
					$this->request = $body;
				// otherwise, query string
				else
					parse_str( $body, $this->request );
			}
		}

		// DELETE and PUT requests can come through POST
		$requestMethodFromPost = Util::array_value( (array)$this->request, 'method' );
		if( $this->method() == 'POST' &&
			in_array( $requestMethodFromPost, [ 'PUT', 'DELETE' ] ) )
		{
			$this->server[ 'REQUEST_METHOD' ] = $requestMethodFromPost;
		}
	}
	
	/** 
	 * Sets the path for the request and parses it.
	 *
	 * @param string $path i.e. /users/10/comments
	 */
	function setPath( $path )
	{
		// get the base path
		$this->path = current(explode('?', $path));
		if( substr( $this->path, 0, 1 ) != '/' )
			$this->path = '/' . $this->path;
		
		// break the URL into paths
		$this->paths = explode( '/', $this->path );
		if( $this->paths[ 0 ] == '' )
			array_splice( $this->paths, 0, 1 );	
	}
	
	/**
	 * Gets the ip address associated with the request.
	 *
	 * @return string ip address
	 */
	function ip()
	{
		return Util::array_value( $this->server, 'REMOTE_ADDR' );
	}
	
	/**
	 * Gets the protocol associated with the request
	 *
	 * @return string https or http
	 */	
	function protocol()
	{
		$https = Util::array_value( $this->server, 'HTTPS' );
		if( $https && $https !== 'off' )
			return 'https';
		
		return ($this->port() == 443) ? 'https' : 'http';
	}
	
	/**
	 * Checks if the request uses a secure protocol.
	 *
	 * @return boolean
	 */
	function isSecure()
	{
		return $this->protocol() == 'https';
	}

	/**
	 * Gets the port associated with the request.
	 *
	 * @param int port number
	 */
	function port()
	{
		return Util::array_value( $this->server, 'SERVER_PORT' );
	}

	/**
	 * Gets values from the request headers
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function headers( $index = false )
	{
		return ($index) ? Util::array_value( $this->headers, strtoupper( $index ) ) : $this->headers;
	}

	/**
	 * @deprecated
	 */
	function header( $index = false )
	{
		return $this->headers( $index );
	}
	
	/**
	 * Gets the username from the auth headers associated with the request
	 *
	 * @return string username
	 */
	function user()
	{
		return Util::array_value( $this->headers, 'PHP_AUTH_USER' );
	}
	
	/**
	 * Gets the password from the auth headers associated with the request
	 *
	 * @return string password
	 */
	function password()
	{
		return Util::array_value( $this->headers, 'PHP_AUTH_PW' );
	}
	
	/**
	 * Gets the host name (or ip) for the request.
	 *
	 * @return string host
	 */
	function host()
	{
		if( !$host = Util::array_value( $this->headers, 'HOST' ) )
		{
            if( !$host = Util::array_value( $this->server, 'SERVER_NAME' ) )
                $host = Util::array_value( $this->server, 'SERVER_ADDR', '' );
        }
        
        // trim and remove port number from host
        // host is lowercase as per RFC 952/2181
        $host = strtolower(preg_replace('/:\d+$/', '', trim($host)));        
	
		return $host;
	}
	
	/**
	 * Gets the complete url associated with the request.
	 *
	 * @param string url
	 */
	function url()
	{
		$port = $this->port();
		if( !in_array( $port, [ 80, 443 ] ) )
			$port = ':' . $port;
		else
			$port = '';

		$path = str_replace( '//', '/', $this->basePath() . $this->path() );
				
		return $this->protocol() . '://' . $this->host() . $port . $path;
	}
	
	/**
	 * Returns each component of the requested path.
	 *
	 * @return array paths
	 */
	function paths( $index = false )
	{
		return (is_numeric($index)) ? Util::array_value( $this->paths, $index ) : $this->paths;
	}

	/**
	 * Gets the path associated with the request minus the bse path
	 *
	 */
	function path()
	{
		return $this->path;
	}

	
	/**
	 * Gets the base path associated with the request. i.e. /comments/10
	 * Useful if the entry point to the request was not located in the root directory
	 * i.e. /blog/index.php returns /blog or /index.php returns an empty string
	 *
	 * @param string base path
	 */
	function basePath()
	{
		return $this->basePath;
	}
	
	/**
	 * Gets the method requested.
	 *
	 * @param string method (i.e. GET, POST, DELETE, PUT, PATCH)
	 */
	function method()
	{
		return Util::array_value( $this->server, 'REQUEST_METHOD' );
	}
	
	/**
	 * Gets the content type the request was sent with.
	 *
	 * @param string content type
	 */
	function contentType()
	{
		return Util::array_value( $this->server, 'CONTENT_TYPE' );
	}
	
	/**
	 * Gets the parsed accepts header from the request.
	 *
	 * @return array accept formats
	 */
	function accepts()
	{
		return $this->accept;
	}
	
	/**
	 * Gets the parsed charsets header from the request.
	 *
	 * @return array charsets
	 */
	function charsets()
	{
		return $this->charsets;
	}
	
	/**
	 * Gets the parsed language header from the request.
	 *
	 * @return array langauges
	 */
	function languages()
	{
		return $this->languages;
	}
	
	/**
	 * Gets the user agent string from the request.
	 *
	 * @return string user agent string
	 */
	function agent()
	{
		return Util::array_value( $this->server, 'HTTP_USER_AGENT' );
	}
	
	/**
	 * Checks if the request accepts HTML
	 *
	 * @return boolean
	 */
	function isHtml()
	{
		foreach( $this->accept as $type )
		{
			if( $type[ 'main_type' ] == 'text' && $type[ 'sub_type' ] == 'html' )
				return true;
		}
		
		return false;
	}
	
	/**
	 * Checks if the request accepts JSON
	 *
	 * @return boolean
	 */
	function isJson()
	{
		foreach( $this->accept as $type )
		{
			if( $type[ 'main_type' ] == 'application' && $type[ 'sub_type' ] == 'json' )
				return true;
		}
		
		return false;
	}
	
	/**
	 * Checks if the request accepts XML
	 *
	 * @return boolean
	 */
	function isXml()
	{
		foreach( $this->accept as $type )
		{
			if( $type[ 'main_type' ] == 'application' && $type[ 'sub_type' ] == 'xml' )
				return true;
		}
		
		return false;	
	}
	
	/**
	 * Checks if the request was sent using AJAX
	 *
	 * @return boolean
	 */
	function isXhr()
	{
		return Util::array_value( $this->headers, 'X-Requested-With' ) == 'XMLHttpRequest';
	}
	
	/**
	 * Checks if the request is for the API. This is used to decide if a request is stateless or not.
	 *
	 * @return boolean
	 */
	function isApi()
	{
		return isset( $this->headers[ 'AUTHORIZATION' ] );
	}
	
	/**
	 * Checks if the request was made over the command line.
	 *
	 * @return boolean
	 */
	function isCli()
	{
		return defined( 'STDIN' );
	}
	
	/**
	 * Gets the parameters associated with the request.
	 * These come from the router or other parts of the framework,
	 * not the HTTP request itself. Request params are a convenient way
	 * to pass data between controllers.
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function params( $index = false )
	{
		return ($index) ? Util::array_value( $this->params, $index ) : $this->params;
	}
	
	/**
	 * Adds parameters to the request.
	 *
	 * @param array $params parameters to add
	 */
	function setParams( $params = [] )
	{
		$this->params = array_replace( $this->params, (array)$params );
	}
	
	/**
	 * Gets values from the query portion of the request. (i.e. GET parameters)
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function query( $index = false )
	{
		return ($index) ? Util::array_value( $this->query, $index ) : $this->query;
	}
	
	/**
	 * Gets values from the body of the request. (i.e. POST, PUT parameters)
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function request( $index = false )
	{
		return ($index) ? Util::array_value( $this->request, $index ) : $this->request;
	}
	
	/**
	 * Gets the cookies associated with the request.
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function cookies( $index = false )
	{
		return ($index) ? Util::array_value( $this->cookies, $index ) : $this->cookies;
	}
	
	/**
	 * Sets a cookie with the same signature as PHP's setcookie()
	 *
	 * @param string $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param boolean $secure
	 * @param boolean $httponly
	 * @param boolean $mock
	 *
	 * @return boolean success
	 */
	function setCookie( $name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httponly = false, $mock = false )
	{
		if( !$mock )
		{
			if( setcookie( $name, $value, $expire, $path, $domain, $secure, $httponly ) )
			{
				$this->cookies[ $name ] = $value;
				
				return true;
			}
			
			return false;
		}
		else
		{
			$this->cookies[ $name ] = $value;
			
			return true;
		}
	}
	
	/**
	 * Gets the files associated with the request. (i.e. $_FILES)
	 * 
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function files( $index = false )
	{
		return ($index) ? Util::array_value( $this->files, $index ) : $this->files;
	}
	
	/**
	 * Gets the session variables associated with the request.
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	function session( $index = false )
	{
		return ($index) ? Util::array_value( $this->session, $index ) : $this->session;
	}
	
	/**
	 * Sets session variable(s)
	 *
	 * @param array|string $key key-value or just a key
	 * @param mixed $value value to set if not supplying key-value map in first argument
	 */
	function setSession( $key, $value = null )
	{
		if( is_array( $key ) )
		{
			foreach( $key as $k => $v )
			{
				$_SESSION[ $k ] = $v;
				$this->session[ $k ] = $v;
			}
		}
		else
		{
			$_SESSION[ $key ] = $value;
			$this->session[ $key ] = $value;
		}
	}
	
	/**
	 * Destroys the session for the request
	 */
	function destroySession()
	{
		$_SESSION = [];
		$this->session = [];
	}

	/**
	 * Gets the CLI arguments associated with the request.
	 *
	 * @param int $index optional
	 *
	 * @return mixed
	 */
	function cliArgs( $index = false )
	{
		if( !$this->isCli() )
			return false;
		
		return ($index) ? Util::array_value( $this->server, "argv.$index" ) : Util::array_value( $this->server, 'argv' );
	}
	
	////////////////////////////////////
	// PRIVATE METHODS
	////////////////////////////////////
	
	private function parseHeaders( $parameters )
	{
        $headers = [];
        foreach( $parameters as $key => $value )
        {
            if( 0 === strpos( $key, 'HTTP_' ) )
                $headers[ substr( $key, 5 ) ] = $value;
            // CONTENT_* are not prefixed with HTTP_
            elseif( in_array( $key, [ 'CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE' ] ) )
                $headers[ $key ] = $value;
        }

        if( isset( $parameters[ 'PHP_AUTH_USER' ] ) )
        {
            $headers[ 'PHP_AUTH_USER' ] = $parameters[ 'PHP_AUTH_USER' ];
            $headers[ 'PHP_AUTH_PW' ] = isset($parameters['PHP_AUTH_PW']) ? $parameters[ 'PHP_AUTH_PW' ] : '';
        }
        else
        {
            /*
			* php-cgi under Apache does not pass HTTP Basic user/pass to PHP by default
			* For this workaround to work, add these lines to your .htaccess file:
			* RewriteCond %{HTTP:Authorization} ^(.+)$
			* RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
			*
			* A sample .htaccess file:
			* RewriteEngine On
			* RewriteCond %{HTTP:Authorization} ^(.+)$
			* RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
			* RewriteCond %{REQUEST_FILENAME} !-f
			* RewriteRule ^(.*)$ app.php [QSA,L]
			*/

            $authorizationHeader = null;
            if( isset( $parameters[ 'HTTP_AUTHORIZATION' ] ) )
                $authorizationHeader = $parameters[ 'HTTP_AUTHORIZATION' ];
            elseif( isset( $parameters[ 'REDIRECT_HTTP_AUTHORIZATION' ] ) )
                $authorizationHeader = $parameters[ 'REDIRECT_HTTP_AUTHORIZATION' ];

            // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
            if( ( null !== $authorizationHeader ) && ( 0 === stripos( $authorizationHeader, 'basic' ) ) )
            {
                $exploded = explode( ':', base64_decode( substr( $authorizationHeader, 6 ) ) );
                if( count( $exploded ) == 2 )
                    list( $headers[ 'PHP_AUTH_USER' ], $headers[ 'PHP_AUTH_PW' ] ) = $exploded;
            }
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if( isset( $headers[ 'PHP_AUTH_USER' ] ) )
        {
        	$userPassStr = $headers[ 'PHP_AUTH_USER' ] . ':' . $headers[  'PHP_AUTH_PW' ];
        	$headers[ 'AUTHORIZATION' ] = 'Basic ' . base64_encode( $userPassStr );
        }

        return $headers;
	}
	
	// Credit to Jurgens du Toit: http://jrgns.net/parse_http_accept_header/
	private function parseAcceptHeader( $acceptStr = '' )
	{
		$return = null;

		$types = explode(',', $acceptStr);
		$types = array_map('trim', $types);
		foreach ($types as $one_type) {
			$one_type = explode(';', $one_type);
			$type = array_shift($one_type);
			if ($type) {
				list($precedence, $tokens) = $this->parseAcceptHeaderOptions($one_type);
				$typeArr = explode('/', $type);
				if( !isset( $typeArr[ 1 ] ) )
					$typeArr[ 1 ] = '';
				list($main_type, $sub_type) = array_map('trim', $typeArr);				
				$return[] = [
					'main_type' => $main_type,
					'sub_type' => $sub_type,
					'precedence' => (float)$precedence,
					'tokens' => $tokens];
			}
		}
		
		usort($return, [$this, 'compareMediaRanges']);
		
		return $return;
	}
	
	private function parseAcceptHeaderOptions( $type_options )
	{
		$precedence = 1;
		$tokens = [];
		if (is_string($type_options)) {
			$type_options = explode(';', $type_options);
		}
		$type_options = array_map('trim', $type_options);
		foreach ($type_options as $option) {
			$option = explode('=', $option);
			$option = array_map('trim', $option);
			if ($option[0] == 'q') {
				$precedence = $option[1];
			} else {
				$tokens[$option[0]] = Util::array_value( $option, 1 );
			}
		}
		$tokens = count ($tokens) ? $tokens : false;
		return [$precedence, $tokens];
	}
	
	private function compareMediaRanges( $one, $two )
	{
		if ($one['main_type'] != '*' && $two['main_type'] != '*') {
			if ($one['sub_type'] != '*' && $two['sub_type'] != '*') {
				if ($one['precedence'] == $two['precedence']) {
					if (count($one['tokens']) == count($two['tokens'])) {
						return 0;
					} else if (count($one['tokens']) < count($two['tokens'])) {
						return 1;
					} else {
						return -1;
					}
				} else if ($one['precedence'] < $two['precedence']) {
					return 1;
				} else {
					return -1;
				}
			} else if ($one['sub_type'] == '*') {
				return 1;
			} else {
				return -1;
			}
		} else if ($one['main_type'] == '*') {
			return 1;
		} else {
			return -1;
		}
	}
}