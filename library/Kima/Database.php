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

/**
 * Database
 *
 * Handles database using mysqli
 * @package Kima
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
     * @access private
     * @var Mysqli
     */
    private static $_connection;

    /**
     * Database host
     * @access private
     * @var string
     */
    private static $_host = '';

    /**
     * Database name
     * @access private
     * @var string
     */
    private static $_database = '';

    /**
     * constructor
     */
    private function __construct()
    {
        # make sure mysqli exists
        if (!function_exists('mysqli_connect')) {
            Error::set(__METHOD__, 'mysqli is not present on this server');
        }

        # just set the default host and database name
        $config = Application::get_instance()->get_config();
        self::$_database = $config->database['name'];
        self::$_host = $config->database['host'];
    }

    /**
     * gets the Database instance
     * @return Kima_Database
     */
    public static function get_instance()
    {
        isset(self::$_instance) || self::$_instance = new self;
        return self::$_instance;
    }

    /**
     * checks database connection status
     * if theres no connection creates a new one
     * @access private
     */
    public function _get_connection()
    {
        # lets check if we already got a connection to this host
        if (empty(self::$_connection)) {
            # set the username and password
            $config = Application::get_instance()->get_config();
            $user = $config->database['user'];
            $password = $config->database['password'];

            # make the connection
            self::_connect($user, $password);
        }

        return self::$_connection;
    }

    /**
     * creates a new database connection
     * @access private
     * @param string $user
     * @param string $password
     */
    private function _connect($user, $password)
    {
        # make the database connection
        try {
            $connection = mysqli_connect(self::$_host, $user, $password, self::$_database);
        } catch(Exception $e) {
            var_dump($e);
        }

        # store the connection when success, otherwise we set the error message
        if (mysqli_connect_errno()<=0) {
            self::$_connection = $connection;
            return true;
        } else {
            Error::set( __METHOD__, 'DbManager: mysqli connection error: '.mysqli_connect_error());
        }

        # return the connection result
        return false;
    }

    /**
     * executes a query
     * @access public
     * @param string $query
     * @param boolean $select_query
     * @param mixed $model
     * @param boolean $fetch_all
     */
    public function execute($query, $select_query=true, $model=null, $fetch_all=false)
    {
        # validate query
        if (empty($query)) {
            Error::set(__METHOD__ , 'mysqli query error: empty query');
        }

        # get the connection to the host
        $connection = self::_get_connection();

        $rs = $connection->query($query);

        # lets try to run the query
        if ($rs) {
            # when is a select query we need to return the results
            if ($select_query) {
                while($row = $rs->fetch_object($model)) {
                    $result[] = $row;
                }

                # return the result
                return !$fetch_all && isset($result[0]) ? $result[0] : $result;

                # free the result set
                $rs->close();
            }

            # in a normal query we just let the user it succeed
            return true;
        }

        # an error message if the query failed
        Error::set(__METHOD__, 'DbManager: mysqli query error: ' . $connection->error);
    }

    /**
     * Escapes the string to prepare it for db queries
     * @access public
     * @param string $string
     * @param boolean $extra_safe
     * @return string
     */
    public function escape($string, $extra_safe=false)
    {
        // escape strings
        if (is_string($string)) {
            # get the current database connection
            $connection = self::_get_connection();

            $string = $extra_safe==true
                ? addcslashes($connection->real_escape_string($data), '%_')
                : $connection->real_escape_string($string);

            # clear garbage
            unset($connection);
        }

        return $string;
    }

    /**
     * gets the last inserted id
     * @access public
     * @return mixed
     */
    public function get_last_id()
    {
        # get the connection
        $connection = self::_get_connection();

        # get the last insert id
        $last_id = $connection->insert_id;

        # clean memory
        unset($connection);

        # ask for the last inserted id
        return $last_id;
    }

    /**
     * begins a transaction
     * @access public
     */
    public function begin()
    {
        # starts the transaction
        $this->execute('BEGIN', false);
    }

    /**
     * finish transaction
     * @access public
     * @param boolean commit
     */
    public function finish($commit=true)
    {
        # end a started transaction
        $this->execute(($commit ? 'COMMIT' : 'ROLLBACK'), false);
    }

    /**
     * sets the current database host
     * @access public
     * @param string $host
     */
    public function set_host($host)
    {
        # set the current host
        $this->_host = $host;
    }

    /**
     * sets the current database
     * @access public
     * @param $string $database
     */
    public function set_database($database)
    {
        # set the current database
        $this->_database = $database;
    }

}