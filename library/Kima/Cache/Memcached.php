<?php
/**
 * Kima Cache Memcached
 * @author Steve Vega
 */
namespace Kima\Cache;

use \Kima\Cache\ACache,
    \Kima\Error,
    \Memcached as PhpMemcached;

/**
 * Memcached Adapter for Kima Cache
 */
class Memcached extends ACache
{

    /**
     * Memcached Connection Pool
     */
    const MEMCACHED_POOL = 'Kima_Memcached';

    /**
     * @var string $cache_type
     */
    protected $cache_type = 'memcached';

    /**
     * @var Memcached $memcached
     */
    protected $memcached;

    /**
     * Construct
     * @param array $options the config options
     */
    public function __construct(array $options = array())
    {
        if (!extension_loaded($this->cache_type))
        {
            Error::set(sprintf(self::ERROR_NO_CACHE_SYSTEM, $this->cache_type));
        }

        $this->memcached = new PhpMemcached(MEMCACHED_POOL);

        if (isset($options['prefix']))
        {
            $this->set_prefix($options['prefix']);
        }

        if (!$this->memcached->getServerList() && isset($options['memcached']['server']))
        {
            $servers = array();

            foreach ($options['memcached']['server'] as $server)
            {
                $host = isset($server['host']) ? $server['host'] : '127.0.0.1';
                $port = isset($server['port']) ? $server['port'] : '11211';
                $weight = isset($server['weight']) ? $server['weight'] : 0;
                $servers[] = array($host, $port, $weight);
            }

            $this->memcached->addServers($servers);
        }
    }

    /**
     * Gets a cache key
     * @param string $key the cache key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->get_key($key);
        $item = $this->memcached->get($key);
        return $item ? $item['value'] : null;
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
        // can we access the original file?
        if (is_readable($file_path))
        {
            $key = $this->get_key($key);
            $item = $this->memcached->get($key);

            // is it newer than the last file modification date?
            return (filemtime($file_path) <= $item['timestamp'])
                ? $item['value']
                : null;
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
        $key = $this->get_key($key);
        $value = array(
            'timestamp' => time(),
            'value' => $value);

        return $this->memcached->set($key, $value, $expiration);
    }

}