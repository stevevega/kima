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

        if (isset($options['prefix'])) {
            $this->_set_prefix($options['prefix']);
        }

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

            $this->_memcached->addServers($servers);
        }
    }

    /**
     * Gets a cache item
     * @access public
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->_get_key($key);
        $item = $this->_memcached->get($key);
        return $item ? $item['value'] : null;
    }

    /**
     * Gets the cache content by file modification
     * @access public
     * @param string $key
     * @param string $file_path
     * @return string
     */
    public function get_by_file($key, $file_path)
    {
        // can we access the original file?
        if (is_readable($file_path)) {
            $key = $this->_get_key($key);
            $item = $this->_memcached->get($key);

            // is it newer than the last file modification date?
            return (filemtime($file_path) <= $item['timestamp'])
                ? $item['value']
                : null;
        }
        return null;
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
        $key = $this->_get_key($key);
        $value = array(
            'timestamp' => time(),
            'value' => $value);

        return $this->_memcached->set($key, $value, $expiration);
    }

}