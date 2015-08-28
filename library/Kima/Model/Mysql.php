<?php
/**
 * Kima Mysql Model
 * @author Steve Vega
 */
namespace Kima\Model;

use Kima\Database;
use Kima\Error;

/**
 * Mysql
 * Mysql Model Class
 */
class Mysql implements IModel
{

    /**
     * Traits
     */
    use TFilter;

    /**
     * Error messages
     */
     const ERROR_EMPTY_FIELDS = 'Fields for save data query not provided';
     const ERROR_INVALID_JOIN = 'Join expects at least "table" and "on" parameters';
     const ERROR_JOIN_NOT_ARRAY = 'Join array should contain an array with joins';
     const ERROR_JOIN_ON_NOT_ARRAY = 'Join "on" clause should be an arrray';
     const ERROR_QUERY = 'Error parsing MySQL query: %s';
     const ERROR_LIMIT = 'Error with mysql max integer value';

     /**
      * Max integer value
      */
     const MAX_INTEGER_VALUE = 9223372036854775807;

    /**
     * Gets the table name format for the database
     * @param  string $table
     * @param  string $database
     * @param  string $prefix
     * @return string
     */
    public function get_table($table, $database = '', $prefix = '')
    {
        $table = empty($prefix) ? $table : $prefix . $table;

        return empty($database) ? $table : $database . '.' . $table;
    }

    /**
     * Gets the join syntax for the query
     * @param  array  $options ['table', 'on', 'type']
     * @return string
     */
    public function get_join(array $options)
    {
        // validate join
        if (empty($options['table']) || empty($options['on'])) {
            Error::set(self::ERROR_INVALID_JOIN);
        }
        if (!is_array($options['on'])) {
            Error::set(self::ERROR_JOIN_ON_NOT_ARRAY);
        }

        // set the base join query
        $join_table = $options['table'];
        $join_type = !empty($options['type']) ? $options['type'] : 'LEFT';
        $join_query = " $join_type JOIN $join_table ON ";

        $binds = null;
        $join_query .= $this->parse_operators($options['on'], $binds);

        return $join_query;
    }

    /**
     * Prepares the fields for a fetch query
     * @param  array  $fields
     * @return string
     */
    public function prepare_fetch_fields(array $fields)
    {
        # select * fields if none were added
        if (empty($fields)) {
            return '*';
        }

        # prepare every field
        $fields_query = [];
        foreach ($fields as $field => $value) {
            $field_name = is_string($field) ? $field . ' AS ' . $value : $value;
            $fields_query[] = $field_name;
        }

        return implode(',', $fields_query);
    }

    /**
     * Prepares the fields for a save query
     * @todo fix $this->{value}
     * @param  array  $fields
     * @param  array  $binds
     * @return string
     */
    public function prepare_save_fields(array $fields, array &$binds)
    {
        # save queries should always provide at least one field
        if (empty($fields)) {
            Error::set(self::ERROR_EMPTY_FIELDS);
        }
        $save_fields = [];

        // check if the array is multidimensional array of arrays
        if ($this->are_save_fields_multiple($fields)) {
            foreach ($fields as $key => $field) {
                $fields_data = $this->set_save_fields($field, $key, $binds);
                $save_fields['values'][] = $fields_data['values'];
            }
        } else {
            $fields_data = $this->set_save_fields($fields, 0, $binds);
            $save_fields['values'][] = $fields_data['values'];
        }

        // add the keys before 1returning the save fields data
        $save_fields['fields'] = $fields_data['fields'];

        return $save_fields;
    }

    /**
     * Checks whether the save fields are for a multiple save request or not
     * @param  array   $fields
     * @return boolean
     */
    private function are_save_fields_multiple(array $fields)
    {
        foreach ($fields as $key => $value) {
            if (!is_int($key) || !is_array($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the values used for a put query
     * @param  array  $rows
     * @param  string $count
     * @return array
     */
    private function set_save_fields(array $rows, $count, &$binds)
    {
        $fields = $values = [];
        foreach ($rows as $key => $value) {
            // support for fields format: Model::FIELD_NAME
            // as an alias of Model::FIELD_NAME => null
            if (is_int($key)) {
                $key = $value;
                $result = 'NULL';
            } elseif (is_array($value) && isset($value['$raw'])) {
                // support for raw values like: Model::FIELD_NAME => ['$raw' => '= NOW()']
                $result = $value['$raw'];
            } else {
                // format the prepare statement key since it only allows alphanumeric and _
                $result = ':' . str_replace('.', '_', $key)  . '_' . $count;
                $binds[$result] = $value;
            }

            // add the value
            $fields[] = $key;
            $values[] = $result;
        }

        return [
            'fields' => $fields,
            'values' => $values
        ];
    }

    /**
     * Gets the values used for a put query
     * @param  array  $fields
     * @param  string $count
     * @return array
     */
    private function get_put_values(array $fields, $count, &$binds)
    {
        $values = [];
        foreach ($fields as $field => $value) {
            $key = $field;

            if (is_array($value) && isset($value['$raw'])) {
                $result = $value['$raw'];
            } else {
                // format the bind key since it only allows alphanumeric and _
                $result = ':' . str_replace('.', '_', $key)  . '_' . $count;
                $binds[$result] = $value;
            }

            // add the value
            $values[] = $result;
        }

        return $values;
    }

    /**
     * Prepares query joins
     * @param  array  $joins
     * @return string
     */
    public function prepare_joins(array $joins)
    {
        $join_query = [];
        foreach ($joins as $join) {
            if (!is_array($join)) {
                Error::set(self::ERROR_JOIN_NOT_ARRAY);
            }
            $join_query[] = $this->get_join($join);
        }

        return empty($join_query) ? '' : implode(' ', $join_query);
    }

    /**
     * Prepares query filters
     * @param  array  $filters
     * @param  array  $binds
     * @return string
     */
    public function prepare_filters(array $filters, array &$binds)
    {
        $filters = $this->parse_operators($filters, $binds);

        return empty($filters) ? '' : ' WHERE ' . $filters;
    }

    /**
     * Prepares procedure params filters
     * @param  array  $params
     * @param  array  $binds
     * @return string
     */
    public function prepare_params(array $params, array &$binds)
    {
        $params = $this->parse_procedure_params($params, $binds);

        return '(' . implode(',', $params) . ')';
    }

    /**
     * Prepares query having
     * @param  array  $having
     * @param  array  $binds
     * @return string
     */
    public function prepare_having(array $having, array &$binds)
    {
        $having = $this->parse_operators($having, $binds);

        return empty($having) ? '' : ' HAVING ' . $having;
    }

    /**
     * Prepares query grouping
     * @param  array  $group
     * @return string
     */
    public function prepare_group(array $group)
    {
        return empty($group) ? '' : ' GROUP BY ' . implode(', ', $group);
    }

    /**
     * Prepares query order values
     * @param  array  $orders
     * @return string
     */
    public function prepare_order(array $orders)
    {
        $order_query = [];

        foreach ($orders as $order => $asc) {
            $order_asc = $asc === 'DESC' ? 'DESC' : 'ASC';
            $order_query[] = $order . ' ' . $order_asc;
        }

        return empty($order) ? '' : ' ORDER BY ' . implode(', ', $order_query);
    }

    /**
     * Prepares query limit
     * @param  int    $limit
     * @param  int    $start
     * @return string
     */
    public function prepare_limit(array &$binds, $limit = 0, $start = 0)
    {
        if ($start >= self::MAX_INTEGER_VALUE or $limit >= self::MAX_INTEGER_VALUE) {
            Error::set(self::ERROR_LIMIT);
        }

        if (!empty($limit)) {
            $bind_key_start = ':start';
            $bind_key_limit = ':limit';

            $binds[$bind_key_start] = $start;
            $binds[$bind_key_limit] = $limit;
        }

        $result =  empty($limit) ? '' : ' LIMIT ' . $bind_key_start . ', ' . $bind_key_limit;

        return $result;
    }

    /**
     * Gets fetch query
     * @param  array   $params
     * @param  boolean $is_count_query
     * @return string
     */
    public function get_fetch_query(array &$params, $is_count_query = false)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);

        // reset filter binds if necessary
        if (array_key_exists('i', $params)) {
            $this->i = 1;
        }

        if ($is_count_query) {
            $params['fields'] = empty($params['group']) ? ['COUNT(*)' => 'count'] : [1];
        }

        $query_string =
            'SELECT ' .
                $this->prepare_fetch_fields($params['fields']) .
                ' FROM ' . $table .
                $this->prepare_joins($params['joins']) .
                $this->prepare_filters($params['filters'], $params['binds']) .
                $this->prepare_group($params['group']) .
                $this->prepare_having($params['having'], $params['binds']) .
                $this->prepare_order($params['order']) .
                $this->prepare_limit($params['binds'], $params['limit'], $params['start']);

        if ($is_count_query && !empty($params['group'])) {
            $query_string = 'SELECT COUNT(*) AS count FROM (' . $query_string . ') AS count';
        }

        return $query_string;
    }

    /**
     * Gets procedure query
     * @param  array  $params
     * @return string
     */
    public function get_procedure_query(array &$params)
    {
        $query_string =
            'CALL ' .
                $params['procedure_name'] .
                $this->prepare_params($params['params'], $params['binds']);

        return $query_string;
    }

    /**
     * Gets update query
     * @param  array  $params
     * @return string
     */
    public function get_update_query(array &$params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);
        $fields = $this->prepare_save_fields($params['fields'], $params['binds']);

        $fields_query  = [];
        foreach ($fields['fields'] as $key => $field) {
            $fields_query[] = $field . '=' . $fields['values'][0][$key];
        }
        $fields_query = implode(', ', $fields_query);

        $query_string =
            'UPDATE ' . $table .
                $this->prepare_joins($params['joins']) .
                ' SET ' .
                $fields_query .
                $this->prepare_filters($params['filters'], $params['binds']) .
                $this->prepare_order($params['order']) .
                $this->prepare_limit($params['binds'], $params['limit']);

        return $query_string;
    }

    /**
     * Gets insert/update query
     * @param  array  $params
     * @return string
     */
    public function get_put_query(array &$params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);
        $fields = $this->prepare_save_fields($params['fields'], $params['binds']);

        // fields names
        $fields_query = '(' . implode(', ', $fields['fields']) . ')';

        // values
        $values_query = [];
        foreach ($fields['values'] as $values) {
            $values_query[] = '(' . implode(', ', $values) . ')';
        }
        $values_query = implode(', ', $values_query);

        // on duplicate query
        $on_duplicate_query = [];
        foreach ($fields['fields'] as $key => $field) {
            $on_duplicate_query[] = $field . '= VALUES(' . $field . ')';
        }
        $on_duplicate_query = implode(', ', $on_duplicate_query);

        // query string
        $query_string =
            'INSERT INTO ' . $table . $fields_query .
                ' VALUES ' .
                $values_query .
                ' ON DUPLICATE KEY UPDATE ' .
                $on_duplicate_query;

        return $query_string;
    }

    /**
     * Gets clone query
     * @param  array  $params
     * @return string
     */
    public function get_copy_query(array &$params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);
        $fields = $this->prepare_save_fields($params['fields'], $params['binds']);

        // extract only the names from the fields
        $fields_names = [];
        foreach ($fields['fields'] as $key => $field) {
            $fields_names[$key] = false !== strpos($field, '.')
                ? str_replace('.', '', strstr($field, '.'))
                : $field;
        }
        $fields_query = '(' . implode(', ', $fields_names) . ')';

        $values_query  = [];
        foreach ($fields['fields'] as $key => $field) {
            $values_query[] = strcmp($fields['values'][0][$key], 'NULL') !== 0
                ? $fields['values'][0][$key] . ' AS ' . $fields_names[$key]
                : $field;
        }
        $values_query = implode(', ', $values_query);

        $query_string =
            'INSERT INTO ' . $table . $fields_query .
                ' SELECT ' .
                $values_query .
                ' FROM ' . $table .
                $this->prepare_joins($params['joins']) .
                $this->prepare_filters($params['filters'], $params['binds']) .
                $this->prepare_group($params['group']) .
                $this->prepare_having($params['having'], $params['binds']) .
                $this->prepare_limit($params['binds'], $params['limit'], $params['start']);

        return $query_string;
    }

    /**
     * Gets delete query
     * @param  array  $params
     * @return string
     */
    public function get_delete_query(array &$params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);

        $query_string = 'DELETE' .
                    ' FROM ' . $table .
                    $this->prepare_joins($params['joins']) .
                    $this->prepare_filters($params['filters'], $params['binds']) .
                    $this->prepare_limit($params['binds'], $params['limit']);

        return $query_string;
    }

}
