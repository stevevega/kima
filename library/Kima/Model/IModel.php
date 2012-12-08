<?php
/**
 * Namespace Kima
 */
namespace Kima\Model;

/**
 * Model Interface
 *
 * Interface used for databases model
 */
interface IModel
{

    /**
     * Gets the table name format for the database
     * @param string $table
     * @param string $database
     * @param string $prefix
     * @return string
     */
    function get_table($table, $database = '', $prefix = '');

    /**
     * Gets the join syntax for the query
     * @param string $table
     * @param string $key
     * @param string $join_key
     * @param string $database
     * @return string
     */
    function get_join($table, $key, $join_key = '', $database = '');

    /**
     * Gets the order syntax for the query
     * @param string $field
     * @param string $order
     * @return string
     */
    function get_order($field, $order = 'ASC');

    /**
     * Prepares the fields for a fetch query
     * @param array $fields
     * @return string
     */
    function prepare_fetch_fields($fields);

    /**
     * Prepares the fields for a save query
     * @param array $fields
     * @return string
     */
    function prepare_save_fields($fields);

    /**
     * Prepares query joins
     * @param array $joins
     * @return string
     */
    function prepare_joins($joins);

    /**
     * Prepares query filters
     * @param array $filters
     * @return string
     */
    function prepare_filters($filters);

    /**
     * Prepares query grouping
     * @param array $group
     * @return string
     */
    function prepare_group($group);

    /**
     * Prepares query order values
     * @param array $order
     * @return string
     */
    function prepare_order($order);

    /**
     * Prepares query limit
     * @param $limit
     * @param $start
     * @return string
     */
    function prepare_limit($limit = 0, $start = 0);

    /**
     * Gets fetch query
     * @param array $params
     * @return string
     */
    function get_fetch_query($params);

    /**
     * Gets update query
     * @param array $params
     * @return string
     */
    function get_update_query($params);

    /**
     * Gets insert/update query
     * @param array $params
     * @return string
     */
    function get_put_query($params);

    /**
     * Gets delete query
     * @param array $params
     * @return string
     */
    function get_delete_query($params);

}