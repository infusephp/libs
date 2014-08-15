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

class Response
{
	static $codes = [  
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
	];
	
	private $code;
	private $contentType;
	private $body;
	private $app;
	
	/**
	 * Constructs a new response
	 *
	 */
	public function __construct( Container $app )
	{
		$this->code = 200;
		$this->app = $app;
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
	public function render( $template, $parameters = [] )
	{	
		// deal with relative paths when using modules
		// TODO this is a hack
		if( substr( $template, 0, 1 ) != '/' )
		{
			// check if called from a controller
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			
			if( isset( $backtrace[ 1 ] ) )
			{
				$class = Util::array_value( $backtrace[ 1 ], 'class' );
				if( strpos( $class, 'app\\' ) !== false && defined( 'INFUSE_APP_DIR' ) )
				{
					$parts = explode( '\\', $class );
					$module = $parts[ 1 ];

					$moduleViewsDir = INFUSE_APP_DIR . '/' . $module . '/views';

					$newTemplate = $moduleViewsDir . '/' . $template . '.tpl';
					if( file_exists( $newTemplate ) )
					{
						$parameters[ 'moduleViewsDir' ] = $moduleViewsDir;
						$template = $newTemplate;
					}
				}
			}
		}
		
		$engine = $this->app[ 'view_engine' ];
		$engine->assignData( $parameters );
		
		$this->body = $engine->render( $template );
		
		return true;
	}
	
	/**
	 * Performs a 302 redirect to a given URL.
	 * NOTE: this will exit the script if the exitAndSetHeaders flag is set
	 *
	 * @param string $url URL we redirect to
	 * @param Request $req
	 * @param boolean $exitAndSetHeaders set the header and exit?
	 *
	 * @return string location header (if exitAndSetHeaders is false)
	 */
	public function redirect( $url, Request $req = null, $exitAndSetHeaders = true )
	{
		if( !$req )
			$req = new Request();

		// handle relative urls
		if( substr( $url, 0, 7 ) != 'http://' && substr( $url, 0, 8 ) != 'https://' && substr( $url, 0, 2 ) != '//' )
		{
			// redirect relative to the requested host name
			// and not the host name php thinks we are (HTTP_HOST vs SERVER_NAME)
			$url = $req->headers( 'host' ) . '/' . $req->basePath() . '/' . urldecode( $url );

			// protocol-agnostic
			$url = '//' . preg_replace( '/\/{2,}/', '/', $url );
		}

		$loc = 'Location: ' . $url;
		
		if( !$exitAndSetHeaders )
			return $loc;

		header( 'X-Powered-By: infuse' );
		header( $loc );

		exit;
	}

	/**
	 * Sends the response using the given information.
	 * NOTE: this will exit the script if the exit flag is set
	 *
	 * @param Request $req request object associated with the response
	 * @param boolean $exit
	 * @param boolean $setHeaders
	 */
	public function send( $req = null, $exit = true, $setHeaders = true )
	{
		if( !$req )
			$req = new Request();

		if( $req->isCli() )
		{
			echo $this->body;

			if( $exit )
				exit;

			return;
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
	
		$headers = [
			'HTTP/1.1 ' . $this->code . ' ' . self::$codes[$this->code],
			'Content-type: ' . $contentType . '; charset=utf-8',
			'X-Powered-By: infuse' ];

		if( $setHeaders )
		{
			foreach( $headers as $header )
				header( $header );
		}
		
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
					$message = self::$codes[ $this->code ];
				break;
			}
			
			if( $contentType == 'text/html' )
			{
				$this->render( 'error', [
					'message' => $message,
					'errorCode' => $this->code,
					'title' => $this->code,
					'errorMessage' => $message ] );
				
				echo $this->body;
			}
		}
		
		if( $exit )
			exit;
	}
}