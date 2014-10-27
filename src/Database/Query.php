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

use infuse\QueryBuilder;

abstract class Query
{
    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var array
     */
    protected $values = [];

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
}
