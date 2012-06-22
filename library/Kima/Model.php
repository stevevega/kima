<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Model\Mysql,
    \Kima\Util\String;

/**
 * Model
 *
 * Base class for models
 * @package Kima
 */
abstract class Model
{

    /**
     * The model name
     * @access private
     * @var string
     */
    private $_model = '';

    /**
     * The database system
     * @var string
     */
    private $_database = '';

    /**
     * The database table name
     * @access private
     * @var string
     */
    private $_table = '';

    /**
     * Query fields
     * @access private
     * @var array
     */
    private $_fields = array();

    /**
     * Query joins
     * @access private
     * @var array
     */
    private $_joins = array();

    /**
     * Query filters/conditions
     * @access private
     * @var array
     */
    private $_filters = array();

    /**
     * The query limit
     * @access private
     * @var string
     */
    private $_limit = 0;

    /**
     * Query start value for pagination
     * @access private
     * @var string
     */
    private $_start = 0;

    /**
     * Query grouping field
     * @access private
     * @var string
     */
    private $_group = array();

    /**
     * Query order
     * @access private
     * @var string
     */
    private $_order = array();

    /**
     * The query string created
     * @access public
     * @var string
     */
    public $query_string = '';

    /**
     * constructor
     */
    public function __construct()
    {
        $config = Application::get_instance()->get_config();

        if (!isset($config->database['system'])) {
            Error::set(__METHOD__, 'Default database.system is not present in the app config');
        }

        $database = $config->database['system'];
        switch ($database) {
            case 'mysql':
                $this->_database = new Mysql();
                break;

            default:
                Error::set(__METHOD__, 'Invalid database system: ' . $database);
                break;
        }

        if (isset($config->database['prefix'])) {
            $this->_prefix = $config->database['prefix'];
        }

        # set the model if it was extended by a model class
        $model = get_called_class();

        if ($model != get_class()) {
            # set the model
            $this->_model = $model;

            # set the table name based on the model
            $table = String::camel_case_to_underscore($model);
            $this->_set_table($table);
        }
    }

    /**
     * Sets the database table which should be used for insertion
     * @access protected
     * @param string $table
     * @param string $database
     */
    protected function _set_table($table, $database = '', $prefix = null)
    {
        if (is_null($prefix)) {
            $prefix = $this->_prefix;
        }

        $this->_table = $this->_database->get_table($table, $database, $prefix);
        return $this;
    }

    /**
     * Sets fields required for a query
     * Example values:
     * SELECT queries: array('id_user', 'name', 'id_city', 'city.name' => 'city_name')
     * INSERT/UPDATE queries: array('id_user' => 1, 'name' => 'Pizcuilo', 'id_city' => 1)
     * @access protected
     * @param string $fields
     */
    public function fields($fields)
    {
        # fields should be an array
        is_array($fields)
            ? $this->_fields = $fields
            : Error::set(__METHOD__, 'expecting an array for query fields', false);

        return $this;
    }

    /**
     * Sets a join with another table for a query
     * @access public
     * @param string $table
     * @param string $key
     * @param string $join_key
     * @param string $database
     */
    public function join($table, $key, $join_key = '', $database = '')
    {
        $this->_joins[] = $this->_database->get_join($table, $key, $join_key, $database);
        return $this;
    }

    /**
     * Sets a query filter
     * @access public
     * @param string $filter
     */
    public function filter($filter)
    {
        $this->_filters[] = $filter;
        return $this;
    }

    /**
     * Sets a group join
     * @access public
     * @param string $field
     */
    public function group($field)
    {
        $this->_group[] = $field;
        return $this;
    }

    /**
     * Sets a response order
     * @access public
     * @param string $field
     * @param string $order
     */
    public function order($field, $order = 'ASC')
    {
        $this->_order[] = $this->_database->get_order($field, $order);
        return $this;
    }

    /**
     * Sets a query limit and start pagination value if necessary
     * @access public
     * @param int $limit
     * @param int $page
     */
    public function limit($limit, $page = 0)
    {
        $limit = intval($limit);
        $page = intval($page);

        if ($limit > 0) {
            $this->_limit = $limit;
            $this->_start = $page > 0 ? $limit * ($page - 1) : 0;
        }

        return $this;
    }

    /**
     * Fetch one field of data from the database
     * @access public
     */
    public function fetch($fetch_all = false)
    {
        if (!$fetch_all) {
            $this->limit(1);
        }

        $params = array(
            'fields' => $this->_fields,
            'table' => $this->_table,
            'joins' => $this->_joins,
            'filters' => $this->_filters,
            'group' => $this->_group,
            'order' => $this->_order,
            'limit' => $this->_limit,
            'start' => $this->_start);

        # build the select query
        $this->query_string = $this->_database->get_fetch_query($params);

        # get result from the query
        return Database::get_instance()->execute($this->query_string, true, $this->_model, $fetch_all);
    }

    /**
     * Updates data
     * @access public
     */
    private function _update()
    {
        $params = array(
            'fields' => $this->_fields,
            'table' => $this->_table,
            'filters' => $this->_filters,
            'order' => $this->_order,
            'limit' => $this->_limit);

        $this->query_string = $this->_database->get_update_query($params);

        # run the query
        return Database::get_instance()->execute($this->query_string, false);
    }

    /**
     * Inserts/updates data
     * @access public
     */
    public function put()
    {
        if (!empty($this->_filters)) {
            return $this->_update();
        }

        $params = array(
            'fields' => $this->_fields,
            'table' => $this->_table);

        $this->query_string = $this->_database->get_put_query($params);

        return Database::get_instance()->execute($this->query_string, false);
    }

    /**
     * Deletes data
     * @access public
     */
    public function delete()
    {
        $params = array(
            'table' => $this->_table,
            'joins' => $this->_joins,
            'filters' => $this->_filters,
            'limit' => $this->_limit);

        $this->query_string = $this->_database->get_delete_query($paramss);

        return Database::get_instance()->execute($this->query_string, false);
    }

}