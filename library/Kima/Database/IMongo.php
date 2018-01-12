<?php
/**
 * Kima Database Mongo Interface
 *
 * @author Óscar Fernández
 */
namespace Kima\Database;

use Kima\Error;
use MongoDB\Collection;

/**
 * Defines the behaviour of mongo database.
 */
interface IMongo extends IDatabase
{
    /**
     * Error messages
     */
    const ERROR_NO_MONGO = 'Mongo extension is not present on this server';
    const ERROR_NO_COLLECTION = 'Mongo error: empty collection name';
    const ERROR_MONGO_QUERY = 'Mongo query error: "%s"';
    const ERROR_MONGO_AGGREGATION = 'Mongo aggregation error: "%s"';
    const ERROR_WRONG_UPDATE_LIMIT = 'You shouldn\'t perform an update, using a limit value different than 1';

    /**
     * Applies an aggreate method to a mongo collection
     *
     * @see    http://php.net/manual/en/mongocollection.aggregate.php
     *
     * @param array $options
     *
     * @return array
     */
    public function aggregate(array $options): array;

    /**
     * Applies a distinct method to a mongo collection
     *
     * @see http://php.net/manual/en/mongocollection.distinct.php
     *
     * @param array $options
     *
     * @return array
     */
    public function distinct(array $options): array;
}
