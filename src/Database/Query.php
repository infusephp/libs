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

use PDO;

abstract class Query
{
    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var array
     */
    protected $values = [];

    /**
     * @var int
     */
    protected $rowCount;

    /**
     * @param PDO $pdo
     */
    public function __construct($pdo = null)
    {
        $this->pdo = $pdo;

        if (method_exists($this, 'initialize'))
            $this->initialize();
    }

    /**
	 * Builds a SQL string for the query
	 *
	 * @return string SQL
	 */
    abstract public function build();

    /**
     * Gets the values associated with this query
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Executes a query
     *
     * @return PDOStatement|false result
     */
    public function execute()
    {
        $stmt = $this->pdo->prepare($this->build());

            if ($stmt->execute($this->getValues())) {
            $this->rowCount = $stmt->rowCount();

            return $stmt;
        } else

            return false;
    }

    /**
     * Executes a query and returns the first row
     *
     * @param int $style PDO fetch style
     *
     * @return mixed|false result
     */
    public function one($style = PDO::FETCH_ASSOC)
    {
        $stmt = $this->execute($this->build());

        if ($stmt)
            return $stmt->fetch($style);
        else
            return false;
    }

    /**
     * Executes a query and returns all of the rows
     *
     * @param int $style PDO fetch style
     *
     * @return mixed|false result
     */
    public function all($style = PDO::FETCH_ASSOC)
    {
        $stmt = $this->execute($this->build());

        if ($stmt)
            return $stmt->fetchAll($style);
        else
            return false;
    }

    /**
     * Executes a query and returns a column from all rows
     *
     * @param int $index zero-indexed column to fetch
     *
     * @return mixed|false result
     */
    public function column($index = 0)
    {
        $stmt = $this->execute($this->build());

        if ($stmt)
            return $stmt->fetchAll(PDO::FETCH_COLUMN, $index);
        else
            return false;
    }

    /**
     * Executes a query and returns a value from the first row
     *
     * @param int $index zero-indexed column to fetch
     *
     * @return mixed|false result
     */
    public function scalar($index = 0)
    {
        $stmt = $this->execute($this->build());

        if ($stmt)
            return $stmt->fetchColumn($index);
        else
            return false;
    }

    /**
     * Returns the number of rows affected by the last executed statement
     *
     * @return int
     */
    public function rowCount()
    {
        return $this->rowCount;
    }
}
