<?php
/**
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

class Request
{
	private $params;
	private $query;
	private $request;
	private $cookies;
	private $files;
	private $server;
	private $headers;
	private $accept;
	private $charsets;
	private $languages;
	private $basePath;
	private $paths;
	
	/**
	 * Constructs a request.
	 *
	 * @param array $query defaults to $_GET
	 * @param array $request defaults to php://input
	 * @param array $cookies defaults to $_COOKIE
	 * @param array $files defaults to $_FILES
	 * @param array $server defaults to $_SERVER
	 */
	public function __construct( $query = null, $request = null, $cookies = null, $files = null, $server = null )
	{
		$this->params = array();
		
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
		
		$this->server = array_replace(array(
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
        ), $server);

		// Remove slash in front of requested url
		$this->server['REQUEST_URI'] = substr_replace (val( $_SERVER, 'REQUEST_URI' ), "", 0, 1);
        
		// sometimes the DELETE and PUT request method is set by forms via POST
		if( $this->server[ 'REQUEST_METHOD' ] == 'POST' && $requestMethod = val( $request, 'method' )  && in_array( $requestMethod, array( 'PUT', 'DELETE' ) ) )
			$this->server[ 'REQUEST_METHOD' ] = $requestMethod;
        
        $this->headers = $this->parseHeaders( $this->server );
        
        // accept header
		$this->accept = $this->parseAcceptHeader( val( $this->headers, 'ACCEPT' ) );
		
		// accept Charsets header
		$this->charsets = $this->parseAcceptHeader( val( $this->headers, 'ACCEPT_CHARSET' ) );
		
		// accept Language header
		$this->languages = $this->parseAcceptHeader( val( $this->headers, 'ACCEPT_LANGUAGE' ) );
		
		$this->setPath( val( $this->server, 'REQUEST_URI' ) );
			
		$this->request = array();
		
		if( $request )
			$this->request = $request;
		// decode request body for POST and PUT
		else if( in_array( $this->method(), array( 'POST', 'PUT' ) ) )
		{
			$body = file_get_contents( 'php://input' );
			$contentType = $this->contentType();

			// parse json
			if( strpos( $contentType, 'application/json') !== false )
				$this->request = json_decode( $body, true );
			// parse multipart form data
			else if( strpos( $contentType, 'multipart/form-data' ) !== false )
				$this->request = $_REQUEST;
			// plain text
			else if( strpos( $contentType, 'text/plain' ) !== false )
				$this->request = $body;
			// parse query string
			else
				parse_str( $body, $this->request );
		}
	}
	
	/** 
	 * Sets the path for the request and parses it.
	 *
	 * @param string $path i.e. /users/1/comments
	 */
	public function setPath( $path )
	{
		// get the base path
		$this->basePath = current(explode('?', $path));
		if( substr( $this->basePath, 0, 1 ) != '/' )
			$this->basePath = '/' . $this->basePath;
		
		// break the URL into paths
		$this->paths = explode( '/', $this->basePath );
		if( $this->paths[ 0 ] == '' )
			array_splice( $this->paths, 0, 1 );	
	}
	
	/**
	 * Gets the ip address associated with the request.
	 *
	 * @return string ip address
	 */
	public function ip()
	{
		return val( $this->server, 'REMOTE_ADDR' );
	}
	
	/**
	 * Gets the protocol associated with the request
	 *
	 * @return string https or http
	 */	
	public function protocol()
	{
	
		if( val( $this->server, 'HTTPS' ) )
			return 'https';
		
		return ($this->port() == 443) ? 'https' : 'http';
	}
	
	/**
	 * Checks if the request uses a secure protocol.
	 *
	 * @return boolean
	 */
	public function isSecure()
	{
		return $this->protocol() == 'https';
	}

	/**
	 * Gets the port associated with the request.
	 *
	 * @param int port number
	 */
	public function port()
	{
		return val( $this->server, 'SERVER_PORT' );
	}

	/**
	 * Gets values from the request headers
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	public function header( $index = false )
	{
		return ($index) ? val( $this->headers, strtoupper( $index ) ) : $this->headers;
	}
	
	/**
	 * Gets the username from the auth headers associated with the request
	 *
	 * @return string username
	 */
	public function user()
	{
		return val( $this->headers, 'PHP_AUTH_USER' );
	}
	
	/**
	 * Gets the password from the auth headers associated with the request
	 *
	 * @return string password
	 */
	public function password()
	{
		return val( $this->headers, 'PHP_AUTH_PW' );
	}
	
	/**
	 * Gets the host name (or ip) for the request.
	 *
	 * @return string host
	 */
	public function host()
	{
		if( !$host = val( $this->headers, 'HOST' ) )
		{
            if( !$host = val( $this->server, 'SERVER_NAME' ) )
                $host = val( $this->server, 'SERVER_ADDR', '' );
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
	public function url()
	{
		$port = $this->port();
		if( !in_array( $port, array( 80, 443 ) ) )
			$port = ':' . $port;
		else
			$port = '';
				
		return $this->protocol() . '://' . $this->host() . $port . $this->basePath();
	}
	
	/**
	 * Returns each component of the requested path.
	 *
	 * @return array paths
	 */
	public function paths( $index = false )
	{
		return (is_numeric($index)) ? val( $this->paths, $index ) : $this->paths;
	}
	
	/**
	 * Gets the base path associated with the request. i.e. /comments/10
	 *
	 * @param string base path
	 */
	public function basePath()
	{
		return $this->basePath;
	}
	
	/**
	 * Gets the method requested.
	 *
	 * @param string method (i.e. GET, POST, DELETE, PUT, PATCH)
	 */
	public function method()
	{
		return val( $this->server, 'REQUEST_METHOD' );
	}
	
	/**
	 * Gets the content type the request was sent with.
	 *
	 * @param string content type
	 */
	public function contentType()
	{
		return val( $this->server, 'CONTENT_TYPE' );
	}
	
	/**
	 * Gets the parsed accepts header from the request.
	 *
	 * @return array accept formats
	 */
	public function accepts()
	{
		return $this->accept;
	}
	
	/**
	 * Gets the parsed charsets header from the request.
	 *
	 * @return array charsets
	 */
	public function charsets()
	{
		return $this->charsets;
	}
	
	/**
	 * Gets the parsed language header from the request.
	 *
	 * @return array langauges
	 */
	public function languages()
	{
		return $this->languages;
	}
	
	/**
	 * Checks if the request accepts HTML
	 *
	 * @return boolean
	 */
	public function isHtml()
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
	public function isJson()
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
	public function isXml()
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
	public function isXhr()
	{
		return val( $this->headers, 'X-Requested-With' ) == 'XMLHttpRequest';
	}
	
	/**
	 * Checks if the request is for the API. This is used to decide if a request is stateless or not.
	 *
	 * @return boolean
	 */
	public function isApi()
	{
		return isset( $this->headers[ 'AUTHORIZATION' ] );
	}
	
	/**
	 * Checks if the request was made over the command line.
	 *
	 * @return boolean
	 */
	public function isCli()
	{
		return defined('STDIN');
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
	public function params( $index = false )
	{
		return ($index) ? val( $this->params, $index ) : $this->params;
	}
	
	/**
	 * Adds parameters to the request.
	 *
	 * @param array $params parameters to add
	 */
	public function setParams( $params = array() )
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
	public function query( $index = false )
	{
		return ($index) ? val( $this->query, $index ) : $this->query;
	}
	
	/**
	 * Gets values from the body of the request. (i.e. POST, PUT parameters)
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	public function request( $index = false )
	{
		return ($index) ? val( $this->request, $index ) : $this->request;
	}
	
	/**
	 * Gets the cookies associated with the request.
	 *
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	public function cookies( $index = false )
	{
		return ($index) ? val( $this->cookies, $index ) : $this->cookies;
	}
	
	/**
	 * Gets the files associated with the request. (i.e. $_FILES)
	 * 
	 * @param string $index optional
	 *
	 * @return mixed
	 */
	public function files( $index = false )
	{
		return ($index) ? val( $this->files, $index ) : $this->files;
	}
	
	////////////////////////////////////
	// PRIVATE METHODS
	////////////////////////////////////
	
	private function parseHeaders( $parameters )
	{
        $headers = array();
        foreach ($parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (in_array($key, array('CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'))) {
                $headers[$key] = $value;
            }
        }

        if (isset($parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($parameters['PHP_AUTH_PW']) ? $parameters['PHP_AUTH_PW'] : '';
        } else {
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
            if (isset($parameters['HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authorizationHeader = $parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

            // Decode AUTHORIZATION header into PHP_AUTH_USER and PHP_AUTH_PW when authorization header is basic
            if ((null !== $authorizationHeader) && (0 === stripos($authorizationHeader, 'basic'))) {
                $exploded = explode(':', base64_decode(substr($authorizationHeader, 6)));
                if (count($exploded) == 2) {
                    list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                }
            }
        }

        // PHP_AUTH_USER/PHP_AUTH_PW
        if (isset($headers['PHP_AUTH_USER'])) {
            $headers['AUTHORIZATION'] = 'Basic '.base64_encode($headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW']);
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
				$return[] = array(
					'main_type' => $main_type,
					'sub_type' => $sub_type,
					'precedence' => (float)$precedence,
					'tokens' => $tokens);
			}
		}
		
		usort($return, array($this, 'compare_media_ranges'));
		
		return $return;
	}
	
	private function parseAcceptHeaderOptions( $type_options )
	{
		$precedence = 1;
		$tokens = array();
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
				$tokens[$option[0]] = $option[1];
			}
		}
		$tokens = count ($tokens) ? $tokens : false;
		return array($precedence, $tokens);
	}	
	
	private function compare_media_ranges( $one, $two )
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
	
	/**
	 * Credit to http://www.chlab.ch/blog/archives/php/manually-parse-raw-http-data-php
	 * Parse raw HTTP request data
	 *
	 * Pass in $a_data as an array. This is done by reference to avoid copying
	 * the data around too much.
	 *
	 * Any files found in the request will be added by their field name to the
	 * $data['files'] array.
	 *
	 * @param	string	Input request
	 * @param	string	Content type
	 * @param   array	Empty array to fill with data
	 * @return  array	Associative array of request data
	 */
	private function parse_raw_http_request($input, $contentType, array &$a_data)
	{
	  // grab multipart boundary from content type header
	  preg_match('/boundary=(.*)$/', $contentType, $matches);
	   
	  // content type is probably regular form-encoded
	  if (!count($matches))
	  {
	    // we expect regular puts to containt a query string containing data
	    parse_str(urldecode($input), $a_data);
	    return $a_data;
	  }
	   
	  $boundary = $matches[1];
	 
	  // split content by boundary and get rid of last -- element
	  $a_blocks = preg_split("/-+$boundary/", $input);
	  array_pop($a_blocks);
	       
	  // loop data blocks
	  foreach ($a_blocks as $id => $block)
	  {
	    if (empty($block))
	      continue;
	     
	    // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char
	     
	    // parse uploaded files
	    if (strpos($block, 'application/octet-stream') !== FALSE)
	    {
	      // match "name", then everything after "stream" (optional) except for prepending newlines
	      preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
	      $a_data['files'][$matches[1]] = $matches[2];
	    }
	    // parse all other fields
	    else
	    {
	      // match "name" and optional value in between newline sequences
	      preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
	      $a_data[$matches[1]] = $matches[2];
	    }
	  }
	}
}