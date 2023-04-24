<?php
/**
 * Kima Cache Redis
 *
 * @author Steve Vega
 */
namespace Kima\Cache;

use Kima\Error;
use Redis as PhpRedis;

/**
 * Redis Adapter for Kima Cache
 */
class Redis extends PhpRedis implements ICache
{
    /**
     * Error messages
     */
    const ERROR_INVALID_CONNECTION_TYPE = 'Invalid connection type "%s"';
    const ERROR_INVALID_SERIALIZER = 'Invalid serializer "%s"';

    /**
     * Connection types
     */
    const NON_PERSISTENT = 0;
    const PERSISTENT = 1;

    /**
     * Default database where the cache keys will be saved
     */
    const DEFAULT_DATABASE = 0;

    /**
     * Data serializer
     */
    const SERIALIZER_NONE = 0;
    const SERIALIZER_PHP = 1;
    const SERIALIZER_IGBINARY = 2;

    /**
     * Default cache values
     */
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 6379;
    const DEFAULT_TIMEOUT = 0;

    /**
     * Connection type
     *
     * @var int
     */
    private $connection_type = self::PERSISTENT;

    /**
     * Connection types
     *
     * @var array
     */
    private $connection_types = [self::NON_PERSISTENT, self::PERSISTENT];

    /**
     * Default prefix key
     *
     * @var string
     */
    private $prefix_key = '';

    /**
     * List of available serializers
     *
     * @var array
     */
    private $serializers = [
        self::SERIALIZER_NONE => Redis::SERIALIZER_NONE,
        self::SERIALIZER_PHP => Redis::SERIALIZER_PHP,
        self::SERIALIZER_IGBINARY => Redis::SERIALIZER_IGBINARY
    ];

    /**
     * Construct
     *
     * @param array $options the config options
     */
    public function __construct(array $options = [])
    {
        // call the parent contruct
        parent::__construct();

        // set connection type
        if (isset($options['redis']['connection_type'])) {
            $this->set_connection_type($options['redis']['connection_type']);
        }

        // conect to the redis server
        $host = isset($options['redis']['host']) ? $options['redis']['host'] : self::DEFAULT_HOST;
        $port = isset($options['redis']['port']) ? $options['redis']['port'] : self::DEFAULT_PORT;
        $weight = isset($options['redis']['timeout'])
            ? $options['redis']['timeout']
            : self::DEFAULT_TIMEOUT;

        $database = isset($options['redis']['database'])
            ? $options['redis']['database']
            : self::DEFAULT_DATABASE;

        $this->connect($host, $port, $weight, $database);
        $this->select($database);

        // set data serializer after connection
        if (isset($options['redis']['serializer'])) {
            $this->set_serializer($options['redis']['serializer']);
        }

        // set prefix key value
        if (isset($options['redis']['prefix_key'])) {
            $this->prefix_key = (string) $options['redis']['prefix_key'];
        }
    }

    /**
     * Gets a cache key
     *
     * @param string $key the cache key
     *
     * @return mixed
     */
    public function get($key)
    {
        $item = parent::get($this->prepare_key($key));

        return $item ? $item['value'] : null;
    }

    /**
     * Gets a cache key using the file last mofication
     * as reference instead of the cache expiration
     *
     * @param string $key       the cache key
     * @param string $file_path the file path
     *
     * @return mixed
     */
    public function get_by_file($key, $file_path)
    {
        // can we access the original file?
        if (!is_readable($file_path)) {
            return;
        }

        $item = parent::get($this->prepare_key($key));
        if (false === $item) {
            return null;
        }

        return (filemtime($file_path) <= $item['timestamp']) ? $item['value'] : null;
    }

    /**
     * Gets the timestamp of a cache key
     *
     * @param string $key the cache key
     *
     * @return mixed
     */
    public function get_timestamp($key)
    {
        $item = parent::get($this->prepare_key($key));

        return $item ? $item['timestamp'] : null;
    }

    /**
     * Sets the cache key
     *
     * @param string $key        the cache key
     * @param mixed  $value
     * @param time   $expiration
     */
    public function set($key, $value, $expiration = 0)
    {
        $value = ['timestamp' => time(), 'value' => $value];

        // set the expiration value
        if ($expiration <= 0) {
            $expiration = null;
        }

        parent::set($this->prepare_key($key), $value, $expiration);
    }

    /**
     * Connects to a redis server
     *
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     * @param int    $retry_interval
     *
     * @return bool
     */
    public function connect($host, $port = 6379, $timeout = 0, $retry_interval = 0)
    {
        $this->connection_type === self::PERSISTENT
            ? parent::pconnect($host, $port, $timeout, $retry_interval)
            : parent::connect($host, $port, $timeout, $retry_interval);

        return $this;
    }

    /**
     * Sets the connection type
     *
     * @param int $connection_type
     *
     * @return ICache
     */
    private function set_connection_type($connection_type)
    {
        if (!in_array($connection_type, $this->connection_types)) {
            Error::set(sprintf(self::ERROR_INVALID_CONNECTION_TYPE, $connection_type));
        }

        $this->connection_type = (int) $connection_type;

        return $this;
    }

    /**
     * Sets the redis data serializer
     *
     * @param int $serializer
     */
    private function set_serializer($serializer)
    {
        if (!in_array($serializer, array_keys($this->serializers))) {
            Error::set(sprintf(self::ERROR_INVALID_SERIALIZER, $serializer));
        }

        $this->setOption(Redis::OPT_SERIALIZER, $this->serializers[$serializer]);

        return $this;
    }

    /**
     * Prepares the key with a prefix if it exists
     *
     * @param string $key
     *
     * @return string
     */
    private function prepare_key($key)
    {
        return empty($this->prefix_key) ? $key : sprintf('%s_%s', $this->prefix_key, $key);
    }
}
