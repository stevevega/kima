<?php
/**
 * Kima Abstract Cache
 * @author Steve Vega
 */
namespace Kima\Cache;

use \Kima\Error;

/**
 * Abstract class for cache systems adapters
 */
abstract class ACache
{

    /**
     * Construct
     * @param array $options the config options
     */
    abstract function __construct(array $options = []);

    /**
     * Gets a cache key
     * @param string $key the cache key
     * @return mixed
     */
    abstract function get($key);

    /**
     * Gets a cache key using the file last mofication
     * as reference instead of the cache expiration
     * @param string $key the cache key
     * @param string $file_path the file path
     * @return mixed
     */
    abstract function get_by_file($key, $file_path);

    /**
     * Sets the cache key
     * @param string $key the cache key
     * @param mixed $value
     * @param time $expiration
     */
    abstract function set($key, $value, $expiration = 0);

    /**
     * Error messages
     */
     const ERROR_NO_CACHE_SYSTEM = '%s extension is not enabled on this server.';

    /**
     * The cache system type
     * @var string $cache_type
     */
    protected $cache_type;

    /**
     * The cache key prefix
     * @var string
     */
    protected $prefix;

    /**
     * Whether the cache is enabled or not
     * @var boolean
     */
    protected $cache_enabled;

    /**
     * Gets the current cache type
     * @return string
     */
    public function get_type()
    {
        return isset($this->cache_type) ? $this->cache_type : null;
    }

    /**
     * Returns the key with prefix included if needed
     * @param string $key
     * @return string
     */
    protected function get_key($key)
    {
        return empty($this->prefix)
            ? $key
            : $this->prefix . '_' . $key;
    }

    /**
     * Sets the cache key prefix
     * @param string $prefix
     */
    protected function set_prefix($prefix)
    {
        $this->prefix = (string)$prefix;
        return $this;
    }

}