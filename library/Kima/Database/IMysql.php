<?php
/**
 * Kima PDO
 *
 * @author Steve Vega
 */
namespace Kima\Database;

use Kima\Error;
use PDOStatement;

/**
 * Handles database using PDO driver
 *
 * @see  http://php.net/manual/en/class.pdo.php
 */
interface IMysql extends IDatabase
{
    /**
     * Error messages
     */
    const ERROR_PDO_EMPTY_QUERY = 'PDO query error: Query is empty';
    const ERROR_PDO_EMPTY_MODEL = 'PDO query error: Model is empty';
    const ERROR_PDO_QUERY_ERROR = 'PDO query error: "%s"';
    const ERROR_PDO_EXECUTE_ERROR = 'PDO execute error: "%s"';
    const ERROR_INVALID_BIND_VALUE = 'PDO invalid bind value "%s"';
    const ERROR_PDO_CONNECTION_FAILED = 'PDO Connection failed: "%s"';
    const ERROR_NO_PDO = 'PDO extension is not present on this server';

    /**
     * Call a store procedure
     *
     * @param array $options The execution options
     *
     * @return array
     */
    public function call(array $options): array;

    /**
     * Copy a database row
     *
     * @param array $options The execution options
     *
     * @return bool
     */
    public function copy(array $options): bool;

    /**
     * Binds values using PDO prepare statements
     *
     * @param PDOStatement $statement
     * @param array        $binds
     */
    public function bind_values(PDOStatement &$statement, array $binds): void;

    /**
     * Escapes the string to prepare it for db queries
     *
     * @param string $string
     *
     * @return string
     */
    public function escape(string $string): string;

    /**
     * Gets the last inserted id
     *
     * @return string
     */
    public function last_insert_id(): string;

    /**
     * Begins a transaction
     *
     * @return bool
     */
    public function begin(): bool;

    /**
     * Commits transaction
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Rollbacks transaction
     *
     * @return bool
     */
    public function rollback(): bool;
}
