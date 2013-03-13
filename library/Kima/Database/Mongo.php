<?php
/**
 * Kima Database Mongo
 * @author Steve Vega
 */
namespace Kima\Database;

use \Kima\Application,
    \Kima\Database\ADatabase,
    \Kima\Error,
    \MongoClient,
    \MongoCollection,
    \MongoConnectionException,
    \MongoCursorException,
    \MongoException,
    \MongoId;

/**
 * Mongo
 * Mongo database hanlder
 */
class Mongo extends ADatabase
{

    /**
     * Error messages
     */
     const ERROR_NO_MONGO = 'Mongo extension is not present on this server';
     const ERROR_NO_COLLECTION = 'Mongo error: empty collection name';
     const ERROR_MONGO_QUERY = 'Mongo query error: "%s"';

    /**
     * The Mongo Client connection
     * @var MongoClient $connection
     */
    private $connection;

    /**
     * The current database
     * @var string $database
     */
    protected $database;

    /**
     * The current host
     * @var string $host
     */
    protected $host;

    /**
     * The Mongo intance
     * @var Mongo $instance
     */
    private static $instance;

    /**
     * constructor
     */
    private function __construct()
    {
        if (!extension_loaded('mongo'))
        {
            Error::set(self::ERROR_NO_MONGO);
        }

        // set the default host and database name
        $config = Application::get_instance()->get_config();

        if (!empty($config->database['mongo']['name']))
        {
            $this->set_database($config->database['mongo']['name']);
        }
        if (!empty($config->database['mongo']['host']))
        {
            $this->set_host($config->database['mongo']['host']);
        }
    }

    /**
     * Gets the Database instance
     * @param string $db_engine The database engine
     * @return MongoClient
     */
    public static function get_instance($db_engine)
    {
        isset(self::$instance) || self::$instance = new self;
        return self::$instance;
    }

    /**
     * Checks database connection status
     * if theres no connection creates a new one
     * @return mixed
     */
    function get_connection()
    {
        // check if we already got a connection to this host
        if (empty($this->connection))
        {
            // set the username and password
            $config = Application::get_instance()->get_config();
            $user = !empty($config->database['mongo']['user'])
                ? $config->database['mongo']['user']
                : '';
            $password = !empty($config->database['mongo']['password'])
                ? $config->database['mongo']['password']
                : '';

            // make the connection
            $this->connect($user, $password);
        }

        return $this->connection;
    }

    /**
     * Creates a new database connection
     * @param string $user
     * @param string $password
     * @return mixed
     */
    function connect($user = '', $password = '')
    {
        // make the database connection
        try
        {
            $credentials = !empty($user) ? $user . ':' . $password . '@' : '';

            $this->connection =
                new MongoClient('mongodb://' . $credentials . $this->host . ':27017/' . $this->database);
            return $this->connection;
        }
        catch (MongoConnectionException $e)
        {
            Error::set('Mongo Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Fetch results from the database
     * @param array $options The execution options
     * @return mixed
     */
    public function fetch(array $options)
    {
        $collection = $this->execute($options);

        try
        {
            $cursor = $collection->find($options['query']['filters'], $options['query']['fields']);

            if (!empty($options['query']['order']))
            {
                // make the sort compatible with mongo
                foreach ($options['query']['order'] as &$sort)
                {
                    $sort = 'DESC' === $sort ? -1 : 1;
                }
                $cursor->sort($options['query']['order']);
            }

            if (!empty($options['query']['start']))
            {
                $cursor->skip($options['query']['start']);
            }

            if (!empty($options['query']['limit']))
            {
                $cursor->limit($options['query']['limit']);
            }

            $objects = [];
            foreach($cursor as $row)
            {
                $object = new $options['model'];
                foreach ($row as $key => $field) {
                    $object->{$key} = $field;
                }
                $objects[] = $object;
            }

            $result['objects'] = $objects;
            $result['count'] = !empty($options['get_count']) ? $cursor->count() : 0;
            return $result;
        }
        catch (MongoCursorException $e)
        {
            Error::set(sprintf(self::ERROR_MONGO_QUERY, $e->getMessage()));
        }
    }

    /**
     * Update/Inserts to the database
     * @param array $options The execution options
     * @return mixed
     */
    public function put(array $options)
    {
        $collection = $this->execute($options);
        $fields = !empty($options['query']['fields'])
            ? $options['query']['fields']
            : [];

        // if filters is empty, we are trying to insert a new value
        $filters = !empty($options['query']['filters'])
            ? $options['query']['filters']
            : ['_id' => new MongoId()];

        try
        {
            // set whether to execute sync/async
            $async = !empty($options['query']['async'])
                ? 0
                : 1;

            return $collection->update(
                $filters,
                $fields,
                ['upsert' => true, 'w' => $async]);
        }
        catch (MongoException $e)
        {
            Error::set(sprintf(self::ERROR_MONGO_QUERY, $e->getMessage()));
        }
    }

    /**
     * Deletes to the database
     * @param array $options The execution options
     * @return mixed
     */
    public function delete(array $options)
    {
        $collection = $this->execute($options);

        try
        {
            return $collection->remove($options['query']['filters']);
        }
        catch (MongoException $e)
        {
            Error::set(sprintf(self::ERROR_MONGO_QUERY, $e->getMessage()));
        }
    }

    /**
     * Executes an operation
     * @param array $options The executions options
     * @return mixed
     */
    public function execute(array $options)
    {
        $connection = $this->get_connection();

        $db_name = !empty($options['query']['database'])
            ? $options['query']['database']
            : $this->database;
        $db = $connection->selectDB($db_name);

        if (empty($options['query']['table']))
        {
            Error::set(self::ERROR_NO_COLLECTION);
        }

        $collection_name = !empty($options['query']['prefix'])
            ? $options['query']['prefix'] . $options['query']['table']
            : $options['query']['table'];

        $collection = new MongoCollection($db, $collection_name);

        return $collection;
    }

}