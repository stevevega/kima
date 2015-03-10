<?php
/**
 * Kima Model
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Model\Mysql;
use \Kima\Model\Mongo;
use \Kima\Model\ResultSet;
use \Kima\Prime\App;
use \Kima\Util\String;
use \ReflectionObject;
use \ReflectionProperty;

/**
 * Model
 * Gets a model with the corresponding db engine
 */
abstract class Model
{

    /**
     * Error messages
     */
     const ERROR_NO_DEFAULT_DB_ENGINE = 'Default database engine is not present in the app config';
     const ERROR_INVALID_DB_MODEL = 'Invalid database engine: "%s"';
     const ERROR_NO_TABLE = 'Required constant "TABLE" not present in model "%s"';
     const ERROR_NO_JOIN_TABLE = 'Required join table field is empty';
     const ERROR_INVALID_FUNCTION = 'Function "%s" is not available for %s models';

     /**
      * Join constants
      */
     const JOIN_TABLE = 'table';
     const JOIN_TYPE = 'type';
     const JOIN_LEFT = 'left';
     const JOIN_INNER = 'inner';
     const JOIN_RIGHT = 'right';
     const JOIN_ON = 'on';

     /**
      * Order constants
      */
     const ORDER_ASC = 'ASC';
     const ORDER_DESC = 'DESC';

    /**
      * Common formats
      */
     const FORMAT_UNIXTIME = 'UNIX_TIMESTAMP(%s)';
     const FORMAT_MAX = 'MAX(%s)';
     const GROUP_CONCAT_DISTINCT = 'GROUP_CONCAT(DISTINCT(%s) SEPARATOR "%s")';

    /**
     * The model name
     * @var string
     */
    private $model;

    /**
     * The model adapter
     * @var string
     */
    private $adapter;

    /**
     * The model database
     * @var string
     */
    private $database;

    /**
     * The model prefix
     * @var string
     */
    private $prefix;

    /**
     * The database table name
     * @var string
     */
    private $table;

    /**
     * Query fields
     * @var array
     */
    private $fields = [];

    /**
     * Query joins
     * @var array
     */
    private $joins = [];

    /**
     * Query filters/conditions
     * @var array
     */
    private $filters = [];

    /**
     * Query group by having
     * @var array
     */
    private $having = [];

    /**
     * Query binds for prepare statements
     * @var array
     */
    private $binds = [];

    /**
     * The query limit
     * @var string
     */
    private $limit = 0;

    /**
     * Query start value for pagination
     * @var string
     */
    private $start = 0;

    /**
     * Query grouping field
     * @var string
     */
    private $group = [];

    /**
     * Query order
     * @var string
     */
    private $order = [];

    /**
     * Async/sync execution
     * @var boolean
     */
    private $async;

    /**
     * Prevent Mongo upsert flag
     * @var boolean
     */
    private $prevent_upsert;

    /**
     * The query string created
     * @var string
     */
    private $query_string;

    /**
     * The db engine
     * @var string
     */
    private $db_engine;

    /**
     * Fetch query total count
     * @var int
     */
    private $total_count;

    /**
     * Sets debug mode
     * @var boolean
     */
    private $debug = false;

    /**
     * constructor
     */
    public function __construct()
    {
        // get the application config
        $config = App::get_instance()->get_config();

        // set the default model
        $this->set_default_model();

        // set the default db engine
        $this->set_default_db_engine($config);

        // set the model adapter
        $this->set_model_adapter();

        // set a database prefix
        if (isset($config->database[$this->db_engine]['prefix'])) {
            $this->set_prefix($config->database[$this->db_engine]['prefix']);
        }
    }

    /**
     * Gets the total count for a query
     * @return int
     */
    public function get_total_count()
    {
        return $this->total_count;
    }

    /**
     * Returns the current object converted to array
     * @param  array $objects
     * @return array
     */
    public function to_array(array $objects = [])
    {
        $objects = empty($objects) ? [$this] : $objects;
        $result = [];

        foreach ($objects as $key => $object) {
            $ref = new ReflectionObject($object);
            $properties = $ref->getProperties(ReflectionProperty::IS_PUBLIC);
            foreach ($properties as $property) {
                $result[$key][$property->getName()] = $property->getValue($object);
            }
        }

        return $result;
    }

    /**
     * Sets the default database engine for the model
     * @param \Kima\Config
     */
    private function set_default_db_engine(Config $config)
    {
        if (!defined($this->model . '::DB_ENGINE')) {
            if (!isset($config->database['default'])) {
                Error::set(self::ERROR_NO_DEFAULT_DB_ENGINE);
            }

            $this->set_db_engine($config->database['default']);
        } else {
            $this->db_engine = constant($this->model . '::DB_ENGINE');
        }
    }

    /**
     * Sets the database engine for the model
     * @param  string $db_engine
     * @return Model
     */
    public function set_db_engine($db_engine)
    {
        $this->db_engine = (string) $db_engine;

        return $this;
    }

    /**
     * Set the model adapter
     * @return mixed
     */
    private function set_model_adapter()
    {
        // get the database model instance
        switch ($this->db_engine) {
            case 'mysql':
                $this->adapter = new Mysql();
                break;
            case 'mongo':
                $this->adapter = null;
                break;
            default:
                Error::set(sprintf(self::ERROR_INVALID_DB_MODEL, $this->db_engine));
                break;
        }
    }

    /**
     * Sets the model table/collection default prefix
     * @param string $prefix
     */
    private function set_prefix($prefix)
    {
        $this->prefix = (string) $prefix;

        return $this;
    }

    /**
     * Set the default model
     */
    private function set_default_model()
    {
        // set the model
        $model = get_called_class();
        $this->set_model($model);
    }

    /**
     * Sets the model name and table used
     * @param  string $model
     * @return Model
     */
    public function set_model($model)
    {
        // set the model
        $model = (string) $model;
        $this->model = $model;

        // set the table for this model
        if (!defined($model . '::TABLE')) {
            Error::set(sprintf(self::ERROR_NO_TABLE, $model));
        }

        $table = constant($model . '::TABLE');

        $this->table($table);

        return $this;
    }

    /**
     * Set the database to use
     * @param string $database
     */
    public function database($database)
    {
        $this->database = (string) $database;

        return $this;
    }

    /**
     * Sets the database table which should be used for insertion
     * @param string $table
     */
    public function table($table)
    {
        $this->table = (string) $table;

        return $this;
    }

    /**
     * Sets a join with another table(s) for a query
     * @param array $joins
     */
    public function join(array $joins)
    {
        $this->joins = array_merge($this->joins, $joins);

        return $this;
    }

    /**
     * Sets the query filters
     * @param array $filter
     */
    public function filter(array $filters)
    {
        $this->filters = array_merge($this->filters, $filters);

        return $this;
    }

    /**
     * Sets the query having
     * @param array $having
     */
    public function having(array $having)
    {
        $this->having = array_merge($this->having, $having);

        return $this;
    }

    /**
     * Set binds used for prepare statements
     * @param  array       $binds
     * @return \Kima\Model
     */
    public function bind(array $binds)
    {
        $this->binds = array_merge($this->binds, $binds);

        return $this;
    }

    /**
     * Sets a group join
     * @param array group
     */
    public function group(array $group)
    {
        $this->group = array_merge($this->group, $group);

        return $this;
    }

    /**
     * Sets a response order
     * @param array $options
     */
    public function order($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Sets a query limit and start pagination value if necessary
     * @param int $limit
     * @param int $page
     */
    public function limit($limit, $page = 0)
    {
        $limit = intval($limit);
        $page = intval($page);

        if ($limit > 0) {
            $this->limit = $limit;
            $this->start = $page > 0 ? $limit * ($page - 1) : 0;
        }

        return $this;
    }

    /**
     * Sets the whether the call is made sync/async
     * @param boolean $async
     */
    public function async($async)
    {
        $this->async = (boolean) $async;

        return $this;
    }

    /**
     * Prevents Mongo's upsert
     * Can be used before a `put` invocation
     * @param boolean $prevent_upsert
     */
    public function prevent_upsert($prevent_upsert = true)
    {
        $this->prevent_upsert = $prevent_upsert;

        return $this;
    }

    /**
     * Turns on debug mode
     */
    public function debug()
    {
        $this->debug = true;

        return $this;
    }

    /**
     * Sets the query fields to fetch/insert
     * @param array $fields
     */
    private function set_fields(array $fields)
    {
        $this->fields = array_merge($fields, $this->fields);

        return $this->fields;
    }

    /**
     * Get the query parameters
     * @return array
     */
    private function get_query_params()
    {
        return [
            'fields' => $this->fields,
            'database' => $this->database,
            'prefix' => $this->prefix,
            'table' => $this->table,
            'joins' => $this->get_joins(),
            'filters' => $this->filters,
            'having' => $this->having,
            'binds' => $this->binds,
            'group' => $this->group,
            'order' => $this->order,
            'limit' => $this->limit,
            'start' => $this->start,
            'async' => $this->async
        ];
    }

    /**
     * Clears the query params
     */
    private function clear_query_params()
    {
        $this->fields = [];
        $this->database = '';
        $this->prefix = '';
        $this->table = constant($this->model . '::TABLE');
        $this->joins = [];
        $this->filters = [];
        $this->having = [];
        $this->binds = [];
        $this->group = [];
        $this->order = [];
        $this->limit = 0;
        $this->start = 0;
        $this->async = null;
    }

    /**
     * Fetch one result of data from the database
     * Example $fields values:
     * array('id_user', 'name', 'id_city', 'city.name' => 'city_name')
     * @param array   $fields
     * @param boolean $return_as_array
     */
    public function fetch(array $fields = [], $return_as_array = false)
    {
        // make sure we limit one result
        $this->limit(1);
        $result = $this->fetch_results($fields, false, false, $return_as_array);

        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Fetch multiple results from the database
     * Example $fields values:
     * array('id_user', 'name', 'id_city', 'city.name' => 'city_name')
     * @param array   $fields
     * @param boolean $get_as_result_set gets a result set with additional info as total count
     * @param boolean $return_as_array
     */
    public function fetch_all(
        array $fields = [], $get_as_result_set = false, $return_as_array = false)
    {
        return $this->fetch_results(
            $fields, true, $get_as_result_set, $return_as_array);
    }

    /**
     * Fetch query results
     * @param boolean $fetch_all
     * @param boolean $get_as_result_set gets a result set with additional info as total count
     * @param boolean $return_as_array
     */
    private function fetch_results(
        array $fields, $fetch_all, $get_as_result_set, $return_as_array)
    {
        // make sure we have the primary key
        $this->fields = $this->set_fields($fields);
        $query_params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_fetch_query($query_params)
            : null;

        $count_query_string = '';

        // set execution options
        $options = [
            'query' => $query_params,
            'query_string' => $this->query_string,
            'get_count' => $get_as_result_set ? true : false,
            'model' => $this->model,
            'count_query_string' => $count_query_string,
            'fetch_all' => $fetch_all,
            'debug' => $this->debug,
        ];

        if ($get_as_result_set && $this->adapter) {
            $count_query_params = $query_params;
            $this->clear_params_array($count_query_params);

            $count_query_string = $this->adapter->get_fetch_query($count_query_params, true);
            $options['query_count'] = $count_query_params;
            $options['count_query_string'] = $count_query_string;
        }

        // get result from the query
        $result = Database::get_instance($this->db_engine)->fetch($options);

        if ($return_as_array) {
            $result['objects'] = $this->to_array($result['objects']);
        }

        if ($get_as_result_set) {
            $result_set = new ResultSet();
            $result_set->count = $result['count'];
            $result_set->values = $result['objects'];
            $result = $result_set;
        } else {
            $result = $result['objects'];
        }

        $this->clear_query_params();

        return $result;
    }

    /**
     * Aggregate method for models
     * @return array
     */
    public function aggregate()
    {
        $params = $this->get_query_params();

        // set execution options
        $options = [
            'query' => $params,
            'model' => $this->model,
            'fetch_all' => true
        ];

        // get result from the query
        $result = Database::get_instance($this->db_engine)->aggregate($options);

        $this->clear_query_params();

        return $result['objects'];
    }

    /**
     * Distinct method for model
     * @param string $field
     */
    public function distinct($field)
    {
        $this->fields = $field;
        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_fetch_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'model' => $this->model,
        ];

        // get result from the query
        $result = Database::get_instance($this->db_engine)->distinct($options);

        $result = $result['objects'];

        $this->clear_query_params();

        return $result;
    }

    /**
     * Updates data
     * Use this method for batch or custom updates
     * @param array $fields
     */
    public function update_model()
    {
        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_update_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'query_string' => $this->query_string,
            'debug' => $this->debug,
            'prevent_upsert' => $this->prevent_upsert,
            'update' => true
        ];

        # run the query
        $this->clear_query_params();

        return Database::get_instance($this->db_engine)->put($options);
    }

    /**
     * Inserts/updates data one at a time
     * @param array $fields
     */
    public function put(array $fields = [])
    {
        $this->fields = $this->set_fields($fields);

        if (!empty($this->filters) || !empty($this->prevent_upsert)) {
            return $this->update_model();
        }

        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_put_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'query_string' => $this->query_string,
            'debug' => $this->debug
        ];

        $this->clear_query_params();

        return Database::get_instance($this->db_engine)->put($options);
    }

    /**
     * Clones a row in the database using the fields requested and filters
     * @param array $fields
     */
    public function copy(array $fields = [])
    {
        $this->set_fields($fields);
        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_copy_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'query_string' => $this->query_string,
            'debug' => $this->debug
        ];

        # run the query
        $this->clear_query_params();

        return Database::get_instance($this->db_engine)->copy($options);
    }

    /**
     * Deletes data
     * @param array $fields
     */
    public function delete()
    {
        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_delete_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'query_string' => $this->query_string,
            'debug' => $this->debug
        ];

        $this->clear_query_params();

        return Database::get_instance($this->db_engine)->delete($options);
    }

    /**
     * Remove duplicate values from the joins and gets the final value
     * @return array
     */
    private function get_joins()
    {
        $unique_joins = [];
        foreach ($this->joins as $key => $join) {
            if (in_array($join, $unique_joins)) {
                unset($this->joins[$key]);
            } else {
                $unique_joins[] = $join;
            }
        }

        return $this->joins;
    }

    /**
     * Clears the basic query params
     */
    private function clear_params_array(array &$params)
    {
        $params['fields'] = [];
        $params['order'] = [];
        $params['start'] = 0;
        $params['limit'] = 0;
        $params['i'] = 1;
        $params['binds'] = [];
    }
}
