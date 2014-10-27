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

class SqlQuery extends Query
{
    /**
     * @var string
     */
    protected $sql;

    /**
	 * Sets the SQL for the query
	 *
	 * @param string $sql
	 *
	 * @return self
	 */
    public function raw($sql)
    {
        $this->sql = $sql;

        return $this;
    }

    public function build()
    {
        return $this->sql;
    }
}
