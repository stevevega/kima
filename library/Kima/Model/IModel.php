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
     * Prepares the fields for a fetch query
     * @param  array  $fields
     * @param  string $table
     * @return string
     */
    public function prepare_fetch_fields(array $fields);

    /**
     * Prepares the fields for a save query
     * @param  array  $fields
     * @param  array  $binds
     * @return string
     */
    public function prepare_save_fields(array $fields, array &$binds);

    /**
     * Prepares query joins
     * @param  array  $joins
     * @return string
     */
    public function prepare_joins(array $joins);

    /**
     * Prepares query filters
     * @param  array  $filters
     * @param  array  $binds
     * @return string
     */
    public function prepare_filters(array $filters, array &$binds);

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
    public function prepare_limit(array &$binds, $limit = 0, $start = 0);

    /**
     * Gets fetch query
     * @param  array   $params
     * @param  boolean $is_count_query
     * @return string
     */
    public function get_fetch_query(array &$params, $is_count_query = false);

    /**
     * Gets update query
     * @param  array  $params
     * @return string
     */
    public function get_update_query(array &$params);

    /**
     * Gets insert/update query
     * @param  array  $params
     * @return string
     */
    public function get_put_query(array &$params);

    /**
     * Gets delete query
     * @param  array  $params
     * @return string
     */
    public function get_delete_query(array &$params);

}
