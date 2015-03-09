<?php
/**
 * Kima Cache
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Cache\Apc;
use \Kima\Cache\File;
use \Kima\Cache\Memcached;
use \Kima\Cache\Redis;
use \Kima\Cache\Void;
use \Kima\Prime\App;

/**
 * Cache
 * Factory method implementation for Kima cache
 */
class Cache
{

    /**
     * Error messages
     */
    const ERROR_DEFAULT_NOT_SET = 'No cache system was enabled in the application config';
    const ERROR_INVALID_CACHE_SYSTEM = '"%s" is not a valid cache system';

    /**
     * Cache systems
     */
    const APC = 'apc';
    const FILE = 'file';
    const MEMCACHED = 'memcached';
    const REDIS = 'redis';
    const VOID = 'void';

    /**
     * Options cache key
     */
    const DEFAULT_KEY = 'default';
    const ENABLED = 'enabled';

    /**
     * private construct
     */
     private function __construct() {}

    /**
     * Get an instance of the required cache system
     * @param  string $type    the cache type
     * @param  array  $options the config options set for the cache system
     * @return ICache
     */
    public static function get_instance($type = null, array $options = [])
    {
        if (empty($options)) {
            $options = App::get_instance()->get_config()->cache;
        }

        // return the null object if cache is not enabled
        if (empty($options[self::ENABLED])) {
            return new Void($options);
        }

        switch ($type) {
            case self::DEFAULT_KEY:
            case '':
            case null:
                if (isset($options[self::DEFAULT_KEY])) {
                    return self::get_instance($options[self::DEFAULT_KEY], $options);
                } else {
                    Error::set(self::ERROR_DEFAULT_NOT_SET, false);
                }
                break;
            case self::APC:
                return new Apc($options);
                break;
            case self::FILE:
                return new File($options);
                break;
            case self::MEMCACHED:
                return new Memcached($options);
                break;
            case self::REDIS:
                return new Redis($options);
                break;
            case self::VOID:
                return new Void($options);
                break;
            default:
                Error::set(sprintf(self::ERROR_INVALID_CACHE_SYSTEM, $type));
                break;
        }
    }

}
