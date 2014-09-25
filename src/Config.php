<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.0
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class Config
{
    private $values = [];

    public function __construct(array $values = [])
    {
        $this->values = array_replace($this->values, $values);
    }

    /**
	 * Gets a global configuration value, section, or all values
	 *
	 * @param string $property dot value property name
	 *
	 * @return mixed value
	 */
    public function get($property = false)
    {
        if (!$property)
            return $this->values;

        return Utility::array_value($this->values, $property);
    }

    /**
	 * Sets a configuration value (only persists for the duration of the script)
	 *
	 * @param string $property dot value property name
	 * @param string $value value to set
	 */
    public function set($property, $value)
    {
        Utility::array_set($this->values, $property, $value);
    }
}
