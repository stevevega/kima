<?php
/**
 * Kima Model
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Config,
    \Kima\Database,
    \Kima\Model\Mysql,
    \Kima\Model\Mongo,
    \Kima\Util\String;

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
     * The query string created
     * @var string
     */
    private $query_string;

    /**
     * The db engine
     */
    private $db_engine;


    /**
     * The primary key
     */
    private $primary_key;

    /**
     * constructor
     */
    public function __construct()
    {
        // get the application config
        $config = Application::get_instance()->get_config();

        // set the default model
        $this->set_default_model();

        // set the default db engine
        $this->set_default_db_engine($config);

        // set the model adapter
        $this->set_model_adapter();

        // set a database prefix
        if (isset($config->database[$this->db_engine]['prefix']))
        {
            $this->set_prefix($config->database[$this->db_engine]['prefix']);
        }
    }

    /**
     * Sets the default database engine for the model
     * @param \Kima\Config
     */
    private function set_default_db_engine(Config $config)
    {
        if (!defined($this->model . '::DB_ENGINE'))
        {
            if (!isset($config->database['default']))
            {
                Error::set(self::ERROR_NO_DEFAULT_DB_ENGINE);
            }

            $this->set_db_engine($config->database['default']);
        }
        else
        {
            $this->db_engine = constant($this->model . '::DB_ENGINE');
        }
    }

    /**
     * Sets the database engine for the model
     * @param string $db_engine
     * @return Model
     */
    public function set_db_engine($db_engine)
    {
        $this->db_engine = (string)$db_engine;
        return $this;
    }

    /**
     * Set the model adapter
     * @return mixed
     */
    private function set_model_adapter()
    {
        // get the database model instance
        switch ($this->db_engine)
        {
            case 'mysql':
                $this->adapter = new Mysql();
                $this->primary_key = defined($this->model . '::PRMARY_KEY')
                    ? constant($this->model . '::PRMARY_KEY')
                    : 'id_' . strtolower($this->table);
                break;
            case 'mongo':
                $this->adapter = null;
                $this->primary_key = '_id';
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
        $this->prefix = (string)$prefix;
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
     * @param string $model
     * @return Model
     */
    public function set_model($model)
    {
        // set the model
        $model = (string)$model;
        $this->model = $model;

        // set the table for this model
        if (!defined($model . '::TABLE'))
        {
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
        $this->database = (string)$database;
        return $this;
    }

    /**
     * Sets the database table which should be used for insertion
     * @param string $table
     */
    public function table($table)
    {
        $this->table = (string)$table;
        return $this;
    }

    /**
     * Sets a join with another table(s) for a query
     * @param array $joins
     */
    public function join(array $joins)
    {
        // get the fields on each join
        foreach ($joins as &$join)
        {
            if (!empty($join['fields']) && is_array($join['fields']))
            {
                if (empty($join['table']))
                {
                    Error::set(self::ERROR_NO_JOIN_TABLE);
                }

                $join['table'] = !empty($join['database'])
                    ? $join_database . '.' . $join['table']
                    : $join['table'];

                foreach ($join['fields'] as $key => $field)
                {
                    is_string($key)
                        ? $this->fields[$join['table'] . '.' . $key] = $field
                        : $this->fields[] = $join['table'] . '.' . $field;
                }

                unset($join['database'], $join['fields']);
            }
        }

        $this->joins = $joins;
        return $this;
    }

    /**
     * Sets the query filters
     * @param array $filter
     */
    public function filter(array $filters)
    {
        $this->filters = $filters;
        return $this;
    }


    /**
     * Set binds used for prepare statements
     * @param array $binds
     * @return \Kima\Model
     */
    public function bind(array $binds)
    {
        $this->binds = $binds;
        return $this;
    }


    /**
     * Sets a group join
     * @param string $field
     */
    public function group($field)
    {
        $this->group[] = $field;
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

        if ($limit > 0)
        {
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
        $this->async = (boolean)$async;
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
            'joins' => $this->joins,
            'filters' => $this->filters,
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
     * @param array $fields
     */
    public function fetch(array $fields = [])
    {
        // make sure we limit one result
        $this->limit(1);
        $result = $this->fetch_results($fields, false);
        return !empty($result[0]) ? $result[0] : null;
    }

    /**
     * Fetch multiple results from the database
     * Example $fields values:
     * array('id_user', 'name', 'id_city', 'city.name' => 'city_name')
     * @param array $fields
     */
    public function fetch_all(array $fields = [])
    {
        return $this->fetch_results($fields, true);
    }

    /**
     * Fetch query results
     * @param boolean $fetch_all
     */
    private function fetch_results(array $fields, $fetch_all = false)
    {
        // make sure we have the primary key
        $this->fields = $this->set_fields($fields);
        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_fetch_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'query_string' => $this->query_string,
            'model' => $this->model,
            'fetch_all' => $fetch_all
        ];

        // get result from the query
        $result = Database::get_instance($this->db_engine)->fetch($options);
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
            'query_string' => $this->query_string
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
        if (!empty($this->filters))
        {
            return $this->update_model();
        }

        // set the primary key if is an existing model
        if (!empty($this->{$this->primary_key}) && !isset($this->fields[$this->primary_key]))
        {
            $this->fields[$this->primary_key] = $this->{$this->primary_key};
        }
        $params = $this->get_query_params();

        // build the query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_put_query($params)
            : null;

        // set execution options
        $options = [
            'query' => $params,
            'query_string' => $this->query_string
        ];

        $this->clear_query_params();
        return Database::get_instance($this->db_engine)->put($options);
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
            'query_string' => $this->query_string
        ];

        $this->clear_query_params();
        return Database::get_instance($this->db_engine)->delete($options);
    }

    /**
     * Ensures the primary key is added to the fetch query
     * @param array $fields
     */
    private function ensure_primary_key(array $fields)
    {
        if (!empty($fields) && !array_key_exists($this->primary_key, $fields))
        {
            array_unshift($fields, $this->primary_key);
        }

        return $fields;
    }

}