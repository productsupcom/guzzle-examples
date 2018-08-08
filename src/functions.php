<?php

namespace Productsup;

use \RecursiveIteratorIterator;
use \RecursiveArrayIterator;
use Symfony\Component\Stopwatch\StopwatchEvent;

/**
 * @internal
 */
class MyRecursiveIteratorIterator extends RecursiveIteratorIterator
{
    public function key()
    {
        return implode('_', $this->getKeyStack());
    }

    public function getKeyStack()
    {
        $result = array();
        for ($depth = 0, $lim = $this->getDepth(); $depth < $lim; $depth += 1) {
            $result[] = $this->getSubIterator($depth)->key();
        }
        $result[] = parent::key();
        return $result;
    }
}

function flatten_product($product)
{
    $it = new MyRecursiveIteratorIterator(new RecursiveArrayIterator($product));

    return iterator_to_array($it);
}

function last_stopwatch_period(StopwatchEvent $event)
{
    $periods = $event->getPeriods();
    $amount = count($periods);

    return $amount ? $periods[$amount - 1] : null;
}

function array_flatten(array $array, $prefix = '')
{
    $result = [];
    foreach ($array as $k => $v) {
        $index = $prefix.$k;
        if (is_array($v)) {
            $result = array_merge($result, array_flatten($v, $index.'__'));
        } else {
            $result[$index] = $v;
        }
    }

    return $result;
}

function array_except(array $array, ...$keys)
{
    return array_diff_key($array, array_flip($keys));
}

function array_combine(array $a1, array $a2, $a2Prefix)
{
    $result = $a1;
    foreach ($a2 as $k => $v) {
        if (array_key_exists($k, $result)) {
            $k = $a2Prefix.$k;
        }

        $result[$k] = $v;
    }

    return $result;
}

/**
 * @param array $initial
 * @param int $N
 * @param string $innerKey
 *
 * @return array 1st element — array with the first N records from the initial array, 2nd element — a string of the all
 * rest elements.
 */
function array_array_crop(array $initial, $N = 5, $innerKey = 'name')
{
    $first = array_slice($initial, 0, $N, true);

    $rest = array_slice($initial, $N);

    $rest = array_column($rest, $innerKey);
    $rest = implode(', ', $rest);

    return [$first, $rest];
}

function array_array_get($array, ...$keys)
{
    if (empty($array)) {
        $array = [];
    }

    $result = [];
    foreach ($array as $key => $value) {
        $result[$key] = array_get($value, ...$keys);
    }

    return $result;
}

function array_get(array $array, ...$keys)
{
    $result = [];
    foreach ($array as $key => $value) {
        if (in_array($key, $keys)) {
            $result[$key] = $value;
        }
    }

    return $result;
}

