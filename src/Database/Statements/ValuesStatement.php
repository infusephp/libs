<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
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
        $keys = array_keys($this->values);
        foreach ($keys as &$key) {
            $key = $this->escapeIdentifier($key);
        }

        // remove empty values
        $keys = array_filter($keys);

        if (count($keys) == 0)
            return '';

        // generates (`col1`,`col2`,`col3`) VALUES (?,?,?)
        return '(' . implode(',', $keys) . ') VALUES (' .
            implode(',', array_fill(0, count($keys), '?')) . ')';
    }
}
