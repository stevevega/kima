<?php
/**
 * Kima Cache File
 * @author Steve Vega
 */
namespace Kima\Cache;

use \Kima\Cache\ACache,
    \Kima\Error;

/**
 * File Adapter for Kima Cache
 */
class File extends ACache
{

    /**
     * Error Messages
     */
     const ERROR_FOLDER_INACCESSIBLE = ' Cache folder path %s is not accesible or writable';

    /**
     * @param string $_cache_type
     */
    protected $cache_type = 'file';

    /**
     * Cache folder path
     * @access private
     * @var string
     */
    private $folder_path;

    /**
     * Construct
     * @param array $options the config options
     */
    public function __construct(array $options = [])
    {
        if (isset($options['prefix']))
        {
            $this->set_prefix($options['prefix']);
        }

        if (isset($options['folder']))
        {
            $this->set_folder_path($options['folder']);
        }
    }

    /**
     * Gets a cache key
     * @param string $key the cache key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->get_key($key);
        $cache_path = $this->folder_path . PATH_SEPARATOR . $key . '.cache';
        if (!is_readable($cache_path))
        {
            return null;
        }

        $item = @unserialize(file_get_contents($cache_path));
        $is_valid_cache = $item['expiration'] <= 0 || time() < $item['expiration'];

        return $is_valid_cache
            ? $item['value']
            : null;
    }

    /**
     * Gets a cache key using the file last mofication
     * as reference instead of the cache expiration
     * @param string $key the cache key
     * @param string $file_path the file path
     * @return mixed
     */
    public function get_by_file($key, $file_path)
    {
        if (is_readable($file_path)) {
            $key = $this->get_key($key);
            $cache_path = $this->folder_path . PATH_SEPARATOR . $key . '.cache';

            if (is_readable($cache_path) && filemtime($file_path) <= filemtime($cache_path)) {
                $item = unserialize(file_get_contents($cache_path));
                return $item['value'];
            }
        }

        return null;
    }

    /**
     * Sets the cache key
     * @param string $key the cache key
     * @param mixed $value
     * @param time $expiration
     */
    public function set($key, $value, $expiration = 0)
    {
        $expiration = intval($expiration);
        $key = $this->get_key($key);
        $value = [
            'expiration' => $expiration > 0 ? time() + $expiration : 0,
            'value' => $value];

        $handler = fopen($this->folder_path . PATH_SEPARATOR . $key . '.cache', 'w');
        fwrite($handler, serialize($value));
        fclose($handler);
    }

    /**
     * Gets the folder path
     * @return string
     */
    public function get_folder_path()
    {
        return $this->folder_path;
    }

    /**
     * Sets the cache path
     * @param string $path
     */
    public function set_folder_path($folder_path)
    {
        $folder_path = (string)$folder_path;

        if (!is_dir($folder_path))
        {
            $oldumask = umask(0);
            mkdir($folder_path, 0777);
            umask($folder_path);
        }

        // path should be writable
        is_dir($folder_path) && is_writable($folder_path)
            ? $this->folder_path = $folder_path
            : Error::set(sprintf(self::ERROR_FOLDER_INACCESSIBLE, $folder_path));
    }

}