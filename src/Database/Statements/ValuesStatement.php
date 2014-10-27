<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Database\Statements;

class ValuesStatement extends Statement
{
    /**
     * @var array
     */
    protected $values = [];

    /**
     * Adds values to the statement
     *
     * @return self
     */
    public function addValues(array $values)
    {
        $this->values = array_replace($this->values, $values);

        return $this;
    }

    /**
     * Gets the values for the query
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
	 * Generates the raw SQL string for the statement
	 *
	 * @return string
	 */
    public function build()
    {
        if (count($this->values) == 0)
            return '';

        $keys = array_keys($this->values);
        foreach ($keys as &$key) {
            $key = $this->escapeIdentifier($key);
        }

        // generates (`col1`,`col2`,`col3`) VALUES (?,?,?)
        return '(' . implode(',', $keys) . ') VALUES (' .
            implode(',', array_fill(0, count($keys), '?')) . ')';
    }
}
