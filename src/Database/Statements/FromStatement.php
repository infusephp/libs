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

class FromStatement extends Statement
{
    protected $tables = [];

    /**
	 * Adds one or more tables to this statement.
	 * Supported input styles:
	 * - addTable('Table,Table2')
	 * - addTable(['Table','Table2'])
	 *
	 * @param string|array $fields
	 *
	 * @return self
	 */
    public function addTable($tables)
    {
        if (!is_array($tables)) {
            $tables = array_map(function ($t) {
                return trim($t);
            }, explode(',', $tables));
        }

        $this->tables = array_merge($this->tables, $tables);

        return $this;
    }

    /**
	 * Gets the table(s) associated with this statement
	 *
	 * @return array
	 */
    public function getTables()
    {
        return $this->tables;
    }

    /**
	 * Generates the raw SQL string for the statement
	 *
	 * @return string
	 */
    public function build()
    {
        if (count($this->tables) == 0)
            return '';

        $tables = $this->tables;
        foreach ($tables as &$table)
            $table = $this->escapeIdentifier($table);

        return 'FROM ' . implode(',', $tables);
    }
}
