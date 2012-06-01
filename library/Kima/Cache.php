<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Cache\Apc,
    \Kima\Cache\File,
    \Kima\Cache\Memcached,
    \Kima\Error;

/**
 * Cache
 *
 * Cache system
 * @package Kima
 */
abstract class Cache
{

    /**
     * cache contruct
     * @param array $options
     */
    public abstract function __construct($options = array());

    /**
     * cache get
     * @param string $key
     * @param int $time
     */
    public abstract function get($key, $time = 3600);

    /**
     * cache get by file
     * @param string $key
     * @param string $original_file_path
     */
    public abstract function get_by_file($key, $file_path);

    /**
     * cache set
     * @param string $key
     * @param mixed $value
     * @param time $expiration
     */
    public abstract function set($key, $value);

    /**
     * get instance of the required cache system
     * @param string $type
     * @param array $params
     */
    public static function get_instance($type, $params = array())
    {
        switch ($type) {
            case 'apc' :
                if (!extension_loaded('apc')) {
                    Error::set(__METHOD__, 'APC extension is not enabled on this server.');
                }
                return new Apc($params);
            case 'memcached' :
                if (!extension_loaded('Memcached')) {
                    Error::set(__METHOD__, 'Memcached extension is not enabled on this server.');
                }
                return new Memcached($params);
            case 'file' :
                return new File($params);
            default :
                Error::set(__METHOD__, 'Invalid Cache system "' . $type . '" required');
        }
    }

}