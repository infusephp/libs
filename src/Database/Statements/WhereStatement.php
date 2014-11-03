<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.2.2
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Database\Statements;

class WhereStatement extends Statement
{
    /**
	 * @var boolean
	 */
    protected $having;

    /**
	 * @var array
	 */
    protected $conditions = [];

    /**
	 * @param boolean $having when true, statement becomes a having statement
	 */
    public function __construct($having = false)
    {
        $this->having = $having;
    }

    /**
	 * Tells whether this statement is a HAVING statement
	 *
	 * @return boolean true: is HAVING, false: is WHERE
	 */
    public function isHaving()
    {
        return $this->having;
    }

    /**
	 * Accepts the following forms:
	 * 1. addCondition('username', 'john')
	 * 2. addCondition('balance', 100, '>')
	 * 3. addCondition('name LIKE "%john%"')
	 * 4. addCondition([['balance', 100, '>'], ['user_id', 5]])
	 * 5. addCondition(['username' => 'john', 'user_id' => 5])
	 * 6. addCondition(['first_name LIKE "%john%"', 'last_name LIKE "%doe%"'])
	 *
	 * @param array|string $field
	 * @param string $value condition value (optional)
	 * @param string $operator operator (optional)
	 *
	 * @return self
	 */
    public function addCondition($field, $value = false, $operator = '=')
    {
        if (is_array($field) && !$value) {
            foreach ($field as $key => $value) {
                if (is_array($value)) { // handles #4
                    call_user_func_array([$this, 'addCondition'], $value);
                } elseif (!is_numeric($key)) { // handles #5
                    $this->addCondition($key, $value);
                } else { // handles #6
                    $this->addCondition($value);
                }
            }
        } else {
            // handles #3
            $condition = [$field];

            // handles #1 and #2
            if (func_num_args($value) >= 2) {
                $condition[] = $operator;
                $condition[] = $value;
            }

            $this->conditions[] = $condition;
        }

        return $this;
    }

    /**
	 * Gets the conditions for this statement
	 *
	 * @return array
	 */
    public function getConditions()
    {
        return $this->conditions;
    }

    /**
	 * Generates the raw SQL string for the statement
	 *
	 * @return string
	 */
    public function build()
    {
        if (count($this->conditions) == 0)
            return '';

        $sql = (!$this->having) ? 'WHERE ' : 'HAVING ';

        $clauses = [];
        foreach ($this->conditions as $clause) {
            $clauses[] = $this->buildClause($clause, $sql);
        }

        return $sql . implode(' AND ', $clauses);
    }
}
