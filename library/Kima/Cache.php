<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Cache\File,
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
            case 'File' :
                return new File($params);
            default :
                Error::set(__METHOD__, 'Invalid Cache system "' . $type . '" required');
        }
    }

}