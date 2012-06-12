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
class Cache
{

    /**
     * constructor
     */
     private function __construct(){}

    /**
     * get instance of the required cache system
     * @param string $type
     * @param array $options
     */
    public static function get_instance($type, $options = array())
    {
        switch ($type) {
            case 'default' :
                if (isset($options['default']) && !empty($options['default'])) {
                    return self::get_instance($options['default'], $options);
                } else {
                    Error::set(__METHOD__, 'No cache system was enabled in the config', false);
                }
            case 'apc' :
                return new Apc($options);
            case 'memcached' :
                return new Memcached($options);
            case 'file' :
                return new File($options);
            default :
                Error::set(__METHOD__, 'Invalid Cache system "' . $type . '" requested');
        }
    }

}