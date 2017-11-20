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

class Config
{
    /**
     * @var array
     */
    private $values = [];

    /**
     * @param array $values
     */
    public function __construct(array $values = [])
    {
        $this->values = array_replace($this->values, $values);
    }

    /**
     * Gets the entire collection of configuration values.
     *
     * @return array
     */
    public function all()
    {
        return $this->values;
    }

    /**
     * Gets a configuration value.
     *
     * @param string $property dot value property name
     * @param mixed  $default  returns this if property was not found
     *
     * @return mixed value
     */
    public function get($property, $default = null)
    {
        $value = array_value($this->values, $property);

        if ($value === null) {
            return $default;
        }

        return $value;
    }

    /**
     * Sets a configuration value (only persists for the duration of the script).
     *
     * @param string $property dot value property name
     * @param string $value    value to set
     *
     * @return $this
     */
    public function set($property, $value)
    {
        array_set($this->values, $property, $value);

        return $this;
    }
}
