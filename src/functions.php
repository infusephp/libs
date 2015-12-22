<?php


/**
 * Looks up a key in an array. If the key follows dot-notation then a nested lookup will be performed.
 * i.e. users.sherlock.address.lat -> ['users']['sherlock']['address']['lat'].
 *
 * @param array  $a array to be searched
 * @param string $k key to search for
 *
 * @return mixed|null
 */
function array_value(array $a = [], $k = '')
{
    $a = (array) $a;
    if (array_key_exists($k, $a)) {
        return $a[$k];
    }

    $pieces = explode('.', $k);

    // use dot notation to search a nested array
    if (count($pieces) > 1) {
        foreach ($pieces as $piece) {
            if (!is_array($a) || !array_key_exists($piece, $a)) {
                // not found
                return;
            }

            $a = &$a[$piece];
        }

        return $a;
    }

    return;
}

/**
 * Sets an element in an array using dot notation (i.e. fruit.apples.qty sets ['fruit']['apples']['qty'].
 *
 * @param array  $a
 * @param string $key
 * @param mixed  $value
 */
function array_set(array &$a, $key, $value)
{
    $pieces = explode('.', $key);

    foreach ($pieces as $k => $piece) {
        $a = &$a[$piece];
        if (!is_array($a)) {
            $a = [];
        }
    }

    return $a = $value;
}

/**
 * Flattens a multi-dimensional array using dot notation
 * i.e. ['fruit' => ['apples' => ['qty' => 1]]] produces
 * [fruit.apples.qty => 1].
 *
 * @param array  $a      input array
 * @param string $prefix key prefix
 *
 * @return array output array
 */
function array_dot(array $a, $prefix = '')
{
    $result = [];

    if (!empty($prefix)) {
        $prefix = $prefix.'.';
    }

    foreach ($a as $k => $v) {
        if (is_array($v)) {
            $result = array_replace(
                $result,
                array_dot($v, $prefix.$k));
        } else {
            $result[$prefix.$k] = $v;
        }
    }

    return $result;
}
