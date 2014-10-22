<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Database;

class InsertQuery extends Query
{
    protected $table;
    protected $values = [];

    /**
	 * Sets the table for the query
	 *
	 * @param string $table table name
	 *
	 * @return self
	 */
    public function into($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Sets the values for the query
     *
     * @param array $values
     *
     * @return self
     */
    public function values(array $values)
    {
        $this->values = $values;

        return $this;
    }

    /**
     * Gets the table for the query
     *
     * @return string
     */
    public function getInto()
    {
        return $this->table;
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
     * Generates the raw SQL string for the query
     *
     * @return string
     */
    public function sql()
    {
        $sql = ['INSERT INTO ' . $this->table]; // into,

        // values TODO
        return implode(' ', $sql);
    }
}
