<?php
/**
 * Kima Database Ins
 *
 * @author Oscar Fernández
 */
namespace Kima\Database;

/**
 * This interface defines the behaviour of any Database object
 */
interface IDatabase
{
    /**
     * Gets the Database instance
     *
     * @param string $db_engine The database engine
     *
     * @return IDatabase
     */
    public static function get_instance(string $db_engine): IDatabase;

    /**
     * Checks database connection status
     * if theres no connection creates a new one
     *
     * @return mixed
     */
    public function get_connection();

    /**
     * Creates a new database connection
     *
     * @param string $user
     * @param string $password
     *
     * @return mixed
     */
    public function connect(string $user = '', string $password = ''): void;

    /**
     * Fetch results from the database
     *
     * @param array $options The execution options
     *
     * @return mixed
     */
    public function fetch(array $options);

    /**
     * Fetch results from the database
     *
     * @param array $options The execution options
     *
     * @return mixed
     */
    public function put(array $options);

    /**
     * Fetch results from the database
     *
     * @param array $options The execution options
     *
     * @return mixed
     */
    public function delete(array $options);

    /**
     * Executes an operation
     *
     * @param array $options The execution options
     *
     * @return mixed
     */
    public function execute(array $options);

    /**
     * Sets the current database
     *
     * @param $string $database
     * @param string $database
     *
     * @return mixed
     */
    public function set_database(string $database): IDatabase;

    /**
     * Sets the current database host
     *
     * @param string $host
     *
     * @return IDatabase
     */
    public function set_host(string $host): IDatabase;
}
