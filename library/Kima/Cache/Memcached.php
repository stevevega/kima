<?php
/**
 * Kima Cache Memcached
 * @author Steve Vega
 */
namespace Kima\Cache;

use Memcached as PhpMemcached;

/**
 * Memcached Adapter for Kima Cache
 */
class Memcached extends PhpMemcached implements ICache
{

    /**
     * Memcached Connection Pool
     */
    const MEMCACHED_POOL = 'kima_memcached';

    /**
     * Default cache values
     */
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 11211;
    const DEFAULT_WEIGHT = 0;

    /**
     * Construct
     * @param array $options the config options
     */
    public function __construct(array $options = [])
    {
        // call the parent with the cache pool name
        parent::__construct(self::MEMCACHED_POOL);

        // add the memcache server pool
        if (!$this->getServerList() && isset($options['memcached']['server'])) {
            $this->set_server_pool($options['memcached']['server']);
        }
    }

    /**
     * Gets a cache key
     * @param  string $key the cache key
     * @return mixed
     */
    public function get($key)
    {
        $item = parent::get($key);

        return $item ? $item['value'] : null;
    }

    /**
     * Gets a cache key using the file last mofication
     * as reference instead of the cache expiration
     * @param  string $key       the cache key
     * @param  string $file_path the file path
     * @return mixed
     */
    public function get_by_file($key, $file_path)
    {
        // can we access the original file?
        if (!is_readable($file_path)) {
            return null;
        }

        $item = parent::get($key);

        return (filemtime($file_path) <= $item['timestamp']) ? $item['value'] : null;
    }

    /**
     * Sets the cache key
     * @param string $key        the cache key
     * @param mixed  $value
     * @param time   $expiration
     */
    public function set($key, $value, $expiration = 0)
    {
        $value = ['timestamp' => time(), 'value' => $value];

        return parent::set($key, $value, $expiration);
    }

    /**
     * Sets servers to the memcached servers pool
     * @param array $servers
     */
    private function set_server_pool($servers)
    {
        $servers = (array) $servers;
        foreach ($servers as $server) {
            $host = isset($server['host']) ? $server['host'] : self::DEFAULT_HOST;
            $port = isset($server['port']) ? $server['port'] : self::DEFAULT_PORT;
            $weight = isset($server['weight']) ? $server['weight'] : self::DEFAULT_WEIGHT;
            $servers[] = [$host, $port, $weight];
        }

        $this->addServers($servers);
    }

}
