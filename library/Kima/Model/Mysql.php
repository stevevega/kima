<?php
/**
 * Namespace Kima
 */
namespace Kima\Model;

/**
 * Namespaces to use
 */
use \Kima\Database;

/**
 * Mysql
 *
 * Mysql Model Class
 * @package Kima
 */
class Mysql
{

	/**
     * Gets the table name format for the database
     * @param string $table
     * @param string $database
     * @param string $prefix
     * @return string
     */
    public function get_table($table, $database = '', $prefix = '')
    {
        $table = empty($prefix) ? $table : $prefix . '_' . $table;
		return empty($database) ? $table : $database . '.' . $table;
    }

    /**
     * Gets the join syntax for the query
     * @param string $table
     * @param string $key
     * @param string $join_key
     * @param string $database
     */
    public function get_join($table, $key, $join_key='', $database='')
    {
    	$join_table = empty($database) ? $table : $database . '.' . $table;
        $join_query = ' LEFT JOIN ' . $join_table;

        # use join key if necessary
        $join_query .= empty($join_key)
            ? ' USING ( ' . $key . ' )'
            : $join_query .= ' ON ( ' . $table . '.' . $key . '=' . $this->_table . '.' . $join_key . ' )';

        return $join_query;
    }

    /**
     * Gets the order syntax for the query
     * @access public
     * @param string $field
     * @param string $order
     * @return string
     */
    public function get_order($field, $order='ASC')
    {
        $order = $order === 'DESC' ? $order : 'ASC';
        return $field . ' ' . $order;
    }

    /**
     * Prepares the fields for a fetch query
     * @param array $fields
     * @return string
     */
    public function prepare_fetch_fields($fields)
    {
        # select * fields if none were added
        if (empty($fields)) {
            return '*';
        }

        # prepare every field
        $fields_query = array();
        foreach ($fields as $field => $value) {
            $fields_query[] = is_string($field) ? $field . ' AS ' . $value : $value;
        }

        return implode(',', $fields_query);
    }

    /**
     * Prepares the fields for a save query
     * @todo fix $this->{value}
     * @param array $fields
     * @return string
     */
    public function prepare_save_fields($fields)
    {
        # save queries should always provide at least one field
        if (empty($fields)) {
            Error::set(__METHOD__, 'fields for save data were not provided');
        }

        # prepare every field
        $fields_query = array();
        foreach ($fields as $field => $value) {
            $fields_query[] = is_string($field)
                ? $field . '=' . Database::get_instance()->escape($value)
                : $value . '=' . Database::get_instance()->escape($this->{$value});
        }

        return implode(',', $fields_query);
    }

    /**
     * Prepares query joins
     * @param array $joins
     * @return string
     */
    public function prepare_joins($joins)
    {
        return empty($joins) ? '' : implode(' ', $joins);
    }

    /**
     * Prepares query filters
     * @param array $filters
     * @return string
     */
    public function prepare_filters($filters)
    {
        return empty($filters) ? '' : ' WHERE ' . implode(' AND ', $filters);
    }

    /**
     * Prepares query grouping
     * @param array $group
     * @return string
     */
    public function prepare_group($group)
    {
        return empty($group) ? '' : ' GROUP BY ' . implode(', ', $group);
    }

    /**
     * Prepares query order values
     * @param array $order
     * @return string
     */
    public function prepare_order($order)
    {
        return empty($order) ? '' : ' ORDER BY ' . implode(', ', $order);
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
     * Fetch one field of data from the database
     * @access public
     */
    public function get_fetch_query($fields, $table, $joins, $filters, $group, $order, $limit, $start)
    {
        $query_string =
            'SELECT ' .
                $this->prepare_fetch_fields($fields) .
                ' FROM ' . $table .
                $this->prepare_joins($joins) .
                $this->prepare_filters($filters) .
                $this->prepare_group($group) .
                $this->prepare_order($order) .
                $this->prepare_limit($limit, $start);

        return $query_string;
    }

    /**
     * Updates data
     * @access public
     */
    private function get_update_query($fields, $table, $filters, $order, $limit)
    {
        $query_string =
            'UPDATE ' . $table .
                ' SET ' .
                $this->prepare_save_fields($fields) .
                $this->prepare_filters($filters) .
                $this->prepare_order($order) .
                $this->prepare_limit($limit);

        return $query_string;
    }

    /**
     * Inserts/updates data
     * @access public
     */
    public function get_put_query($fields, $table)
    {
       $fields = $this->prepare_save_fields($fields);

       $query_string =
            'INSERT INTO ' . $table .
                ' SET ' .
                $fields .
                ' ON DUPLICATE KEY UPDATE ' .
                $fields;

        return $query_string;
    }

    /**
     * Deletes data
     * @access public
     */
    public function get_delete_query($table, $joins, $filters, $limit)
    {
        $query_string = 'DELETE' .
                    ' FROM ' .$table .
                    $this->prepare_joins($joins) .
                    $this->prepare_filters($filters) .
                    $this->prepare_limit($limit);

        return $query_string;
    }

}