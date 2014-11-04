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

class SelectStatement extends Statement
{
    protected $fields = [];

    /**
	 * Adds fields to this statement.
	 * Supported input styles:
	 * - addFields('field1,field2')
	 * - addFields(['field','field2'])
	 *
	 * @param string|array $fields
	 *
	 * @return self
	 */
    public function addFields($fields)
    {
        if (!is_array($fields)) {
            $fields = array_map(function ($f) {
                return trim($f);
            }, explode(',', $fields));
        }

        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    /**
	 * Gets the fields associated with this statement.
	 * If no fields are present then defaults to '*'
	 *
	 * @return array fields
	 */
    public function getFields()
    {
        return (count($this->fields) > 0) ? $this->fields : ['*'];
    }

    /**
	 * Generates the raw SQL string for the statement
	 *
	 * @return string
	 */
    public function build()
    {
        $fields = $this->getFields();
        foreach ($fields as &$field)
            $field = $this->escapeIdentifier($field);

        // remove empty values
        $fields = array_filter($fields);

        return 'SELECT ' . implode(',', $fields);
    }
}
