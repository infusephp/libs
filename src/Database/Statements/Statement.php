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

abstract class Statement
{
    /**
	 * @var array
	 */
    protected $values = [];

    /**
	 * Generates the raw SQL string for the statement
	 *
	 * @return string
	 */
    abstract public function build();

    /**
     * Gets the values associated with this statement
     *
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    protected function buildClause(array $clause, $base)
    {
        // handle pure SQL clauses
        if (count($clause) == 1)
            return $clause[0];

        $base .= '_';

        foreach ($clause as $k => &$value) {
            // handles nested conditions
            if (is_array($value)) {
                $this->buildClause($value, $base);
            // escape the identifier
            } elseif ($k == 0) {
                $value = $this->escapeIdentifier($value);
            // parameterize the value
            } elseif ($k == 2) {
                $value = $this->parameterize($base . $clause[0], $value);
            }
        }

        return implode('', $clause);
    }

    /**
	 * Escapes potentially reserved keywords in identifiers by wrapping them
	 * with the escape character as necessary
	 *
	 * @param string $word
	 * @param string $escapeChar
	 *
	 * @return string escaped identifier
	 */
    protected function escapeIdentifier($word, $escapeChar = '`')
    {
        $spaces = explode(' ', $word);
        foreach ($spaces as &$space) {
            if (strtolower($space) == 'as') {
                $space = 'AS';
            } else {
                $periods = explode('.', $space);
                foreach ($periods as &$period) {
                    if (preg_match('/^[A-Za-z0-9_]*$/', $period))
                        $period = $escapeChar . $period . $escapeChar;
                }

                $space = implode('.', $periods);
            }
        }

        return implode(' ', $spaces);
    }

    protected function parameterize($key, $value)
    {
        // numbered parameters
        $this->values[] = $value;

        return '?';

        // named parameters
        $key = ':' . preg_replace('/[^a-z0-9_]/', '', strtolower($key));
        $this->values[$key] = $value;

        return $key;
    }
}
