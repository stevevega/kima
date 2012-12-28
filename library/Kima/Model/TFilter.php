<?php
/**
 * Kima Model Filter
 * @author Steve Vega
 */
namespace Kima\Model;

use \Kima\Error;

/**
 * Kima Model Filter
 * Defines the operators used for the model query filters
 */
trait TFilter
{

    /**
     * Query operators
     * @var array
     */
    private $operators = [
        '$ne' => '!=',
        '$lt' => '<',
        '$lte' => '<=',
        '$gt' => '>',
        '$gte' => '>=',
        '$in' => 'IN (%s)',
        '$nin' => 'NOT IN (%s)',
        '$exists' => 'IS %s NULL'];

    /**
     * Query logical operators
     * @var array
     */
    private $logical_operators = [
        '$or' => 'OR',
        '$and' => 'AND'];

    /**
     * Parse the operators of the query filters
     * @param array $filters
     * @param array $binds
     * @param string $logical_operator
     * @param int $i
     */
    private function parse_operators(array $filters, array &$binds, $logical_operator = 'AND', &$i = 1)
    {
        $filter = [];

        foreach ($filters as $key => $value)
        {
            switch (true)
            {
                case array_key_exists((string)$key, $this->logical_operators) :
                    if (!is_array($value) || count($value) < 1)
                    {
                        Error::set(sprintf(self::ERROR_QUERY,
                            '$or operator expects an array of two or more'));
                    }

                    $logical_filter = [];
                    foreach ($value as $v)
                    {
                        $logical_filter[] = '(' . $this->parse_operators(
                        $v, $binds, 'AND', $i) . ')';
                        $i++;
                    }

                    $logical_operator = $this->logical_operators[$key];
                    $filter[] = '(' .
                        implode(" $logical_operator ",
                        $logical_filter) .
                        ')';
                    break;

                case is_array($value) :
                    // parse operator foreach value inside the array
                    foreach ($value as $k => $v)
                    {
                        $filter[] = $this->parse_custom_operator($k, $key, $v, $i, $binds);
                        $i++;
                    }
                    break;

                default:
                    $filter[] = $this->parse_key_value($key, $value, $i, $binds);
                    break;
            }
            $i++;
        }

        return implode(" $logical_operator ", $filter);
    }

    /**
     * Parse a normal key value filter
     * @param string $key
     * @param mixed $value
     * @param int $bind_key
     * @param array $binds
     */
    private function parse_key_value($key, $value, $bind_key, array &$binds)
    {
        // set the bind key
        $bind_key = ':' . $bind_key;

        // set the filter with prepare statements
        $filter = $key . ' = ' . $bind_key;

        // set the prepare statement
        $binds[$bind_key] = $value;

        return $filter;
    }

    /**
     * Parse a custom query operator
     * @param string $operator
     * @param string $key
     * @param mixed $value
     * @param int $bind_key
     * @param array $binds
     */
    private function parse_custom_operator($operator, $key, $value, &$bind_key, array &$binds)
    {
        switch ($operator)
        {
            case '$ne':  // not equal
            case '$lt': // lower than
            case '$lte': // lower than or equals
            case '$gt': // greater than
            case '$gte': // greater than or equals
                $filter = $this->parse_simple_operator(
                    $this->operators[$operator], $key, $value, $bind_key, $binds);
                break;

            case '$in': // in (1,2,3)
            case '$nin': // not in (1,2,3)
                $filter = $this->parse_in_operator(
                    $this->operators[$operator], $key, $value, $bind_key, $binds);
                break;

            case '$exists':
                $exists = false === $value ? '' : 'NOT';
                $filter = $key . ' ' . sprintf($this->operators[$operator], $exists);
                break;

            default:
                Error::set(sprintf(self::ERROR_QUERY, 'Invalid operator "' . $operator . '"'));
                break;
        }

        return $filter;
    }

    /**
     * Parse a simple query operators
     * @param string $operator
     * @param string $key
     * @param mixed $value
     * @param int $bind_key
     * @param array $binds
     */
    private function parse_simple_operator($operator, $key, $value, $bind_key, array &$binds)
    {
        $bind_key = ':' . $bind_key;
        $filter = "$key $operator $bind_key";
        $binds[$bind_key] = $value;

        return $filter;
    }

    /**
     * Parse a "in" and "not in" operators
     * @param string $operator
     * @param string $key
     * @param mixed $value
     * @param int $bind_key
     * @param array $binds
     */
    private function parse_in_operator($operator, $key, $value, &$bind_key, array &$binds)
    {
        $values = [];

        if (!is_array($value))
        {
            Error::set(sprintf(self::ERROR_QUERY, '"$in" operator expects an array as value'));
        }

        // parse and bind every value of the "in" filter
        foreach ($value as $v)
        {
            $values[] = ':' . $bind_key;
            $binds[':' . $bind_key] = $v;
            $bind_key++;
        }

        return $key . ' ' . sprintf($operator, implode(',', $values));
    }

}