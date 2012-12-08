<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Application;
use \Kima\Error;
use \PDO;
use \PDOStatement;

/**
 * Database
 *
 * Handles database using PDO
 */
class Database
{

    /**
     * instance
     * @var Kima_Application
     */
    private static $_instance;

    /**
     * Database connection
     * @var PDO
     */
    private $_connection;

    /**
     * Database host
     * @var string
     */
    private $_host = '';

    /**
     * Database name
     * @var string
     */
    private $_database = '';

    /**
     * constructor
     */
    private function __construct()
    {
        # make sure pdo is available
        if (!extension_loaded('pdo')) {
            Error::set(__METHOD__, 'PDO extension is not present on this server');
        }

        # just set the default host and database name
        $config = Application::get_instance()->get_config();
        $this->set_database($config->database['name'])
            ->set_host($config->database['host']);
    }

    /**
     * gets the Database instance
     * @return Kima\Database
     */
    public static function get_instance()
    {
        isset(self::$_instance) || self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * checks database connection status
     * if theres no connection creates a new one
     * @return PDO
     */
    public function _get_connection()
    {
        # lets check if we already got a connection to this host
        if (empty($this->_connection)) {
            # set the username and password
            $config = Application::get_instance()->get_config();
            $user = $config->database['user'];
            $password = $config->database['password'];

            # make the connection
            $this->_connect($user, $password);
        }

        return $this->_connection;
    }

    /**
     * creates a new database connection
     * @param string $user
     * @param string $password
     * @return PDO
     */
    private function _connect($user, $password)
    {
        # make the database connection
        $dsn = 'mysql:dbname=' . $this->_database .';host=' . $this->_host;
        try {
            $this->_connection = new PDO($dsn, $user, $password);
            return $this->_connection;
        } catch (PDOException $e) {
            Error::set( __METHOD__, 'PDO Connection failed: ' . $e->getMessage());
        }
    }

    /**
     * executes a query
     * @param string $query
     * @param array $binds
     * @param boolean $select_query
     * @param mixed $model
     * @param boolean $fetch_all
     * @return mixed
     */
    public function execute($query, array $binds = array(), $select_query = true, $model = null, $fetch_all = false)
    {
        # validate query
        if (empty($query)) {
            Error::set(__METHOD__ , 'Database query error: empty query');
        }

        try {
            $statement = $this->_get_connection()->prepare($query);
            $this->bind_values(&$statement, $binds);
            $statement->execute();

            if ($select_query) {
                while ($row = $statement->fetchObject($model)) {
                    $result[] = $row;
                }
                return !$fetch_all && isset($result[0]) ? $result[0] : $result;
            }

            # in a normal query we just let the user it succeed
            return true;
        } catch (PDOException $e) {
            Error::set( __METHOD__, 'PDO execute failed: ' . $e->getMessage());
        }
    }


    /**
     * Binds values using PDO prepare statements
     * @param PDOStatement $statement
     * @param array $binds
     */
    private function bind_values(PDOStatement $statement, array $binds)
    {
        foreach ($binds as $key => $bind) {
            switch (true) {
                case is_int($value) :
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value) :
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value) :
                    $type = PDO::PARAM_NULL;
                    break;
                default :
                    $type = PDO::PARAM_STR;
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
        if (is_string($string)) {
            # get the current database connection
            $this->_get_connection()->quote($string);
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
        return $this->_get_connection()->lastInsertId();
    }

    /**
     * begins a transaction
     * @return boolean
     */
    public function begin()
    {
        # begins transaction
        return $this->_get_connection()->beginTransaction();
    }

    /**
     * commits transaction
     * @return boolean
     */
    public function commit()
    {
        # commits a transaction
        return $this->_get_connection()->commit();
    }

    /**
     * rollbacks transaction
     * @return boolean
     */
    public function rollback()
    {
        # roll backs a transaction
        return $this->_get_connection()->rollBack();
    }

    /**
     * sets the current database host
     * @param string $host
     * @return Kima\Database
     */
    public function set_host($host)
    {
        # set the current host
        $this->_host = $host;
        return $this;
    }

    /**
     * sets the current database
     * @param $string $database
     * @return Kima\Database
     */
    public function set_database($database)
    {
        # set the current database
        $this->_database = $database;
        return $this;
    }

}