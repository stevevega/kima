<?php
/**
 * Kima PDO
 *
 * @author Steve Vega
 */
namespace Kima\Database;

use Kima\Error;
use Kima\Prime\App;
use PDO as PdoDriver;
use PDOException;
use PDOStatement;

/**
 * Handles database using PDO driver
 *
 * @see  http://php.net/manual/en/class.pdo.php
 */
final class Pdo implements IDatabase, ITransaction
{
    /**
     * Error messages
     */
    const ERROR_NO_PDO = 'PDO extension is not present on this server';
    const ERROR_NO_AGGREGATE = 'Aggregate not implemented for PDO';
    const ERROR_NO_DISTINCT = 'Distinct not implemented for PDO';
    const ERROR_PDO_CONNECTION_FAILED = 'PDO Connection failed: "%s"';
    const ERROR_PDO_EMPTY_QUERY = 'PDO query error: Query is empty';
    const ERROR_PDO_EMPTY_MODEL = 'PDO query error: Model is empty';
    const ERROR_PDO_QUERY_ERROR = 'PDO query error: "%s"';
    const ERROR_PDO_EXECUTE_ERROR = 'PDO execute error: "%s"';
    const ERROR_INVALID_BIND_VALUE = 'PDO invalid bind value "%s"';

    /**
     * instance
     *
     * @var Pdo
     */
    private static $instance;

    /**
     * Database host
     *
     * @var string
     */
    private $host;

    /**
     * Database name
     *
     * @var string
     */
    private $database;

    /**
     * Database connection
     *
     * @var PDO
     */
    private $connection;

    /**
     * constructor
     */
    public function __construct()
    {
        // make sure pdo is available
        if (!extension_loaded('pdo')) {
            Error::set(self::ERROR_NO_PDO);
        }

        // set the default host and database name
        $config = App::get_instance()->get_config();
        $this->set_database($config->database['mysql']['name'])
            ->set_host($config->database['mysql']['host']);
    }

    /**
     * inheritDoc
     */
    public static function get_instance(string $db_engine): IDatabase
    {
        isset(self::$instance) || self::$instance = new self();

        return self::$instance;
    }

    /**
     * inheritDoc
     */
    public function get_connection()
    {
        // lets check if we already got a connection to this host
        if (empty($this->connection)) {
            // set the username and password
            $config = App::get_instance()->get_config();
            $user = $config->database['mysql']['user'];
            $password = $config->database['mysql']['password'];

            // make the connection
            $this->connect($user, $password);
        }

        return $this->connection;
    }

    /**
     * inheritDoc
     */
    public function connect(string $user = '', string $password = ''): void
    {
        // make the database connection
        $dsn = 'mysql:dbname=' . $this->database . ';host=' . $this->host;
        try {
            $this->connection = new PdoDriver($dsn, $user, $password,
                [PdoDriver::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"]);
        } catch (PDOException $e) {
            Error::set(sprintf(self::ERROR_PDO_CONNECTION_FAILED, $e->getMessage()));
        }
    }

    /**
     * inheritDoc
     */
    public function fetch(array $options): array
    {
        if (empty($options['model'])) {
            Error::set(self::ERROR_PDO_EMPTY_MODEL);
        }

        $statement = $this->execute($options);
        $objects = [];
        while ($row = $statement->fetchObject($options['model'])) {
            $objects[] = $row;
        }
        $result['objects'] = $objects;

        // get the count if necessary
        $count = 0;
        if ($options['get_count']) {
            $options['query_string'] = $options['count_query_string'];
            $options['query'] = $options['query_count'];
            $statement = $this->execute($options);
            $count_result = $statement->fetchObject();
            $count = $count_result->count;
        }

        $result['count'] = $count;

        return $result;
    }

    /**
     * inheritDoc
     */
    public function call(array $options): array
    {
        $statement = $this->execute($options);
        $objects = [];
        while ($row = $statement->fetchObject()) {
            $objects[] = $row;
        }
        $result['objects'] = $objects;

        return $result;
    }

    /**
     * Not implemented for PDO
     *
     * @param array $options
     */
    public function aggregate(array $options): array
    {
        Error::set(self::ERROR_NO_AGGREGATE);
    }

    /**
     * Not implemented for PDO
     *
     * @param array $options
     */
    public function distinct(array $options): array
    {
        Error::set(self::ERROR_NO_DISTINCT);
    }

    /**
     * inheritDoc
     */
    public function put(array $options)
    {
        $this->execute($options);

        return true;
    }

    /**
     * inheritDoc
     */
    public function copy(array $options): bool
    {
        $this->execute($options);

        return true;
    }

    /**
     * inheritDoc
     */
    public function delete(array $options)
    {
        $this->execute($options);

        return true;
    }

    /**
     * inheritDoc
     */
    public function execute(array $options): PDOStatement
    {
        // validate query
        if (empty($options['query_string'])) {
            Error::set(self::ERROR_PDO_EMPTY_QUERY);
        }

        try {
            if (!empty($options['debug'])) {
                var_dump($options);
                exit;
            }

            $statement = $this->get_connection()->prepare($options['query_string']);

            // bind prepare statement values if necessary
            if (!empty($options['query']['binds'])) {
                $this->bind_values($statement, $options['query']['binds']);
            }
            $success = $statement->execute();

            if (!$success) {
                $error = $statement->errorInfo();
                Error::set(sprintf(self::ERROR_PDO_QUERY_ERROR, $error[2]));
            }

            return $statement;
        } catch (PDOException $e) {
            Error::set(sprintf(self::ERROR_PDO_EXECUTE_ERROR, $e->getMessage()));
        }
    }

    /**
     * inheritDoc
     */
    public function escape(string $string): string
    {
        // escape strings
        if (is_string($string)) {
            // get the current database connection
            $this->get_connection()->quote($string);
        }

        return $string;
    }

    /**
     * inheritDoc
     */
    public function last_insert_id(): string
    {
        return $this->get_connection()->lastInsertId();
    }

    /**
     * inheritDoc
     */
    public function begin(): bool
    {
        return $this->get_connection()->beginTransaction();
    }

    /**
     * inheritDoc
     */
    public function commit(): bool
    {
        return $this->get_connection()->commit();
    }

    /**
     * inheritDoc
     */
    public function rollback(): bool
    {
        return $this->get_connection()->rollBack();
    }

    /**
     * inheritDoc
     */
    public function set_database(string $database): IDatabase
    {
        $this->database = $database;

        return $this;
    }

    /**
     * inheritDoc
     */
    public function set_host(string $host): IDatabase
    {
        $this->host = $host;

        return $this;
    }

    /**
     * inheritDoc
     */
    private function bind_values(PDOStatement &$statement, array $binds): void
    {
        foreach ($binds as $key => $bind) {
            switch (true) {
                case is_int($bind):
                    $type = PdoDriver::PARAM_INT;
                    break;
                case is_bool($bind):
                    $type = PdoDriver::PARAM_BOOL;
                    break;
                case is_null($bind):
                    $type = PdoDriver::PARAM_NULL;
                    break;
                case is_object($bind):
                case is_array($bind):
                    Error::set(sprintf(self::ERROR_INVALID_BIND_VALUE, print_r($bind, true)));
                default:
                    $type = PdoDriver::PARAM_STR;
                    break;
            }

            $statement->bindValue($key, $bind, $type);
        }
    }
}
