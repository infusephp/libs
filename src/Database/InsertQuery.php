<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Database;

class InsertQuery extends Query
{
    /**
     * @var FromStatement
     */
    protected $table;

    /**
    * @var ValuesStatement
    */
    protected $insertValues;

    /**
     * @var array
     */
    protected $values = [];

    public function initialize()
    {
        $this->table = new Statements\FromStatement(false);
        $this->insertValues = new Statements\ValuesStatement();
    }

    /**
	 * Sets the table for the query
	 *
	 * @param string $table table name
	 *
	 * @return self
	 */
    public function into($table)
    {
        $this->table->addTable($table);

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
        $this->insertValues->addValues($values);

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
     * Gets the insert values for the query
     *
     * @return InsertStatement
     */
    public function getInsertValues()
    {
        return $this->insertValues;
    }

    /**
     * Generates the raw SQL string for the query
     *
     * @return string
     */
    public function build()
    {
        $sql = [
            'INSERT INTO',
            $this->table->build() ]; // into

        $this->values = [];

        // insert values
        $values = $this->insertValues->build();
        if (!empty($values)) {
            $sql[] = $values;
            $this->values = array_merge($this->values, array_values($this->insertValues->getValues()));
        }

        return implode(' ', $sql);
    }
}
