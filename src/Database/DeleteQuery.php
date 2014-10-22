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

class DeleteQuery extends Query
{
    protected $table;

    /**
     * @var FromStatement
     */
    protected $from;

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
    protected $liimt;

    public function initialize()
    {
        $this->from = new Statements\FromStatement();
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
    public function from($table)
    {
        $this->from->addFields($table);

        return $this;
    }

    public function where($where, $condition = false, $operator = false)
    {
        $this->where->addCondition($where, $condition, $operator);

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
        $this->limit = (string) $limit;
        $this->offset = (string) $offset;

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
     * Gets the limit for the query
     *
     * @return string limit
     */
    public function getLimit()
    {
        return $this->limit;
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
    public function sql()
    {
        $sql = [
            'DELETE',
            $this->from->build() ]; // from

        // where
        $where = $this->where->build();
        if (!empty($where))
            $sql[] = $where;

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
