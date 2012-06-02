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
    public abstract function get($key);

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
     * @param array $options
     */
    public static function get_instance($type, $options = array())
    {
        switch ($type) {
            case 'default' :
                if (isset($options['default']) && !empty($options['default'])) {
                    return self::get_instance($options['default'], $options);
                } else {
                    return self::_get_prefered_cache($options);
                }
            case 'apc' :
                return self::_get_apc($options);
            case 'memcached' :
                return self::_get_memcached($options);
            case 'file' :
                return self::_get_file($options);
            default :
                Error::set(__METHOD__, 'Invalid Cache system "' . $type . '" requested');
        }
    }

    /**
     * Gets APC instance
     * @param array $options
     * @return \Kima\Apc
     */
    private static function _get_apc($options)
    {
        if (!self::_is_apc_enabled()) {
            Error::set(__METHOD__, 'APC extension is not enabled on this server.');
        }
        return new Apc($options);
    }

    /**
     * Gets File cache instance
     * @param array $options
     * @return \Kima\File
     */
    private static function _get_file($options)
    {
        return new File($options);
    }

    /**
     * Gets memcached instance
     * @param array $options
     * @return \Kima\Memcached
     */
    private static function _get_memcached($options)
    {
        if (!self::_is_memcached_enabled()) {
            Error::set(__METHOD__, 'Memcached extension is not enabled on this server.');
        }
        return new Memcached($options);
    }

    /**
     * Gets the prefered cache system available
     * @param array $options
     * @return mixed
     */
    private static function _get_prefered_cache($options)
    {
        switch (true) {
            case self::_is_apc_enabled() :
                return self::_get_apc($options);
            case self::_is_memcached_enabled() :
                return self::_get_memcached($options);
            default :
                return self::_get_file($options);
        }
    }

    /**
     * Checks whether APC is enabled or not
     * @return boolean
     */
    protected static function _is_apc_enabled()
    {
        return extension_loaded('apc');
    }

    /**
     * Checks whether Memcached is enabled or not
     * @return boolean
     */
    protected static function _is_memcached_enabled()
    {
        return extension_loaded('memcached');
    }

    /**
     * gets the current cache type
     * @return string
     */
    public function get_type()
    {
        return isset($this->_cache_type) ? $this->_cache_type : null;
    }

}