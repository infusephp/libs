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

class Request
{
    /**
     * @var array
     */
    private $params;

    /**
     * @var array
     */
    private $query;

    /**
     * @var array
     */
    private $request;

    /**
     * @var array
     */
    private $cookies;

    /**
     * @var array
     */
    private $session;

    /**
     * @var array
     */
    private $files;

    /**
     * @var array
     */
    private $server;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var array
     */
    private $accept;

    /**
     * @var array
     */
    private $charsets;

    /**
     * @var array
     */
    private $languages;

    /**
     * @var string
     */
    private $basePath;

    /**
     * @var string
     */
    private $path;

    /**
     * @var array
     */
    private $paths;

    /**
     * Creates a request from the PHP globals.
     *
     * @return Request
     */
    public static function createFromGlobals()
    {
        $server = $_SERVER;

        // parse the request body based on the content type
        $request = [];
        if (in_array(array_value($server, 'REQUEST_METHOD'), ['POST', 'PUT', 'PATCH'])) {
            $contentType = array_value($server, 'CONTENT_TYPE');

            // Multi-Part Form Data
            if (strpos($contentType, 'multipart/form-data') !== false) {
                $request = $_REQUEST;
            } else {
                $body = file_get_contents('php://input');

                // JSON
                if (strpos($contentType, 'application/json') !== false) {
                    $request = json_decode($body, true);

                // Plain-Text
                } elseif (strpos($contentType, 'text/plain') !== false) {
                    $request = $body;

                // Default to Query String
                } else {
                    parse_str($body, $request);
                }
            }
        }

        $session = (isset($_SESSION)) ? $_SESSION : [];

        return new self($_GET, $request, $_COOKIE, $_FILES, $server, $session);
    }

    /**
     * Creates a request from a given URI.
     *
     * @param string       $uri
     * @param string       $method
     * @param array|string $parameters
     * @param array        $cookies
     * @param array        $files
     * @param array        $server
     * @param array        $session
     *
     * @return Request
     */
    public static function create($uri, $method = 'GET', $parameters = [], array $cookies = [], array $files = [], array $server = [], array $session = [])
    {
        // ensure the basic server parameters are filled in
        $server = array_replace([
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Infuse/1.X',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '127.0.0.1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_TIME' => time(),
            'PATH_INFO' => '',
        ], $server);

        // parse the URI and overwrite extracted properties
        $components = parse_url($uri);
        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] = $server['HTTP_HOST'].':'.$components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        switch (strtoupper($method)) {
            case 'POST':
            case 'PUT':
            case 'DELETE':
                if (!isset($server['CONTENT_TYPE'])) {
                    $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
                }
                // no break
            case 'PATCH':
                $request = $parameters;
                $query = [];
                break;
            default:
                $request = [];
                $query = $parameters;
                break;
        }

        // build the request URI and query string
        $queryString = '';
        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);
            if ($query) {
                $query = array_replace($qs, $query);
                $queryString = http_build_query($query, '', '&');
            } else {
                $query = $qs;
                $queryString = $components['query'];
            }
        } elseif ($query) {
            $queryString = http_build_query($query, '', '&');
        }

        $server['REQUEST_URI'] = $components['path'].('' !== $queryString ? '?'.$queryString : '');
        $server['QUERY_STRING'] = $queryString;

        return new self($query, $request, $cookies, $files, $server);
    }

    /**
     * Constructs a request.
     *
     * @param array        $query
     * @param array|string $request
     * @param array        $cookies
     * @param array        $files
     * @param array        $server
     * @param array        $session
     */
    public function __construct(array $query = [], $request = [], array $cookies = [], array $files = [], array $server = [], array $session = [])
    {
        $this->query = $query;
        $this->request = $request;
        $this->cookies = $cookies;
        $this->files = $files;
        $this->server = $server;
        $this->session = $session;
        $this->params = [];

        // remove slash in front of requested url
        $this->server['REQUEST_URI'] = substr_replace(array_value($this->server, 'REQUEST_URI'), '', 0, 1);

        // figure out the base path and REQUEST_URI
        $this->basePath = '/';

        if (isset($this->server['DOCUMENT_URI'])) {
            $docParts = explode('/', substr_replace($this->server['DOCUMENT_URI'], '', 0, 1));
            $uriParts = explode('/', $this->server['REQUEST_URI']);

            $basePaths = [];

            // uriParts = uriParts - $docParts
            // basePaths = docParts - uriParts
            foreach ($uriParts as $key => $part) {
                if (isset($docParts[$key]) && $docParts[$key] == $part) {
                    $basePaths[] = $uriParts[$key];
                    unset($uriParts[$key]);
                }

                if (strpos($part, '.php') !== false) {
                    break;
                }
            }

            // ignore a trailing "/"
            end($uriParts);
            $key = key($uriParts);
            if (empty($uriParts[$key])) {
                unset($uriParts[$key]);
            }

            // strip base path from REQUEST_URI
            $this->server['REQUEST_URI'] = implode('/', $uriParts);

            $this->basePath .= implode('/', $basePaths);
        }

        // parse url
        $this->setPath($this->server['REQUEST_URI']);

        // parse headers
        $this->headers = $this->parseHeaders($this->server);

        // accept header
        $this->accept = $this->parseAcceptHeader(array_value($this->headers, 'ACCEPT'));

        // accept charsets header
        $this->charsets = $this->parseAcceptHeader(array_value($this->headers, 'ACCEPT_CHARSET'));

        // accept language header
        $this->languages = $this->parseAcceptHeader(array_value($this->headers, 'ACCEPT_LANGUAGE'));

        // PUT, PATCH, and DELETE requests can come through POST
        if ($this->method() == 'POST' &&
            in_array($this->request('method'), ['PUT', 'PATCH', 'DELETE'])) {
            $this->server['REQUEST_METHOD'] = $this->request('method');
        }
    }

    /**
     * Sets the path for the request and parses it.
     *
     * @param string $path i.e. /users/10/comments
     */
    public function setPath($path)
    {
        // get the base path
        $this->path = current(explode('?', $path));
        if (substr($this->path, 0, 1) != '/') {
            $this->path = '/'.$this->path;
        }

        // break the URL into paths
        $this->paths = explode('/', $this->path);
        if ($this->paths[0] == '') {
            array_splice($this->paths, 0, 1);
        }
    }

    /**
     * Gets the ip address associated with the request.
     *
     * @return string ip address
     */
    public function ip()
    {
        return array_value($this->server, 'REMOTE_ADDR');
    }

    /**
     * Gets the protocol associated with the request.
     *
     * @return string https or http
     */
    public function protocol()
    {
        $https = array_value($this->server, 'HTTPS');
        if ($https && $https !== 'off') {
            return 'https';
        }

        return ($this->port() == 443) ? 'https' : 'http';
    }

    /**
     * Checks if the request uses a secure protocol.
     *
     * @return bool
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
        return array_value($this->server, 'SERVER_PORT');
    }

    /**
     * Gets values from the request headers.
     *
     * @param string $index optional
     *
     * @return mixed
     */
    public function headers($index = false)
    {
        return ($index) ? array_value($this->headers, strtoupper($index)) : $this->headers;
    }

    /**
     * Gets the username from the auth headers associated with the request.
     *
     * @return string username
     */
    public function user()
    {
        return array_value($this->headers, 'PHP_AUTH_USER');
    }

    /**
     * Gets the password from the auth headers associated with the request.
     *
     * @return string password
     */
    public function password()
    {
        return array_value($this->headers, 'PHP_AUTH_PW');
    }

    /**
     * Gets the host name (or ip) for the request.
     *
     * @return string host
     */
    public function host()
    {
        $host = array_value($this->headers, 'HOST');

        if (!$host) {
            $host = array_value($this->server, 'SERVER_NAME');
        }

        if (!$host) {
            $host = array_value($this->server, 'SERVER_ADDR', '');
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
        if (!in_array($port, [80, 443])) {
            $port = ':'.$port;
        } else {
            $port = '';
        }

        $path = str_replace('//', '/', $this->basePath().$this->path());

        return $this->protocol().'://'.$this->host().$port.$path;
    }

    /**
     * Returns each component of the requested path.
     *
     * @return array paths
     */
    public function paths($index = false)
    {
        return (is_numeric($index)) ? array_value($this->paths, $index) : $this->paths;
    }

    /**
     * Gets the path associated with the request minus the bse path.
     *
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Gets the base path associated with the request. i.e. /comments/10
     * Useful if the entry point to the request was not located in the root directory
     * i.e. /blog/index.php returns /blog or /index.php returns an empty string.
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
        return array_value($this->server, 'REQUEST_METHOD');
    }

    /**
     * Gets the content type the request was sent with.
     *
     * @param string content type
     */
    public function contentType()
    {
        return array_value($this->server, 'CONTENT_TYPE');
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
     * Gets the user agent string from the request.
     *
     * @return string user agent string
     */
    public function agent()
    {
        return array_value($this->server, 'HTTP_USER_AGENT');
    }

    /**
     * Checks if the request accepts HTML.
     *
     * @return bool
     */
    public function isHtml()
    {
        foreach ($this->accept as $type) {
            if ($type['main_type'] == 'text' && $type['sub_type'] == 'html') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the request accepts JSON.
     *
     * @return bool
     */
    public function isJson()
    {
        foreach ($this->accept as $type) {
            if ($type['main_type'] == 'application' && $type['sub_type'] == 'json') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the request accepts XML.
     *
     * @return bool
     */
    public function isXml()
    {
        foreach ($this->accept as $type) {
            if ($type['main_type'] == 'application' && $type['sub_type'] == 'xml') {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the request was sent using AJAX.
     *
     * @return bool
     */
    public function isXhr()
    {
        return array_value($this->headers, 'X-Requested-With') == 'XMLHttpRequest';
    }

    /**
     * Checks if the request is for the API. This is used to decide if a request is stateless or not.
     *
     * @return bool
     */
    public function isApi()
    {
        return isset($this->headers['AUTHORIZATION']) ||
            strlen($this->request('access_token')) > 0 ||
            strlen($this->query('access_token')) > 0;
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
    public function params($index = false)
    {
        return ($index) ? array_value($this->params, $index) : $this->params;
    }

    /**
     * Adds parameters to the request.
     *
     * @param array $params parameters to add
     */
    public function setParams($params = [])
    {
        $this->params = array_replace($this->params, (array) $params);
    }

    /**
     * Gets values from the query portion of the request. (i.e. GET parameters).
     *
     * @param string $index optional
     *
     * @return mixed
     */
    public function query($index = false)
    {
        return ($index) ? array_value($this->query, $index) : $this->query;
    }

    /**
     * Gets values from the body of the request. (i.e. POST, PUT parameters).
     *
     * @param string $index optional
     *
     * @return mixed
     */
    public function request($index = false)
    {
        if (!is_array($this->request) && $index) {
            return;
        }

        return ($index) ? array_value($this->request, $index) : $this->request;
    }

    /**
     * Gets the cookies associated with the request.
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
     * Gets the files associated with the request. (i.e. $_FILES).
     *
     * @param string $index optional
     *
     * @return mixed
     */
    public function files($index = false)
    {
        return ($index) ? array_value($this->files, $index) : $this->files;
    }

    /**
     * Gets the session variables associated with the request.
     *
     * @param string $index optional
     *
     * @return mixed
     */
    public function session($index = false)
    {
        return ($index) ? array_value($this->session, $index) : $this->session;
    }

    /**
     * Sets session variable(s).
     *
     * @param array|string $key   key-value or just a key
     * @param mixed        $value value to set if not supplying key-value map in first argument
     */
    public function setSession($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
                $this->session[$k] = $v;
            }
        } else {
            $_SESSION[$key] = $value;
            $this->session[$key] = $value;
        }
    }

    /**
     * Destroys the session for the request.
     */
    public function destroySession()
    {
        $_SESSION = [];
        $this->session = [];
    }

    ////////////////////////////////////
    // PRIVATE METHODS
    ////////////////////////////////////

    private function parseHeaders($parameters)
    {
        $headers = [];
        foreach ($parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            }
            // CONTENT_* are not prefixed with HTTP_
            elseif (in_array($key, ['CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'])) {
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
            $userPassStr = $headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW'];
            $headers['AUTHORIZATION'] = 'Basic '.base64_encode($userPassStr);
        }

        return $headers;
    }

    // Credit to Jurgens du Toit: http://jrgns.net/parse_http_accept_header/
    private function parseAcceptHeader($acceptStr = '')
    {
        $return = [];

        $types = explode(',', $acceptStr);
        $types = array_map('trim', $types);
        foreach ($types as $one_type) {
            $one_type = explode(';', $one_type);
            $type = array_shift($one_type);
            if ($type) {
                list($precedence, $tokens) = $this->parseAcceptHeaderOptions($one_type);
                $typeArr = explode('/', $type);
                if (!isset($typeArr[1])) {
                    $typeArr[1] = '';
                }
                list($main_type, $sub_type) = array_map('trim', $typeArr);
                $return[] = [
                    'main_type' => $main_type,
                    'sub_type' => $sub_type,
                    'precedence' => (float) $precedence,
                    'tokens' => $tokens, ];
            }
        }

        usort($return, [$this, 'compareMediaRanges']);

        return $return;
    }

    private function parseAcceptHeaderOptions($type_options)
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
                $tokens[$option[0]] = array_value($option, 1);
            }
        }
        $tokens = count($tokens) ? $tokens : false;

        return [$precedence, $tokens];
    }

    private function compareMediaRanges($one, $two)
    {
        if ($one['main_type'] != '*' && $two['main_type'] != '*') {
            if ($one['sub_type'] != '*' && $two['sub_type'] != '*') {
                if ($one['precedence'] == $two['precedence']) {
                    if (count($one['tokens']) == count($two['tokens'])) {
                        return 0;
                    } elseif (count($one['tokens']) < count($two['tokens'])) {
                        return 1;
                    } else {
                        return -1;
                    }
                } elseif ($one['precedence'] < $two['precedence']) {
                    return 1;
                } else {
                    return -1;
                }
            } elseif ($one['sub_type'] == '*') {
                return 1;
            } else {
                return -1;
            }
        } elseif ($one['main_type'] == '*') {
            return 1;
        } else {
            return -1;
        }
    }
}
