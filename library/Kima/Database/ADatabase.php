<?php
/**
 * Kima Database Abstract
 * @author Steve Vega
 */
namespace Kima\Database;

use \Kima\Error;

/**
 * Abstract Database
 * Abstract class for database handlers
 */
abstract class ADatabase
{

    /**
     * Gets the Database instance
     * @param  string $db_engine The database engine
     * @return mixed
     */
    public static function get_instance($db_engine)
    {
        Error::set('Not implemented');
    }

    /**
     * Checks database connection status
     * if theres no connection creates a new one
     * @return mixed
     */
    abstract public function get_connection();

    /**
     * Creates a new database connection
     * @param  string $user
     * @param  string $password
     * @return mixed
     */
    abstract public function connect($user = '', $password = '');

    /**
     * Fetch results from the database
     * @param  array $options The execution options
     * @return mixed
     */
    abstract public function fetch(array $options);

    /**
     * Call a store procedure
     * @param  array $options The execution options
     * @return mixed
     */
    abstract public function call(array $options);

    /**
     * Performs an aggregate method in the database
     * @param  array $options The execution options
     * @return mixed
     */
    abstract public function aggregate(array $options);

    /**
     * Fetch results from the database
     * @param  array $options The execution options
     * @return mixed
     */
    abstract public function put(array $options);

    /**
     * Fetch results from the database
     * @param  array $options The execution options
     * @return mixed
     */
    abstract public function delete(array $options);

    /**
     * Executes an operation
     * @param  array $options The execution options
     * @return mixed
     */
    abstract public function execute(array $options);

    /**
     * Sets the current database
     * @param $string $database
     * @return mixed
     */
    public function set_database($database)
    {
        // set the current database
        $this->database = (string) $database;

        return $this;
    }

    /**
     * Sets the current database host
     * @param  string $host
     * @return mixed
     */
    public function set_host($host)
    {
        # set the current host
        $this->host = (string) $host;

        return $this;
    }

}
