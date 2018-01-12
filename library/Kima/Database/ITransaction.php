<?php
/**
 * Kima Database Transaction Interface
 *
 * @author Óscar Fernández
 */
namespace Kima\Database;

/**
 * Defines the behaviour of database with transactions.
 *
 * @see  http://php.net/manual/en/class.pdo.php
 */
interface ITransaction
{
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
