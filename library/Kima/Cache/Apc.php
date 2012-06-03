<?php
/**
 * Namespace Kima
 */
namespace Kima\Cache;

/**
 * Namespaces to use
 */
use \Kima\Cache,
    \Kima\Error;

/**
 * APC
 *
 * Alternative PHP Cache system
 */
class Apc extends Cache
{

    /**
     * @param string $_cache_type
     */
    protected $_cache_type = 'apc';

     /**
     * Constructor
     * @access public
     * @param array $options
     */
    public function __construct($options = array()){
        if (isset($options['prefix'])) {
            $this->_set_prefix($options['prefix']);
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
        $item = apc_fetch($key);
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
            $item = apc_fetch($key);

            // do we have a valid cache?, if so, is it newer than the last template modification date?
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

        return apc_store($key, $value, $expiration);
    }

}