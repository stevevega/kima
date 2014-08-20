<?php
/**
 * Kima Cache APC
 * @author Steve Vega
 */
namespace Kima\Cache;

use \Kima\Error;

/**
 * APC Adapter for Kima Cache
 */
class Apc implements ICache
{

    /**
     * Error messages
     */
    const ERROR_NO_APC = 'APC extension is not loaded';

    /**
     * Cache system
     */
    const APC_EXTENSION = 'apc';

    /**
     * Construct
     * @param array $options the config options
     */
    public function __construct(array $options = [])
    {
        if (!extension_loaded(self::APC_EXTENSION)) {
            Error::set(self::ERROR_NO_APC);
        }
    }

    /**
     * Gets a cache key
     * @param  string $key the cache key
     * @return mixed
     */
    public function get($key)
    {
        $item = apc_fetch($key);

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

        $item = apc_fetch($key);

        // cache is valid and is it newer than the last template modification date?
        return (filemtime($file_path) <= $item['timestamp']) ? $item['value'] : null;
    }

    /**
     * Sets the cache key
     * @param  string  $key        the cache key
     * @param  mixed   $value
     * @param  time    $expiration
     * @return boolean
     */
    public function set($key, $value, $expiration = 0)
    {
        $value = ['timestamp' => time(), 'value' => $value];

        return apc_store($key, $value, $expiration);
    }

}
