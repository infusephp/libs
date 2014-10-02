<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

use Pimple\Container;

class ErrorStack
{
    /////////////////////////////
    // Private Class Variables
    /////////////////////////////

    private $stack = [];
    private $context = '';
    private $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    /**
	 * Adds an error message to the stack
	 *
	 * @param array $error
	 * - error: error code
	 * - params: array of parameters to be passed to message
	 * - context: (optional) the context which the error message occured in
	 * - class: (optional) the class invoking the error
	 * - function: (optional) the function invoking the error
	 *
	 * @return boolean was error valid?
	 */
    public function push(array $error)
    {
        if( !isset( $error[ 'context' ] ) )
            $error[ 'context' ] = $this->context;

        if( !isset( $error[ 'params' ] ) )
            $error[ 'params' ] = [];

        if (Utility::array_value($error, 'error')) {
            $this->stack[] = $error;
        }

        return $this;
    }

    /**
	 * Sets the current default error context
	 *
	 * @param string $context
	 */
    public function setCurrentContext($context = '')
    {
        $this->context = $context;

        return $this;
    }

    /**
	 * Clears the current default error context
	 */
    public function clearCurrentContext()
    {
        $this->context = '';

        return $this;
    }

    /**
	 * Gets all of the errors on the stack and also attempts
	 * translation using the Locale class
	 *
	 * @param string $context optional context
	 * @param string $locale optional locale
	 *
	 * @return array errors
	 */
    public function errors($context = false, $locale = false)
    {
        $errors = [];

        foreach ($this->stack as $error) {
            if (!$context || $error[ 'context' ] == $context) {
                // attempt to translate error into a message
                if( !isset( $error[ 'message' ] ) )
                    $error[ 'message' ] = $this->app[ 'locale' ]->t( $error[ 'error' ], $error[ 'params' ], $locale );

                $errors[] = $error;
            }
        }

        return $errors;
    }

    /**
	 * Gets the messages of errors on the stack
	 *
	 * @param string $context optional context
	 * @param string $locale optional locale
	 *
	 * @return array errors
	 */
    public function messages($context = null, $locale = false)
    {
        $errors = $this->errors( $context );

        $messages = [];

        foreach( $errors as $error )
            $messages[] = $error[ 'message' ];

        return $messages;
    }

    /**
	 * Gets an error for a specific parameter on the stack
	 *
	 * @param string $value value we are searching for
	 * @param string $param parameter name
	 *
	 * @return array|false
	 */
    public function find($value, $param = 'field')
    {
        foreach ($this->stack as $error) {
            if( Utility::array_value( $error[ 'params' ], $param ) === $value )

                return $error;
        }

        return false;
    }

    /**
	 * Checks if an error exists with a specific parameter on the stack
	 *
	 * @param string $value value we are searching for
	 * @param string $param parameter name
	 *
	 * @return boolean
	 */
    public function has($value, $param = 'field')
    {
        return $this->find( $value, $param ) !== false;
    }

    /**
	 * Clears the error stack
	 */
    public function clear()
    {
        $this->stack = [];

        return $this;
    }
}
