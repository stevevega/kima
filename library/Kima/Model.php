<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Database;
use \Kima\String;

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
    protected function _set_table($table, $database='', $prefix=null)
    {
        if (is_null($prefix)) {
            $config = Application::get_instance()->get_config();
            $prefix = $config->database['prefix'];
        }

        $table = empty($prefix) ? $table : $prefix . '_' . $table;

        $this->_table = empty($database) ? $table : $database . '.' . $table;

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
    public function join($table, $key, $join_key='', $database='')
    {
        $join_table = empty($database) ? $table : $database . '.' . $table;
        $join_query = ' LEFT JOIN ' . $join_table;

        # use join key if necessary
        $join_query .= empty($join_key)
            ? ' USING ( ' . $key . ' )'
            : $join_query .= ' ON ( ' . $table . '.' . $key . '=' . $this->_table . '.' . $join_key . ' )';

        $this->_joins[] = $join_query;
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
    public function order($field, $order='ASC')
    {
        # make sure we use a valid order value
        $order = $order === 'DESC' ? $order : 'ASC';

        $this->_order[] = $field . ' ' . $order;
        return $this;
    }

    /**
     * Sets a query limit and start pagination value if necessary
     * @access public
     * @param int $limit
     * @param int $page
     */
    public function limit($limit, $page=0)
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
     * Prepares the fields for a fetch query
     * @access private
     * @return string
     */
    private function _prepare_fetch_fields()
    {
        # select * fields if none were added
        if (empty($this->_fields)) {
            return '*';
        }

        # prepare every field
        $fields = array();
        foreach ($this->_fields as $field => $value) {
            $fields[] = is_string($field) ? $field . ' AS ' . $value : $value;
        }

        return implode(',', $fields);
    }

    /**
     * Prepares the fields for a save query
     * @access private
     * @return string
     */
    private function _prepare_save_fields()
    {
        # save queries should always provide at least one field
        if (empty($this->_fields)) {
            Error::set(__METHOD__, 'fields for save data were not provided');
        }

        # prepare every field
        $fields = array();
        foreach ($this->_fields as $field => $value) {
            $fields[] = is_string($field)
                ? $field . '=' . Database::get_instance()->escape($value)
                : $value . '=' . Database::get_instance()->escape($this->{$value});
        }

        return implode(',', $fields);
    }

    /**
     * Prepares query joins
     * @access private
     * @return string
     */
    private function _prepare_joins()
    {
        return empty($this->_joins) ? '' : implode(' ', $this->_joins);
    }

    /**
     * Prepares query filters
     * @access private
     */
    private function _prepare_filters()
    {
        return empty($this->_filters) ? '' : ' WHERE ' . implode(' AND ', $this->_filters);
    }

    /**
     * Prepares query grouping
     * @access private
     * @return string
     */
    private function _prepare_group()
    {
        return empty($this->_group) ? '' : ' GROUP BY ' . implode(', ', $this->_group);
    }

    /**
     * Prepares query order values
     * @access private
     * @return string
     */
    private function _prepare_order()
    {
        return empty($this->_order) ? '' : ' ORDER BY ' . implode(', ', $this->_order);
    }

    /**
     * Prepares query limit
     * @access private
     * @return string
     */
    private function _prepare_limit()
    {
        return empty($this->_limit) ? '' : ' LIMIT ' . $this->_start . ', ' . $this->_limit;
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

        # build the select query
        $this->query_string =
            'SELECT ' .
                $this->_prepare_fetch_fields() .
                ' FROM ' . $this->_table .
                $this->_prepare_joins() .
                $this->_prepare_filters() .
                $this->_prepare_group() .
                $this->_prepare_order() .
                $this->_prepare_limit();

        # get result from the query
        return Database::get_instance()->execute($this->query_string, true, $this->_model, $fetch_all);
    }

    /**
     * Updates data
     * @access public
     */
    private function _update()
    {
        # prepare fields for save query
        $fields = $this->_prepare_save_fields();

        # build the query
        $this->query_string =
            'UPDATE ' . $this->_table .
                ' SET ' .
                $fields .
                $this->_prepare_filters() .
                $this->_prepare_order() .
                $this->_prepare_limit();

        # run the query
        return Database::get_instance()->execute($this->query_string, false);
    }

    /**
     * Inserts/updates data
     * @access public
     */
    public function put()
    {
        # if a conditional/filter was added, we use the update query
        if (!empty($this->_filters)) {
            return $this->_update();
        }

        # prepare fields for save query
        $fields = $this->_prepare_save_fields();

        # build the query
        $this->query_string =
            'INSERT INTO ' . $this->_table .
                ' SET ' .
                $fields .
                ' ON DUPLICATE KEY UPDATE ' .
                $fields;

        # run the query
        return Database::get_instance()->execute($this->query_string, false);
    }

    /**
     * Deletes data
     * @access public
     */
    public function delete()
    {
        # build the query
        $this->query_string = 'DELETE' .
                    ' FROM ' .$this->_table .
                    $this->_prepare_joins() .
                    $this->_prepare_filters() .
                    $this->_prepare_limit();

        # run the query
        return Database::get_instance()->execute($this->query_string, false);
    }

}