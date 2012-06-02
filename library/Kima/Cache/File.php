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
 * File Cache
 *
 * File Cache system
 * @package Kima
 */
class File extends Cache
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
        if (isset($options['folder'])) {
            $this->_set_folder_path($options['folder']);
        }
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

    /**
     * Set the cache
     * @access public
     * @param string $cache_file
     * @param string $content
     */
    public function set($cache_file, $content)
    {
        // Set the cache
        $handler = fopen($this->_folder_path . '/' . $cache_file . '.cache', 'w');
        fwrite($handler, serialize($content));
        fclose($handler);
    }

    /**
     * Gets the cache content by time parameter
     * @access public
     * @param string $cache_file
     * @param int $time
     * @return string
     */
    public function get($cache_file, $time=3600)
    {
        // Set the file path
        $cache_path = $this->_folder_path . '/' . $cache_file . '.cache';

        // can we access the cache file? if so, is the cache on the time frame we need?
        return (is_readable($cache_path) && time() < (filemtime($cache_path) + $time))
            ? @unserialize(file_get_contents($cache_path))
            : null;
    }

    /**
     * Gets the cache content by file modification
     * @access public
     * @param string $cache_file
     * @param string $original_file_path
     * @return string
     */
    public function get_by_file($cache_file, $original_file_path)
    {
        // can we access the original file?
        if (is_readable($original_file_path)) {
            // set the cache full path
            $file_path = $this->_folder_path . '/' . $cache_file . '.cache';

            // do we have a valid cache?, if so, is it newer than the last template modification date?
            return (is_readable($file_path) && filemtime($original_file_path) <= filemtime($file_path))
                ? unserialize(file_get_contents($file_path))
                : null;
        }
        return null;
    }

}