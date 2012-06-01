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
class Apc
{

     /**
     * Constructor
     * @access public
     * @param array $options
     */
    public function __construct($options = array()){}

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

        return apc_store($key, $value, $expiration);
    }

    /**
     * Gets a cache item
     * @access public
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $item = apc_fetch($key);
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
            $item = apc_fetch($key);

            // do we have a valid cache?, if so, is it newer than the last template modification date?
            return (filemtime($original_file_path) <= $item['timestamp'])
                ? $item['value']
                : null;
        }
        return null;
    }

}