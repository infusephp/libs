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

class Response
{
	static $codes = Array(  
		100 => 'Continue',  
		101 => 'Switching Protocols',  
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',  
		203 => 'Non-Authoritative Information',  
		204 => 'No Content',  
		205 => 'Reset Content',  
		206 => 'Partial Content',  
		300 => 'Multiple Choices',  
		301 => 'Moved Permanently',  
		302 => 'Found',  
		303 => 'See Other',  
		304 => 'Not Modified',  
		305 => 'Use Proxy',  
		306 => '(Unused)',  
		307 => 'Temporary Redirect',  
		400 => 'Bad Request',  
		401 => 'Unauthorized',  
		402 => 'Payment Required',  
		403 => 'Forbidden',  
		404 => 'Not Found',  
		405 => 'Method Not Allowed',  
		406 => 'Not Acceptable',  
		407 => 'Proxy Authentication Required',  
		408 => 'Request Timeout',  
		409 => 'Conflict',  
		410 => 'Gone',  
		411 => 'Length Required',  
		412 => 'Precondition Failed',  
		413 => 'Request Entity Too Large',  
		414 => 'Request-URI Too Long',  
		415 => 'Unsupported Media Type',  
		416 => 'Requested Range Not Satisfiable',  
		417 => 'Expectation Failed',  
		500 => 'Internal Server Error',  
		501 => 'Not Implemented',  
		502 => 'Bad Gateway',  
		503 => 'Service Unavailable',  
		504 => 'Gateway Timeout',  
		505 => 'HTTP Version Not Supported'  
	);
	
	private $code;
	private $contentType;
	private $body;
	
	/**
	 * Constructs a new response
	 *
	 */
	public function __construct()
	{
		$this->code = 200;
	}
	
	/**
	 * Sets the HTTP status code for the response
	 *
	 * @param int $code
	 */
	public function setCode( $code )
	{
		$this->code = $code;
	}
	
	/**
	 * Gets the HTTP status code for the response
	 * 
	 * @return int code
	 */
	public function getCode()
	{
		return $this->code;
	}
	
	/**
	 * Sets the response body.
	 *
	 * @param string $body
	 */
	public function setBody( $body )
	{
		$this->body = $body;
	}
	
	/**
	 * Gets the response body
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}
	
	/**
	 * Convenience method to send a JSON response.
	 *
	 * @param object $obj object to be encoded
	 */
	public function setBodyJson( $obj )
	{
		$this->setBody( json_encode( $obj ) );
		$this->contentType = 'application/json';
	}
	
	/**
	 * Gets the content type of the response.
	 *
	 * @return string content type
	 */
	public function getContentType()
	{
		return $this->contentType;
	}
	
	/**
	 * Sets the content type of the request.
	 *
	 * @param string $contentType content type
	 */
	public function setContentType( $contentType )
	{
		$this->contentType = $contentType;
	}
	
	/**
	 * Renders a template using the view engine and puts the result in the body.
	 *
	 * @param string $template template to render
	 * @param array $parameters parameters to pass to the template
	 */
	public function render( $template, $parameters = array() )
	{
		// add extension if left off
		if( substr( $template, -4, 4 ) != '.tpl' )
			$template .= '.tpl';

		// deal with relative paths
		if( substr( $template, 0, 1 ) != '/' )
		{
			// check if called from a controller
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			
			if( isset( $backtrace[ 1 ] ) )
			{
				if( strpos( $backtrace[ 1 ][ 'class' ], 'infuse\\controllers\\' ) !== false )
				{					
					$module = strtolower( str_replace( 'infuse\\controllers\\', '', $backtrace[ 1 ][ 'class' ] ) );
					
					$template = INFUSE_MODULES_DIR . '/' . $module . '/views/' . $template;
				}
			}
		}
			
		$parameters[ 'currentUser' ] = \infuse\models\User::currentUser();
		$parameters[ 'baseUrl' ] = ((Config::value('site','ssl-enabled'))?'https':'http') . '://' . Config::value('site','host-name') . '/';
	
		$engine = ViewEngine::engine();
		
		$engine->assignData( $parameters );
		
		$this->body = $engine->fetch( $template );
	}
	
	/**
	 * Performs a 302 redirect to a given URL. NOTE: this will exit the script.
	 *
	 * @param string $url URL to redirect to
	 */
	public function redirect( $url )
	{
		if( substr( $url, 0, 7 ) != 'http://' && substr( $url, 0, 8 ) != 'https://' )
		{
			$url = $_SERVER['HTTP_HOST'] . dirname ($_SERVER['PHP_SELF']) . '/' . urldecode( $url );
			$url = '//' . preg_replace('/\/{2,}/','/', $url);
		}
		
		header('X-Powered-By: infuse');
		header ("Location: " . $url);

		exit;
	}

	/**
	 * Sends the response using the given information.
	 *
	 * @param Request $req request object associated with the response
	 */
	public function send( $req = null )
	{
		if( !$req )
			$req = new Request();

		if( $req->isCli() )
		{
			echo $this->body;
			exit;
		}
	
		$contentType = $this->contentType;
		
		if( empty( $contentType ) )
		{
			// send back the first content type requested
			$accept = $req->accepts();
			
			$contentType = 'text/html';
			if( $req->isJson() )
				$contentType = 'application/json';
			else if( $req->isHtml() )
				$contentType = 'text/html';
			else if( $req->isXml() )
				$contentType = 'application/xml';			
		}
		
		// set the status
		header('HTTP/1.1 ' . $this->code . ' ' . self::$codes[$this->code]);
		// set the content type
		header('Content-type: ' . $contentType . '; charset=utf-8');
		// set the powered by
		header('X-Powered-By: infuse');
		
		if( !empty( $this->body ) )
		{
			// send the body
			echo $this->body;
		}
		// we need to create the body if none is passed
		else if( $this->code != 200 )
		{
			// create some body messages
			$message = '';
			
			// this is purely optional, but makes the pages a little nicer to read
			// for your users.  Since you won't likely send a lot of different status codes,
			// this also shouldn't be too ponderous to maintain
			switch( $this->code )
			{
				case 401:
					$message = 'You must be authorized to view this page.';
				break;
				case 404:
					$message = 'The requested URL was not found.';
				break;
				case 500:
					$message = 'The server encountered an error processing your request.';
				break;
				case 501:
					$message = 'The requested method is not implemented.';
				break;
				default:
					$message = self::$codes[$this->code];
				break;
			}
			
			if( $contentType == 'text/html' )
			{
				$this->render( 'error.tpl', array(
					'message' => $message,
					'errorCode' => $this->code,
					'title' => $this->code,
					'errorMessage' => $message ) );
				
				echo $this->body;
			}
		}
		
		exit;
	}
}