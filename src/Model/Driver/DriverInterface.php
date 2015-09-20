<?php

namespace infuse\Model\Driver;

interface DriverInterface
{
    /**
     * Marshals a value for a given property to storage, and
     * checks the validity of a value.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed serialized value
     */
    public function serializeValue(array $property, $value);

    /**
     * Marshals a value for a given property from storage.
     *
     * @param array $property
     * @param mixed $value
     *
     * @return mixed unserialized value
     */
    public function unserializeValue(array $property, $value);
}
