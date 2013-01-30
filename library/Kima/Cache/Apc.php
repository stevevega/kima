<?php
/**
 * Kima Cache APC
 * @author Steve Vega
 */
namespace Kima\Cache;

use \Kima\Cache\ACache,
    \Kima\Error;

/**
 * APC Adapter for Kima Cache
 */
class Apc extends ACache
{

    /**
     * @param string $_cache_type
     */
    protected $cache_type = 'apc';

    /**
     * Construct
     * @param array $options the config options
     */
    public function __construct(array $options = []) {
        if (!extension_loaded($this->cache_type))
        {
            Error::set(sprintf(self::ERROR_NO_CACHE_SYSTEM, $this->cache_type));
        }

        $this->cache_enabled = !empty($options['enabled']) ? true : false;

        if (isset($options['prefix']))
        {
            $this->set_prefix($options['prefix']);
        }
    }

    /**
     * Gets a cache key
     * @param string $key the cache key
     * @return mixed
     */
    public function get($key)
    {
        if ($this->cache_enabled)
        {
            $key = $this->get_key($key);
            $item = apc_fetch($key);
            return $item ? $item['value'] : null;
        }

        return null;
    }

    /**
     * Gets a cache key using the file last mofication
     * as reference instead of the cache expiration
     * @param string $key the cache key
     * @param string $file_path the file path
     * @return mixed
     */
    public function get_by_file($key, $file_path)
    {
        if ($this->cache_enabled)
        {
            // can we access the original file?
            if (is_readable($file_path))
            {
                $key = $this->get_key($key);
                $item = apc_fetch($key);

                // do we have a valid cache?, if so, is it newer than the last template modification date?
                return (filemtime($file_path) <= $item['timestamp'])
                    ? $item['value']
                    : null;
            }
        }

        return null;
    }

    /**
     * Sets the cache key
     * @param string $key the cache key
     * @param mixed $value
     * @param time $expiration
     */
    public function set($key, $value, $expiration = 0)
    {
        if ($this->cache_enabled)
        {
            $key = $this->get_key($key);
            $value = [
                'timestamp' => time(),
                'value' => $value];

            return apc_store($key, $value, $expiration);
        }

        return null;
    }

}