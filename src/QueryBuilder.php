<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse;

class QueryBuilder
{
    private $pdo;

    public function __construct(\PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public function getPDO()
    {
        return $this->pdo;
    }

    public function select($fields = '*')
    {
        $query = new Database\SelectQuery($this);

        return $query->select($fields);
    }

    public function insert($values)
    {
        $query = new Database\InsertQuery($this);

        return $query->values($values);
    }

    public function update($table)
    {
        $query = new Database\UpdateQuery($this);

        return $query->table($table);
    }

    public function delete($fields)
    {
        $query = new Database\DeleteQuery($this);

        return $query->fields($fields);
    }

    public function raw($sql)
    {
        $query = new Database\SqlQuery($this);

        return $query->sql($sql);
    }
}
