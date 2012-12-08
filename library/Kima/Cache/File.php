<?php
/**
 * Namespace Kima
 */
namespace Kima\Cache;

/**
 * Namespaces to use
 */
use \Kima\Cache\ACache,
    \Kima\Error;

/**
 * File Cache
 *
 * File Cache system
 * @package Kima
 */
class File extends ACache
{

    /**
     * @param string $_cache_type
     */
    protected $_cache_type = 'file';

    /**
     * Cache folder path
     * @access private
     * @var string
     */
    private $_folder_path;

    /**
     * Constructor
     * @access public
     * @param array $options
     */
    public function __construct($options = array())
    {
        if (isset($options['prefix'])) {
            $this->_set_prefix($options['prefix']);
        }

        if (isset($options['folder'])) {
            $this->_set_folder_path($options['folder']);
        }
    }

    /**
     * Gets the cache content by time parameter
     * @access public
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->_get_key($key);
        $cache_path = $this->_folder_path . '/' . $key . '.cache';
        if (!is_readable($cache_path)) {
            return null;
        }

        $item = @unserialize(file_get_contents($cache_path));
        $is_valid_cache = $item['expiration']<=0 || time() < $item['expiration'];

        return $is_valid_cache
            ? $item['value']
            : null;
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
        if (is_readable($file_path)) {
            $key = $this->_get_key($key);
            $cache_path = $this->_folder_path . '/' . $key . '.cache';

            if (is_readable($cache_path) && filemtime($file_path) <= filemtime($cache_path)) {
                $item = unserialize(file_get_contents($cache_path));
                return $item['value'];
            }
        }

        return null;
    }

    /**
     * Set the cache
     * @access public
     * @param string $key
     * @param string $value
     * @param int $expiration
     */
    public function set($key, $value, $expiration = 0)
    {
        $expiration = intval($expiration);
        $key = $this->_get_key($key);
        $value = array(
            'expiration' => $expiration>0 ? time() + $expiration : 0,
            'value' => $value);

        $handler = fopen($this->_folder_path . '/' . $key . '.cache', 'w');
        fwrite($handler, serialize($value));
        fclose($handler);
    }

    /**
     * Gets the folder path
     * @return string
     */
    public function get_folder_path()
    {
        return $this->_folder_path;
    }

    /**
     * Sets the cache path
     * @param string $path
     */
    public function _set_folder_path($folder_path)
    {
        if (!is_dir($folder_path)) {
            $oldumask = umask(0);
            mkdir($folder_path, 0777);
            umask($folder_path);
        }

        // path should be a writable folder
        is_dir($folder_path) && is_writable($folder_path)
            ? $this->_folder_path = $folder_path
            : Error::set(__METHOD__, ' Cache folder path ' . $folder_path . ' is not accesible or writable');
    }

}