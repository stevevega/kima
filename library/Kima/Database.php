<?php
/**
 * Kima Database
 *
 * @author Steve Vega
 */
namespace Kima;

use Kima\Database\IDatabase;
use Kima\Database\Mongo;
use Kima\Database\Pdo;

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
     * Class constants
     */
    const MYSQL = 'mysql';
    const MONGO = 'mongo';

    /**
     * constructor
     */
    private function __construct()
    {
    }

    /**
     * Gets a Database instance
     *
     * @param string $db_engine The database engine
     *
     * @return IDatabase
     */
    public static function get_instance($db_engine = null): IDatabase
    {
        switch ($db_engine) {
            case self::MYSQL:
                return Pdo::get_instance($db_engine);
            case self::MONGO:
                return Mongo::get_instance($db_engine);
            default:
                Error::set(sprintf(self::ERROR_INVALID_DATABASE_ENGINE, $db_engine));
                break;
        }
    }
}
