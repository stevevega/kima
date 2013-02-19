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
     * @param array $fields
     * @param array $raw_fields
     * @param string $table
     * @return string
     */
    function prepare_fetch_fields(array $fields, array $raw_fields, $table);

    /**
     * Prepares the fields for a save query
     * @param array $fields
     * @param array $binds
     * @return string
     */
    function prepare_save_fields(array $fields, array &$binds);

    /**
     * Prepares query joins
     * @param array $joins
     * @return string
     */
    function prepare_joins(array $joins);

    /**
     * Prepares query filters
     * @param array $filters
     * @param array $binds
     * @return string
     */
    function prepare_filters(array $filters, array &$binds);

    /**
     * Prepares query grouping
     * @param array $group
     * @return string
     */
    function prepare_group(array $group);

    /**
     * Prepares query order values
     * @param array $order
     * @return string
     */
    function prepare_order(array $order);

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
    function get_fetch_query(array &$params);

    /**
     * Gets update query
     * @param array $params
     * @return string
     */
    function get_update_query(array $params);

    /**
     * Gets insert/update query
     * @param array $params
     * @return string
     */
    function get_put_query(array &$params);

    /**
     * Gets delete query
     * @param array $params
     * @return string
     */
    function get_delete_query(array $params);

}