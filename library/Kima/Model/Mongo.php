<?php
/**
 * Kima Model Mongo
 * @author Steve Vega
 */
namespace Kima\Model;

use \Kima\Database;

/**
 * Mongo
 * Mongo Model Class
 */
class Mongo implements IModel
{

    /**
     * Gets the table name format for the database
     * @param  string $table
     * @param  string $database
     * @param  string $prefix
     * @return string
     */
    public function get_table($table, $database = '', $prefix = '');

    /**
     * Gets the join syntax for the query
     * @param  string $table
     * @param  string $key
     * @param  string $join_key
     * @param  string $database
     * @return string
     */
    public function get_join($table, $key, $join_key = '', $database = '');

    /**
     * Gets the order syntax for the query
     * @param  string $field
     * @param  string $order
     * @return string
     */
    public function get_order($field, $order = 'ASC');

    /**
     * Prepares the fields for a fetch query
     * @param  array  $fields
     * @return string
     */
    public function prepare_fetch_fields(array $fields);

    /**
     * Prepares the fields for a save query
     * @param  array  $fields
     * @return string
     */
    public function prepare_save_fields(array $fields);

    /**
     * Prepares query joins
     * @param  array  $joins
     * @return string
     */
    public function prepare_joins(array $joins);

    /**
     * Prepares query filters
     * @param  array  $filters
     * @return string
     */
    public function prepare_filters(array $filters);

    /**
     * Prepares query grouping
     * @param  array  $group
     * @return string
     */
    public function prepare_group(array $group);

    /**
     * Prepares query order values
     * @param  array  $order
     * @return string
     */
    public function prepare_order(array $order);

    /**
     * Prepares query limit
     * @param $limit
     * @param $start
     * @return string
     */
    public function prepare_limit($limit = 0, $start = 0);

    /**
     * Gets fetch query
     * @param  array  $params
     * @return string
     */
    public function get_fetch_query(array $params);

    /**
     * Gets update query
     * @param  array  $params
     * @return string
     */
    public function get_update_query(array $params);

    /**
     * Gets insert/update query
     * @param  array  $params
     * @return string
     */
    public function get_put_query(array $params);

    /**
     * Gets delete query
     * @param  array  $params
     * @return string
     */
    public function get_delete_query(array $params);

}
