<?php
/**
 * Kima Mysql Model
 * @author Steve Vega
 */
namespace Kima\Model;

use \Kima\Database,
    \Kima\Error,
    \Kima\Model\IModel,
    \Kima\Model\TFilter;

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
     const ERROR_JOIN_NOT_ARRAY = 'Join array should contain an array with joins';
     const ERROR_QUERY = 'Error parsing MySQL query: %s';

    /**
     * Gets the table name format for the database
     * @param string $table
     * @param string $database
     * @param string $prefix
     * @return string
     */
    public function get_table($table, $database = '', $prefix = '')
    {
        $table = empty($prefix) ? $table : $prefix . $table;
        return empty($database) ? $table : $database . '.' . $table;
    }

    /**
     * Gets the join syntax for the query
     * @param array $options
     * @return string
     */
    public function get_join(array $options)
    {
        $join_table = $options['table'];

        $join_type = !empty($options['type']) ? $options['type'] : 'LEFT';
        $join_query = ' ' . $join_type . ' JOIN ' . $join_table;

        $key_parts = explode('.', $options['key']);
        $key = array_pop($key_parts);

        // use join key if necessary
        $join_query .= empty($options['joiner'])
            ? ' USING (' . $key . ')'
            : ' ON ( ' . $options['key'] . '=' . $options['joiner'] . ' )';

        return $join_query;
    }

    /**
     * Prepares the fields for a fetch query
     * @param array $fields
     * @param array $raw_fields
     * @return string
     */
    public function prepare_fetch_fields(array $fields, array $raw_fields)
    {
        # select * fields if none were added
        if (empty($fields) && empty($raw_fields))
        {
            return '*';
        }

        # prepare every field
        $fields_query = [];
        foreach ($fields as $field => $value)
        {
            $field_name = is_string($field) ? $field . ' AS ' . $value : $value;
            $fields_query[] = $field_name;
        }

        // add raw fields
        foreach ($raw_fields as $raw_field => $value)
        {
            $field_name = is_string($raw_field) ? $raw_field . ' AS ' . $value : $value;
            $fields_query[] = $field_name;
        }

        return implode(',', $fields_query);
    }

    /**
     * Prepares the fields for a save query
     * @todo fix $this->{value}
     * @param array $fields
     * @param array $binds
     * @return string
     */
    public function prepare_save_fields(array $fields, array &$binds)
    {
        # save queries should always provide at least one field
        if (empty($fields))
        {
            Error::set(self::ERROR_EMPTY_FIELDS);
        }

        # prepare every field
        $fields_query = [];
        foreach ($fields as $field => $value)
        {
            if (is_string($field))
            {
                $key = $field;
                $value = $value;
            }
            else
            {
                $key = $value;
                $value = $this->{$value};
            }

            // format the bind key since it only allows alphanumeric and _
            $bind_key = ':' . str_replace('.', '_', $key);
            $fields_query[] = $key . ' = ' . $bind_key;
            $binds[$bind_key] = $value;
        }

        return implode(', ', $fields_query);
    }

    /**
     * Prepares query joins
     * @param array $joins
     * @return string
     */
    public function prepare_joins(array $joins)
    {
        $join_query = [];
        foreach ($joins as $join)
        {
            if (!is_array($join))
            {
                Error::set(self::ERROR_JOIN_NOT_ARRAY);
            }
            $join_query[] = $this->get_join($join);
        }
        return empty($join_query) ? '' : implode(' ', $join_query);
    }

    /**
     * Prepares query filters
     * @param array $filters
     * @param array $binds
     * @return string
     */
    public function prepare_filters(array $filters, array &$binds)
    {
        $filters = $this->parse_operators($filters, $binds);

        return empty($filters) ? '' : ' WHERE ' . $filters;
    }

    /**
     * Prepares query grouping
     * @param array $group
     * @return string
     */
    public function prepare_group(array $group)
    {
        return empty($group) ? '' : ' GROUP BY ' . implode(', ', $group);
    }

    /**
     * Prepares query order values
     * @param array $orders
     * @return string
     */
    public function prepare_order(array $orders)
    {
        $order_query = [];

        foreach ($orders as $order => $asc)
        {
            $order_asc = $asc === 'DESC' ? 'DESC' : 'ASC';
            $order_query[] = $order . ' ' . $order_asc;
        }

        return empty($order) ? '' : ' ORDER BY ' . implode(', ', $order_query);
    }

    /**
     * Prepares query limit
     * @param $limit
     * @param $start
     * @return string
     */
    public function prepare_limit($limit = 0, $start = 0)
    {
        return empty($limit) ? '' : ' LIMIT ' . $start . ', ' . $limit;
    }

    /**
     * Gets fetch query
     * @param array $params
     * @return string
     */
    public function get_fetch_query(array &$params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);

        $query_string =
            'SELECT ' .
                $this->prepare_fetch_fields($params['fields'], $params['raw_fields']) .
                ' FROM ' . $table .
                $this->prepare_joins($params['joins']) .
                $this->prepare_filters($params['filters'], $params['binds']) .
                $this->prepare_group($params['group']) .
                $this->prepare_order($params['order']) .
                $this->prepare_limit($params['limit'], $params['start']);

        return $query_string;
    }

    /**
     * Gets update query
     * @param array $params
     * @return string
     */
    public function get_update_query(array $params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);

        $query_string =
            'UPDATE ' . $table .
                ' SET ' .
                $this->prepare_save_fields($params['fields']) .
                $this->prepare_filters($params['filters']) .
                $this->prepare_order($params['order']) .
                $this->prepare_limit($params['limit']);

        return $query_string;
    }

    /**
     * Gets insert/update query
     * @param array $params
     * @return string
     */
    public function get_put_query(array &$params)
    {
       $table = $this->get_table($params['table'], $params['database'], $params['prefix']);
       $fields = $this->prepare_save_fields($params['fields'], $params['binds']);

       $query_string =
            'INSERT INTO ' . $table .
                ' SET ' .
                $fields .
                ' ON DUPLICATE KEY UPDATE ' .
                $fields;

        return $query_string;
    }

    /**
     * Gets delete query
     * @param array $params
     * @return string
     */
    public function get_delete_query(array $params)
    {
        $table = $this->get_table($params['table'], $params['database'], $params['prefix']);

        $query_string = 'DELETE' .
                    ' FROM ' . $table .
                    $this->prepare_joins($params['joins']) .
                    $this->prepare_filters($params['filters']) .
                    $this->prepare_limit($params['limit']);

        return $query_string;
    }

}