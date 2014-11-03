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

class SelectQuery extends Query
{
    /**
	 * @var SelectStatement
	 */
    protected $select;

    /**
	 * @var FromStatement
	 */
    protected $from;

    /**
	 * @var WhereStatement
	 */
    protected $where;

    /**
	 * @var WhereStatement
	 */
    protected $having;

    /**
	 * @var OrderStatement
	 */
    protected $orderBy;

    /**
	 * @var OrderStatement
	 */
    protected $groupBy;

    /**
	 * @var string
	 */
    protected $limit;

    /**
	 * @var string
	 */
    protected $offset = '0';

    public function initialize()
    {
        $this->select = new Statements\SelectStatement();
        $this->from = new Statements\FromStatement();
        $this->where = new Statements\WhereStatement();
        $this->having = new Statements\WhereStatement(true);
        $this->orderBy = new Statements\OrderStatement();
        $this->groupBy = new Statements\OrderStatement(true);
    }

    /**
	 * Sets the fields to be selected for the query
	 *
	 * @param array|string $fields fields
	 *
	 * @return self
	 */
    public function select($fields)
    {
        $this->select->addFields($fields);

        return $this;
    }

    /**
	 * Sets the table for the query
	 *
	 * @param string $table table name
	 *
	 * @return self
	 */
    public function from($table)
    {
        $this->from->addTable($table);

        return $this;
    }

    /**
	 * inner join
	 */
    public function join($join, $on = null)
    {
        // TODO
        return $this;
    }

    public function leftJoin($join, $on = null)
    {
        // TODO
        return $this;
    }

    public function rightJoin($join, $on = null)
    {
        // TODO
        return $this;
    }

    public function crossJoin($join, $on = null)
    {
        // TODO
        return $this;
    }

    public function naturalJoin($join, $on = null)
    {
        // TODO
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
	 * Sets the limit for the query
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return self
	 */
    public function limit($limit, $offset = 0)
    {
        if (is_numeric($limit) && is_numeric($offset)) {
            $this->limit = (string) $limit;
            $this->offset = (string) $offset;
        }

        return $this;
    }

    /**
	 * Sets the group by fields for the query
	 *
	 * @param string|array $fields
	 * @param string $direction
	 *
	 * @return self
	 */
    public function groupBy($fields, $direction = false)
    {
        $this->groupBy->addFields($fields, $direction);

        return $this;
    }

    /**
     * Sets the having conditions for the query
     *
     * @param array|string $field
     * @param string       $value    condition value (optional)
     * @param string       $operator operator (optional)
     *
     * @return self
     */
    public function having($field, $condition = false, $operator = '=')
    {
        if (func_num_args() >= 2) {
            $this->having->addCondition($field, $condition, $operator);
        } else {
            $this->having->addCondition($field);
        }

        return $this;
    }

    /**
	 * Sets the order for the query
	 *
	 * @param string|array $fields
	 * @param string $direction
	 *
	 * @return self
	 */
    public function orderBy($fields, $direction = false)
    {
        $this->orderBy->addFields($fields, $direction);

        return $this;
    }

    /**
	 * Gets the select statement for the query
	 *
	 * @return SelectStatement
	 */
    public function getSelect()
    {
        return $this->select;
    }

    /**
	 * Gets the from statement for the query
	 *
	 * @return FromStatement
	 */
    public function getFrom()
    {
        return $this->from;
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
	 * Gets the limit and offset for the query
	 *
	 * @return array [limit, offset]
	 */
    public function getLimit()
    {
        return [$this->limit, $this->offset];
    }

    /**
	 * Gets the group by statement for the query
	 *
	 * @return GroupByStatement
	 */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
	 * Gets the having statement for the query
	 *
	 * @return HavingStatement
	 */
    public function getHaving()
    {
        return $this->having;
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
	 * Generates the raw SQL string for the query
	 *
	 * @return string
	 */
    public function build()
    {
        $sql = [
            $this->select->build(), // select
            $this->from->build() ]; // from

        $this->values = [];

        // where
        $where = $this->where->build();
        if (!empty($where)) {
            $sql[] = $where;
            $this->values = array_merge($this->values, $this->where->getValues());
        }

        // group by
        $groupBy = $this->groupBy->build();
        if (!empty($groupBy))
            $sql[] = $groupBy;

        // having
        $having = $this->having->build();
        if (!empty($having)) {
            $sql[] = $having;
            $this->values = array_merge($this->values, $this->having->getValues());
        }

        // order by
        $orderBy = $this->orderBy->build();
        if (!empty($orderBy))
            $sql[] = $orderBy;

        // limit
        if ($this->limit)
            $sql[] = 'LIMIT ' . $this->offset . ',' . $this->limit;

        return implode(' ', $sql);
    }
}
