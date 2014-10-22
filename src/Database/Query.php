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

abstract class Query
{
    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var string
     */
    protected $sql;

    /**
     * @var array
     */
    protected $clauses = [];

    public function __construct(QueryBuilder $qb = null)
    {
        $this->qb = $qb;
        if (method_exists($this, 'initialize'))
            $this->initialize();
    }

    public function __get($name)
    {
        // invalidate the cached SQL string
        $this->sql = false;

        return $this->$name;
    }

    public function __set($name, $value)
    {
        // invalidate the cached SQL string
        $this->sql = false;

        $this->$name = $value;
    }

    /**
	 * Builds and caches a SQL string for the query
	 *
	 * @return string SQL
	 */
    public function build()
    {
        if (!$this->sql)
            $this->sql = $this->sql();

        return $this->sql;
    }

    /**
	 * Generates the raw SQL string for the query
	 *
	 * @return string
	 */
    protected function sql()
    {
        return '';
    }
}
