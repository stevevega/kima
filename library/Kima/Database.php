<?php
/**
 * Kima Database
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Error;
use \Kima\Database\Pdo;
use \Kima\Database\Mongo;

/**
 * Database
 * Abstract Factory implementation for the database
 */
class Database
{

    /**
     * Error messages
     */
    const ERROR_INVALID_DATABASE_ENGINE = '"%s" is not a valid database engine';

    /**
     * constructor
     */
    private function __construct(){}

    /**
     * Gets a Database instance
     * @param string $db_engine The database engine
     * @return Pdo | Mongo
     */
    public static function get_instance($db_engine = null)
    {
        switch ($db_engine)
        {
            case 'mysql':
                return Pdo::get_instance($db_engine);
            case 'mongo' :
                return Mongo::get_instance($db_engine);
            default:
                Error::set(sprintf(self::ERROR_INVALID_DATABASE_ENGINE, $db_engine));
                break;
        }
    }

}