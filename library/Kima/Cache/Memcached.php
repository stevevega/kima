<?php
/**
 * Namespace Kima
 */
namespace Kima\Cache;

/**
 * Namespaces to use
 */
use \Kima\Cache,
    \Kima\Error,
    \Memcached as PhpMemcached;

/**
 * Memcached
 *
 * Memcached system
 */
class Memcached extends Cache
{

    /**
     * Memcached Connection Pool
     */
    const MEMCACHED_POOL = 'Kima_Memcached';

    /**
     * @param string $_cache_type
     */
    protected $_cache_type = 'memcached';

    /**
     * @param Memcached $_memcached
     */
    protected $_memcached;

    /**
     * Constructor
     * Creates memcached connections, add servers
     * @access public
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->_memcached = new PhpMemcached(MEMCACHED_POOL);

        if ($this->_memcached->getServerList()) {
            return;
        }

        if (isset($options['memcached']['server'])) {
            $servers = array();

            foreach ($options['memcached']['server'] as $server) {
                $host = isset($server['host']) ? $server['host'] : '127.0.0.1';
                $port = isset($server['port']) ? $server['port'] : '11211';
                $weight = isset($server['weight']) ? $server['weight'] : 0;
                $servers[] = array($host, $port, $weight);
            }

            $this->addServers($servers);
        }
    }

    /**
     * Set the cache
     * @access public
     * @param string $key
     * @param mixed $value
     * @param int $expiration
     * @return boolean
     */
    public function set($key, $value, $expiration = 0)
    {
        $value = array(
            'timestamp' => time(),
            'value' => $value);

        return $this->_memcached->set($key, $value, $expiration);
    }

    /**
     * Gets a cache item
     * @access public
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $item = $this->_memcached->get($key);
        return $item ? $item['value'] : null;
    }

    /**
     * Gets the cache content by file modification
     * @access public
     * @param string $key
     * @param string $original_file_path
     * @return string
     */
    public function get_by_file($key, $original_file_path)
    {
        // can we access the original file?
        if (is_readable($original_file_path)) {
            $item = $this->_memcached->get($key);

            // do we have a valid cache?, if so, is it newer than the last template modification date?
            return (filemtime($original_file_path) <= $item['timestamp'])
                ? $item['value']
                : null;
        }
        return null;
    }

}