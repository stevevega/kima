<?php
/**
 * Kima PDO
 * @author Steve Vega
 */
namespace Kima\Database;

use \Kima\Application,
    \Kima\Database\ADatabase,
    \Kima\Error,
    \PDO as PdoDriver,
    \PDOException,
    \PDOStatement;

/**
 * PDO
 * Handles database using PDO driver
 */
class Pdo extends ADatabase
{

    /**
     * Error messages
     */
     const ERROR_NO_PDO = 'PDO extension is not present on this server';
     const ERROR_PDO_CONNECTION_FAILED = 'PDO Connection failed: "%s"';
     const ERROR_PDO_EMPTY_QUERY = 'PDO query error: Query is empty';
     const ERROR_PDO_EMPTY_MODEL = 'PDO query error: Model is empty';
     const ERROR_PDO_QUERY_ERROR = 'PDO query error: "%s"';
     const ERROR_PDO_EXECUTE_ERROR = 'PDO execute error: "%s"';

    /**
     * instance
     * @var Pdo
     */
    private static $instance;

    /**
     * Database connection
     * @var PDO
     */
    private $connection;

    /**
     * Database host
     * @var string
     */
    protected $host;

    /**
     * Database name
     * @var string
     */
    protected $database;

    /**
     * constructor
     */
    private function __construct()
    {
        // make sure pdo is available
        if (!extension_loaded('pdo'))
        {
            Error::set(self::ERROR_NO_PDO);
        }

        // set the default host and database name
        $config = Application::get_instance()->get_config();
        $this->set_database($config->database['mysql']['name'])
            ->set_host($config->database['mysql']['host']);
    }

    /**
     * Gets the Database instance
     * @param string $db_engine The database engine
     * @return Pdo
     */
    public static function get_instance($db_engine)
    {
        isset(self::$instance) || self::$instance = new self;
        return self::$instance;
    }

    /**
     * checks database connection status
     * if theres no connection creates a new one
     * @return PDO
     */
    public function get_connection()
    {
        # lets check if we already got a connection to this host
        if (empty($this->connection))
        {
            # set the username and password
            $config = Application::get_instance()->get_config();
            $user = $config->database['mysql']['user'];
            $password = $config->database['mysql']['password'];

            # make the connection
            $this->connect($user, $password);
        }

        return $this->connection;
    }

    /**
     * creates a new database connection
     * @param string $user
     * @param string $password
     * @return PDO
     */
    public function connect($user = '', $password = '')
    {
        # make the database connection
        $dsn = 'mysql:dbname=' . $this->database .';host=' . $this->host;
        try
        {
            $this->connection = new PdoDriver($dsn, $user, $password);
            return $this->connection;
        }
        catch (PDOException $e)
        {
            Error::set(sprintf(self::ERROR_PDO_CONNECTION_FAILED, $e->getMessage()));
        }
    }

    /**
     * Fetch results from the database
     * @param array $options The execution options
     * @return mixed
     */
    public function fetch(array $options)
    {
        if (empty($options['model']))
        {
            Error::set(self::ERROR_PDO_EMPTY_MODEL);
        }

        $statement = $this->execute($options);
        while ($row = $statement->fetchObject($options['model']))
        {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Update/Inserts to the database
     * @param array $options The execution options
     * @return boolean
     */
    public function put(array $options)
    {
        $this->execute($options);
        return true;
    }

    /**
     * Deletes to the database
     * @param array $options The execution options
     * @return boolean
     */
    public function delete(array $options)
    {
        $this->execute($options);
        return true;
    }

    /**
     * Executes an operation
     * @param array $options
     * @return mixed
     */
    public function execute(array $options)
    {
        // validate query
        if (empty($options['query_string']))
        {
            Error::set(self::ERROR_PDO_EMPTY_QUERY);
        }

        try
        {
            $statement = $this->get_connection()->prepare($options['query_string']);

            // bind prepare statement values if necessary
            if (!empty($options['binds']))
            {
               $this->bind_values($statement, $options['binds']);
            }
            $success = $statement->execute();

            if (!$success)
            {
                $error = $statement->errorInfo();
                Error::set(sprintf(self::ERROR_PDO_QUERY_ERROR, $error[2]));
            }

            return $statement;
        }
        catch (PDOException $e)
        {
            Error::set(sprintf(self::ERROR_PDO_EXECUTE_ERROR, $e->getMessage()));
        }
    }

    /**
     * Binds values using PDO prepare statements
     * @param PDOStatement $statement
     * @param array $binds
     */
    public function bind_values(PDOStatement &$statement, array $binds)
    {
        foreach ($binds as $key => $bind)
        {
            switch (true)
            {
                case is_int($bind) :
                    $type = PdoDriver::PARAM_INT;
                    break;
                case is_bool($bind) :
                    $type = PdoDriver::PARAM_BOOL;
                    break;
                case is_null($bind) :
                    $type = PdoDriver::PARAM_NULL;
                    break;
                default :
                    $type = PdoDriver::PARAM_STR;
                    break;
            }

            $statement->bindValue($key, $bind, $type);
        }
    }


    /**
     * Escapes the string to prepare it for db queries
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        // escape strings
        if (is_string($string))
        {
            # get the current database connection
            $this->get_connection()->quote($string);
        }
        return $string;
    }

    /**
     * gets the last inserted id
     * @return mixed
     */
    public function get_last_id()
    {
        # get the last insert id
        return $this->get_connection()->lastInsertId();
    }

    /**
     * begins a transaction
     * @return boolean
     */
    public function begin()
    {
        # begins transaction
        return $this->get_connection()->beginTransaction();
    }

    /**
     * commits transaction
     * @return boolean
     */
    public function commit()
    {
        # commits a transaction
        return $this->get_connection()->commit();
    }

    /**
     * rollbacks transaction
     * @return boolean
     */
    public function rollback()
    {
        # roll backs a transaction
        return $this->get_connection()->rollBack();
    }

}