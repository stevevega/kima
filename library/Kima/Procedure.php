<?php
/**
 * Kima Model
 * @author Fabian Hernandez
 */
namespace Kima;

use \Kima\Model\Mysql;
use \Kima\Prime\App;
use \Kima\Prime\Config;

/**
 * Model
 * Gets a procedure model with the corresponding db engine
 */
abstract class Procedure
{

    /**
     * Error messages
     */
     const ERROR_NO_DEFAULT_DB_ENGINE = 'Default database engine is not present in the app config';
     const ERROR_INVALID_DB_MODEL = 'Invalid database engine: "%s"';

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
     * Params used as arguments for the procedures
     */
    private $params = [];

    /**
     * Name of the procedure to be call
     * @var string
     */
    private $procedure_name = '';

    /**
     * Query binds for prepare statements
     * @var array
     */
    private $binds = [];

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
     * Sets the default database engine for the model
     * @param Config
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
     * Set the name of the procedure to be call
     * @param string $procedure_name
     */
    private function set_procedure_name($procedure_name)
    {
        $this->procedure_name = (string) $procedure_name;

        return $this;
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
     * Sets the params for the procedures
     * @param array params
     */
    public function params(array $params)
    {
        $this->params = array_merge($this->params, $params);

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
     * Get the query parameters for the procedure
     * @return array
     */
    private function get_procedure_params()
    {
        return [
            'params' => $this->params,
            'procedure_name' => $this->procedure_name,
            'binds' => $this->binds
        ];
    }

    /**
     * Clears the procedure query params
     */
    private function clear_procedure_params()
    {
        $this->params = [];
        $this->procedure_name = '';
        $this->binds = [];
    }

    /**
     * Call procedure
     * @param  string  $procedure_name
     * @return $result result as array
     */
    public function call($procedure_name)
    {
        // Set the name of the procedure to be vall
        $this->set_procedure_name($procedure_name);

        // Get the procedure query pararms
        $procedure_params = $this->get_procedure_params();

        // build the procedure query using the adapter
        $this->query_string = $this->adapter
            ? $this->adapter->get_procedure_query($procedure_params)
            : null;

        // set execution options
        $options = [
            'query' => $procedure_params,
            'query_string' => $this->query_string,
            'debug' => $this->debug
        ];

        // get result from the query
        $result = Database::get_instance($this->db_engine)->call($options);

        $this->clear_procedure_params();

        return $result['objects'];
    }

}
