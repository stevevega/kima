<?php
/**
 * Namespace Kima
 */
namespace Kima\Cache;

/**
 * Namespaces to use
 */
use \Kima\Error;

/**
 * Abstract Cache
 *
 * Cache Abstract class
 */
abstract class ACache
{

    /**
     * @var string $_cache_type
     */
    protected $_cache_type;

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
     * @param string $file_path
     */
    public abstract function get_by_file($key, $file_path);

    /**
     * cache set
     * @param string $key
     * @param mixed $value
     * @param time $expiration
     */
    public abstract function set($key, $value, $expiration = 0);

    /**
     * The cache key prefix
     * @var string
     */
    private $_prefix;

    /**
     * gets the current cache type
     * @return string
     */
    public function get_type()
    {
        return isset($this->_cache_type) ? $this->_cache_type : null;
    }

    /**
     * Returns the key with prefix included if needed
     * @param string $key
     * @return string
     */
    protected function _get_key($key)
    {
        return empty($this->_prefix)
            ? $key
            : $this->_prefix . '_' . $key;
    }

    /**
     * Sets the cache key prefix
     */
    protected function _set_prefix($prefix)
    {
        $this->_prefix = (string)$prefix;
        return $this;
    }

}