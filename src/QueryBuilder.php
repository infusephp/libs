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
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @param PDO $pdo
     */
    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;
    }

    /**
     * Returns the PDO instance
     *
     * @return PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * Creates a SELECT query
     *
     * @param string|array $fields select fields
     *
     * @return SelectQuery
     */
    public function select($fields = '*')
    {
        $query = new Database\SelectQuery($this->pdo);

        return $query->select($fields);
    }

    /**
     * Creates an INSERT query
     *
     * @param array $values insert values
     *
     * @return InsertQuery
     */
    public function insert(array $values)
    {
        $query = new Database\InsertQuery($this->pdo);

        return $query->values($values);
    }

    /**
     * Creates an UPDATE query
     *
     * @param string|array $table update table
     *
     * @return UpdateQuery
     */
    public function update($table)
    {
        $query = new Database\UpdateQuery($this->pdo);

        return $query->table($table);
    }

    /**
     * Creates a DELETE query
     *
     * @param string $from delete table
     *
     * @return DeleteQuery
     */
    public function delete($from)
    {
        $query = new Database\DeleteQuery($this->pdo);

        return $query->from($from);
    }

    /**
     * Creates a raw SQL query
     *
     * @param string $sql SQL statement
     *
     * @return SqlQuery
     */
    public function raw($sql)
    {
        $query = new Database\SqlQuery($this->pdo);

        return $query->raw($sql);
    }
}
