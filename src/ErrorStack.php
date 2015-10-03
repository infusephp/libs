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

use Pimple\Container;

class ErrorStack implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var array
     */
    private $stack;

    /**
     * @var string
     */
    private $context;

    /**
     * @var \Pimple\Container
     */
    private $app;

    /**
     * @var int
     */
    private $pointer;

    public function __construct(Container $app)
    {
        $this->stack = [];
        $this->context = '';
        $this->app = $app;
        $this->pointer = 0;
    }

    /**
     * Adds an error message to the stack.
     *
     * @param array|string $error
     *                            - error: error code
     *                            - params: array of parameters to be passed to message
     *                            - context: (optional) the context which the error message occured in
     *                            - class: (optional) the class invoking the error
     *                            - function: (optional) the function invoking the error
     *
     * @return self
     */
    public function push($error)
    {
        $this->stack[] = $this->sanitize($error);

        return $this;
    }

    /**
     * Sets the current default error context.
     *
     * @param string $context
     *
     * @return self
     */
    public function setCurrentContext($context = '')
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Clears the current default error context.
     *
     * @return self
     */
    public function clearCurrentContext()
    {
        $this->context = '';

        return $this;
    }

    /**
     * Gets all of the errors on the stack and also attempts
     * translation using the Locale class.
     *
     * @param string $context optional context
     * @param string $locale  optional locale
     *
     * @return array errors
     */
    public function errors($context = false, $locale = false)
    {
        $errors = [];
        foreach ($this->stack as $error) {
            if (!$context || $error['context'] == $context) {
                $errors[] = $this->parse($error, $locale);
            }
        }

        return $errors;
    }

    /**
     * Gets the messages of errors on the stack.
     *
     * @param string $context optional context
     * @param string $locale  optional locale
     *
     * @return array errors
     */
    public function messages($context = null, $locale = false)
    {
        $messages = [];
        foreach ($this->errors($context, $locale) as $error) {
            $messages[] = $error['message'];
        }

        return $messages;
    }

    /**
     * Gets an error for a specific parameter on the stack.
     *
     * @param string $value value we are searching for
     * @param string $param parameter name
     *
     * @return array|false
     */
    public function find($value, $param = 'field')
    {
        foreach ($this->errors() as $error) {
            if (Utility::array_value($error['params'], $param) === $value) {
                return $error;
            }
        }

        return false;
    }

    /**
     * Checks if an error exists with a specific parameter on the stack.
     *
     * @param string $value value we are searching for
     * @param string $param parameter name
     *
     * @return bool
     */
    public function has($value, $param = 'field')
    {
        return $this->find($value, $param) !== false;
    }

    /**
     * Clears the error stack.
     *
     * @return self
     */
    public function clear()
    {
        $this->stack = [];

        return $this;
    }

    /**
     * Formats an incoming error message.
     *
     * @param array|string $error
     *
     * @return array
     */
    private function sanitize($error)
    {
        if (!is_array($error)) {
            $error = ['error' => $error];
        }

        if (!isset($error['context'])) {
            $error['context'] = $this->context;
        }

        if (!isset($error['params'])) {
            $error['params'] = [];
        }

        return $error;
    }

    /**
     * Parses an error message before displaying it.
     *
     * @param array        $error
     * @param string|false $locale
     *
     * @return array
     */
    private function parse(array $error, $locale = false)
    {
        // attempt to translate error into a message
        if (!isset($error['message'])) {
            $error['message'] = $this->app['locale']->t($error['error'], $error['params'], $locale);
        }

        return $error;
    }

    //////////////////////////
    // Iterator Interface
    //////////////////////////

    /**
     * Rewind the Iterator to the first element.
     */
    public function rewind()
    {
        $this->pointer = 0;
    }

    /**
     * Returns the current element.
     *
     * @return array|null
     */
    public function current()
    {
        if ($this->pointer >= $this->count()) {
            return;
        }

        $errors = $this->errors();

        return $errors[$this->pointer];
    }

    /**
     * Return the key of the current element.
     *
     * @return int
     */
    public function key()
    {
        return $this->pointer;
    }

    /**
     * Move forward to the next element.
     */
    public function next()
    {
        ++$this->pointer;
    }

    /**
     * Checks if current position is valid.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->pointer < $this->count();
    }

    //////////////////////////
    // Countable Interface
    //////////////////////////

    /**
     * Get total number of models matching query.
     *
     * @return int
     */
    public function count()
    {
        return count($this->stack);
    }

    /////////////////////////////
    // ArrayAccess Interface
    /////////////////////////////

    public function offsetExists($offset)
    {
        return isset($this->stack[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            throw new \OutOfBoundsException("$offset does not exist on this ErrorStack");
        }

        $this->pointer = $offset;

        return $this->current();
    }

    public function offsetSet($offset, $error)
    {
        if (!is_numeric($offset)) {
            throw new \Exception('Can only perform set on numeric indices');
        }

        $this->stack[$offset] = $this->sanitize($error);
    }

    public function offsetUnset($offset)
    {
        unset($this->stack[$offset]);
    }
}
