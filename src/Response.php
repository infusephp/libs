<?php

/**
 * @author Jared King <j@jaredtking.com>
 *
 * @link http://jaredtking.com
 *
 * @copyright 2015 Jared King
 * @license MIT
 */
namespace Infuse;

class Response
{
    /**
     * @staticvar array
     */
    public static $codes = [
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
        505 => 'HTTP Version Not Supported',
    ];

    /**
     * @var string
     */
    private $version = '1.1';

    /**
     * @var int
     */
    private $code = 200;

    /**
     * @var string
     */
    private $contentType = 'text/html';

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $body;

    /**
     * Gets one or all headers.
     *
     * @param string $index optional header to look up
     *
     * @return string|null
     */
    public function headers($index = null)
    {
        return ($index) ? Utility::array_value($this->headers, $index) : $this->headers;
    }

    /**
     * Sets a specific header.
     *
     * @param string $header
     * @param string $value
     *
     * @return Response
     */
    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * Sets the HTTP version.
     *
     * @param string $version HTTP version
     *
     * @return Response
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Gets the HTTP version.
     *
     * @return string version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Sets the HTTP status code for the response.
     *
     * @param int $code
     *
     * @return Response
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Gets the HTTP status code for the response.
     *
     * @return int code
     */
    public function getCode()
    {
        return $this->code;
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
     *
     * @return Response
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this->setHeader('Content-type', $contentType.'; charset=utf-8');
    }

    /**
     * Sets the response body.
     *
     * @param string $body
     *
     * @return Response
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Gets the response body.
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Convenience method to render a View and set the body to the result.
     *
     * @param string $template   template to render
     * @param array  $parameters parameters to pass to the template
     *
     * @return Response
     */
    public function render(View $view)
    {
        return $this->setBody($view->render());
    }

    /**
     * Convenience method to send a JSON response.
     *
     * @param object|array $obj object to be encoded
     *
     * @return Response
     */
    public function json($obj)
    {
        return $this->setContentType('application/json')
                    ->setBody(json_encode($obj));
    }

    /**
     * Convenience method to send a redirect response.
     *
     * @param string  $url  URL we redirect to
     * @param int     $code HTTP status code to send
     * @param Request $req  request object for getting requested host information
     *
     * @return Response
     */
    public function redirect($url, $code = 302, Request $req = null)
    {
        // handle relative URL redirects
        if (substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://' && substr($url, 0, 2) != '//') {
            if (!$req) {
                $req = new Request();
            }
            $url = $req->headers('host').'/'.$req->basePath().'/'.urldecode($url);

            // here we use a protocol-agnostic URL
            $url = '//'.preg_replace('/\/{2,}/', '/', $url);
        }

        $eUrl = htmlspecialchars($url);
        $body = '<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="refresh" content="1;url='.$eUrl.'" />
        <title>Redirecting to '.$eUrl.'</title>
    </head>
    <body>
        Redirecting to <a href="'.$eUrl.'">'.$eUrl.'</a>.
    </body>
</html>';

        return $this->setCode($code)
                    ->setHeader('Location', $url)
                    ->setBody($body);
    }

    /**
     * Sends the headers.
     *
     * @return Response
     */
    public function sendHeaders()
    {
        // check if headers have already been sent
        if (headers_sent()) {
            return $this;
        }

        // send status code
        header('HTTP/'.$this->version.' '.$this->code.' '.self::$codes[$this->code], true, $this->code);

        // send other headers
        foreach ($this->headers as $header => $value) {
            header("$header: $value", false, $this->code);
        }

        return $this;
    }

    /**
     * Sends the content.
     *
     * @return Response
     */
    public function sendBody()
    {
        if (empty($this->body)) {
            $this->body = self::$codes[$this->code];
        }

        echo $this->body;

        return $this;
    }

    /**
     * Sends the response using the given information.
     *
     * @return Response
     */
    public function send()
    {
        return $this->sendHeaders()
                    ->sendBody();
    }

    /**
     * @deprecated use json()
     */
    public function setBodyJson($obj)
    {
        return $this->json($obj);
    }
}
