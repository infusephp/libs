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

class UpdateQuery extends Query
{
    /**
	 * @var FromStatement
	 */
    protected $table;

    /**
	 * @var SetStatement
	 */
    protected $set;

    /**
	 * @var WhereStatement
	 */
    protected $where;

    /**
     * @var OrderStatement
     */
    protected $orderBy;

    /**
     * @var string
     */
    protected $limit;

    public function initialize()
    {
        $this->table = new Statements\FromStatement(false);
        $this->set = new Statements\SetStatement();
        $this->where = new Statements\WhereStatement();
        $this->orderBy = new Statements\OrderStatement();
    }

    /**
     * Sets the table for the query
     *
     * @param string $table table name
     *
     * @return self
     */
    public function table($table)
    {
        $this->table->addTable($table);

        return $this;
    }

    /**
     * Sets the where conditions for the query
     *
     * @param array|string $field
     * @param string       $value    condition value (optional)
     * @param string       $operator operator (optional)
     *
     * @return self
     */
    public function where($field, $condition = false, $operator = '=')
    {
        if (func_num_args() >= 2) {
            $this->where->addCondition($field, $condition, $operator);
        } else {
            $this->where->addCondition($field);
        }

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
        $this->set->addValues($values);

        return $this;
    }

    /**
     * Sets the limit for the query
     *
     * @param int $limit
     *
     * @return self
     */
    public function limit($limit)
    {
        if (is_numeric($limit)) {
            $this->limit = (string) $limit;
        }

        return $this;
    }

    /**
     * Sets the order for the query
     *
     * @param string|array $fields
     * @param string       $direction
     *
     * @return self
     */
    public function orderBy($fields, $direction = false)
    {
        $this->orderBy->addFields($fields, $direction);

        return $this;
    }

    /**
	 * Gets the table name for the query
	 *
	 * @return FromStatement
	 */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Gets the values for the query
     *
     * @return array
     */
    public function getSet()
    {
        return $this->set;
    }

    /**
	 * Gets the where statement for the query
	 *
	 * @return WhereStatement
	 */
    public function getWhere()
    {
        return $this->where;
    }

    /**
	 * Gets the order by statement for the query
	 *
	 * @return OrderByStatement
	 */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * Gets the limit for the query
     *
     * @return string limit
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
	 * Generates the raw SQL string for the query
	 *
	 * @return string
	 */
    public function build()
    {
        $sql = [
            'UPDATE',
            $this->table->build() ]; // table

        $this->values = [];

        // set values
        $set = $this->set->build();
        if (!empty($set)) {
            $sql[] = $set;
            $this->values = array_merge($this->values, array_values($this->set->getValues()));
        }

        // where
        $where = $this->where->build();
        if (!empty($where)) {
            $sql[] = $where;
            $this->values = array_merge($this->values, $this->where->getValues());
        }

        // order by
        $orderBy = $this->orderBy->build();
        if (!empty($orderBy))
            $sql[] = $orderBy;

        // limit
        if ($this->limit)
            $sql[] = 'LIMIT ' . $this->limit;

        return implode(' ', $sql);
    }
}
