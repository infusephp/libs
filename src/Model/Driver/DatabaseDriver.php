<?php

namespace infuse\Model\Driver;

use infuse\Model;

class DatabaseDriver implements DriverInterface
{
    public function serializeValue(array $property, $value)
    {
        // encode JSON
        if ($property['type'] == Model::TYPE_JSON && !is_string($value)) {
            $value = json_encode($value);
        }

        return $value;
    }

    public function unserializeValue(array $property, $value)
    {
        // handle empty strings as null
        if ($property['null'] && $value == '') {
            return;
        }

        $type = $property['type'];

        // handle boolean values, they might be strings
        if ($type == Model::TYPE_BOOLEAN && is_string($value)) {
            return ($value == '1') ? true : false;
        }

        // cast numbers as....numbers
        if ($type == Model::TYPE_NUMBER) {
            return $value + 0;
        }

        // cast dates as numbers also
        if ($type == Model::TYPE_DATE) {
            if (!is_numeric($value)) {
                return strtotime($value);
            } else {
                return $value + 0;
            }
        }

        // decode JSON into an array
        if ($type == Model::TYPE_JSON && is_string($value)) {
            return (array) json_decode($value, true);
        }

        return $value;
    }
}
