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
     * See https://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml.
     *
     * @var array
     */
    public static $codes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => '(Unused)',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
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
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
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
    private $headers;

    /**
     * @var array
     */
    private $cookies;

    /**
     * @var string
     */
    private $body;

    public function __construct()
    {
        $this->headers = [];
        $this->cookies = [];
    }

    /**
     * Gets one or all headers.
     *
     * @param string $index optional header to look up
     *
     * @return string|array|null
     */
    public function headers($index = null)
    {
        return ($index) ? array_value($this->headers, $index) : $this->headers;
    }

    /**
     * Sets a specific header.
     *
     * @param string $header
     * @param string $value
     *
     * @return $this
     */
    public function setHeader($header, $value)
    {
        $this->headers[$header] = $value;

        return $this;
    }

    /**
     * Gets the cookies that will be set with this response.
     *
     * @param string $index optional
     *
     * @return mixed
     */
    public function cookies($index = false)
    {
        return ($index) ? array_value($this->cookies, $index) : $this->cookies;
    }

    /**
     * Sets a cookie with the same signature as PHP's setcookie().
     *
     * @param string $name
     * @param string $value
     * @param int    $expire
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     * @param bool   $httponly
     *
     * @return $this
     */
    public function setCookie($name, $value = '', $expire = 0, $path = '', $domain = '', $secure = false, $httponly = false)
    {
        $this->cookies[$name] = [$value, $expire, $path, $domain, $secure, $httponly];

        return $this;
    }

    /**
     * Sets the HTTP version.
     *
     * @param string $version HTTP version
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;

        return $this->setHeader('Content-Type', $contentType.'; charset=utf-8');
    }

    /**
     * Sets the response body.
     *
     * @param string $body
     *
     * @return $this
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
     * @param View $view view to render
     *
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function redirect($url, $code = 302, Request $req = null)
    {
        // handle relative URL redirects
        if (substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://' && substr($url, 0, 2) != '//') {
            if (!$req) {
                $req = Request::createFromGlobals();
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
     * Sends the headers to the client.
     *
     * @return $this
     */
    public function sendHeaders()
    {
        // check if headers have already been sent
        if (headers_sent()) {
            return $this;
        }

        // send status code
        header('HTTP/'.$this->version.' '.$this->code.' '.array_value(self::$codes, $this->code), true, $this->code);

        // send other headers
        foreach ($this->headers as $header => $value) {
            header("$header: $value", false, $this->code);
        }

        return $this;
    }

    /**
     * Sends the cookies to the client.
     *
     * @return $this
     */
    public function sendCookies()
    {
        // check if headers have already been sent
        if (headers_sent()) {
            return $this;
        }

        // set cookies
        foreach ($this->cookies as $name => $cookie) {
            list($value, $expire, $path, $domain, $secure, $httponly) = $cookie;
            setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
        }

        return $this;
    }

    /**
     * Sends the content to the client.
     *
     * @return $this
     */
    public function sendBody()
    {
        // 204 No Content should have an empty body
        if ($this->code == 204) {
            return $this;
        }

        if (empty($this->body)) {
            $this->body = array_value(self::$codes, $this->code);
        }

        echo $this->body;

        return $this;
    }

    /**
     * Sends the response to the client.
     *
     * @return $this
     */
    public function send()
    {
        $this->sendHeaders()
             ->sendCookies()
             ->sendBody();

        if (function_exists('fastcgi_finish_request') ||
            function_exists(__NAMESPACE__.'\fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        return $this;
    }
}
