<?php

/**
 * @package infuse\libs
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @copyright 2014 Jared King
 * @license MIT
 */

namespace infuse\Database\Statements;

class OrderStatement extends Statement
{
    protected $groupBy;
    protected $fields = [];

    /**
     * @param boolean $groupBy when true, statement becomes a group by statement
     */
    public function __construct($groupBy = false)
    {
        $this->groupBy = $groupBy;
    }

    /**
     * Tells whether this statement is a GROUP BY statement
     *
     * @return boolean true: is group by, false: is order by
     */
    public function isGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * Adds fields to this statement
     * Support input styles:
     * - addFields('field ASC,field2')
     * - addFields('field', 'ASC')
     * - addFields(['field','field2'], 'DESC')
     * - addFields([['field','ASC'], ['field2','ASC']])
     *
     * @param string|array $fields
     * @param string       $direction direction for fields where direction is unspecified (optional)
     *
     * @return self
     */
    public function addFields($fields, $direction = false)
    {
        if (!is_array($fields)) {
            $fields = array_map(function ($f) {
                return trim($f);
            }, explode(',', $fields));
        }

        foreach ($fields as &$field) {
            if (!is_array($field)) {
                $field = explode(' ', trim($field));
            }

            if (count($field) == 1 && $direction) {
                $field[] = $direction;
            }
        }

        $this->fields = array_merge($this->fields, $fields);

        return $this;
    }

    /**
     * Gets the fields associated with this statement
     *
     * @return array fields i.e. [['field1','ASC'], ['field2']]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Generates the raw SQL string for the statement
     *
     * @return string
     */
    public function build()
    {
        $fields = $this->fields;
        foreach ($fields as &$field) {
            $field[0] = $this->escapeIdentifier($field[0]);
            $field = implode(' ', $field);
        }

        // remove empty values
        $fields = array_filter($fields);

        if (count($fields) == 0) {
            return '';
        }

        return ((!$this->groupBy) ? 'ORDER BY ' : 'GROUP BY ').
            implode(',', $fields);
    }
}
