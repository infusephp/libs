<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Database\Statements;

class SetStatement extends Statement
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
        $values = [];
        foreach ($this->values as $key => $value) {
            if ($id = $this->escapeIdentifier($key)) {
                $values[] = $id . '=?';
            }
        }

        if (count($values) == 0)
            return '';

        // generates SET `col1`=?,`col2`=?,`col3`=?
        return 'SET ' . implode(',', $values);
    }
}
