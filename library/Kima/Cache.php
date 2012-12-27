<?php
/**
 * Kima Cache
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Cache\Apc,
    \Kima\Cache\File,
    \Kima\Cache\Memcached,
    \Kima\Error;

/**
 * Cache
 * Abstract Factory implementation for Cache
 */
class Cache
{

    /**
     * Error messages
     */
    const ERROR_DEFAULT_NOT_SET = 'No cache system was enabled in the application config';
    const ERROR_INVALID_CACHE_SYSTEM = '"%s" is not a valid cache system';

    /**
     * private construct
     */
     private function __construct(){}

    /**
     * Get an instance of the required cache system
     * @param string $type the cache type
     * @param array $options the config options set for the cache system
     * @return Apc|Memcached|File
     */
    public static function get_instance($type, array $options = [])
    {
        switch ($type)
        {
            case 'default':
                if (isset($options['default']) && !empty($options['default']))
                {
                    return self::get_instance($options['default'], $options);
                }
                else
                {
                    Error::set(self::ERROR_DEFAULT_NOT_SET, false);
                    break;
                }
            case 'apc':
                return new Apc($options);
            case 'memcached' :
                return new Memcached($options);
            case 'file':
                return new File($options);
            default:
                Error::set(sprintf(self::ERROR_INVALID_CACHE_SYSTEM, $type));
                break;
        }
    }

}