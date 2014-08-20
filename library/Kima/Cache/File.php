<?php
/**
 * Kima Cache File
 * @author Steve Vega
 */
namespace Kima\Cache;

use \Kima\Error;

/**
 * File Adapter for Kima Cache
 */
class File implements ICache
{

    /**
     * Error Messages
     */
    const ERROR_NO_FOLDER_PATH = 'Option file.folder is required for file cache';
    const ERROR_FOLDER_NOT_EXISTS = 'Folder path %s does not exists and cannot be created';
    const ERROR_FOLDER_PERMISSION = 'Cache folder path %s is not accesible or writable';

    /**
     * Cache file extension
     */
    const FILE_EXTENSION = '.cache';

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
        if (!isset($options['file']['folder'])) {
            Error::set(self::ERROR_NO_FOLDER_PATH);
        }

        $this->set_folder_path($options['file']['folder']);
    }

    /**
     * Gets a cache key
     * @param  string $key the cache key
     * @return mixed
     */
    public function get($key)
    {
        $cache_path = $this->folder_path . DIRECTORY_SEPARATOR . $key . self::FILE_EXTENSION;
        if (!is_readable($cache_path)) {
            return null;
        }

        $item = @unserialize(file_get_contents($cache_path));
        $is_valid_cache = $item['expiration'] <= 0 || time() < $item['expiration'];

        return $is_valid_cache ? $item['value'] : null;
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
        if (!is_readable($file_path)) {
            return null;
        }

        $cache_path = $this->folder_path . DIRECTORY_SEPARATOR . $key . self::FILE_EXTENSION;
        if (is_readable($cache_path) && filemtime($file_path) <= filemtime($cache_path)) {
            $item = unserialize(file_get_contents($cache_path));

            return $item['value'];
        }
    }

    /**
     * Sets the cache key
     * @param string $key        the cache key
     * @param mixed  $value
     * @param time   $expiration
     */
    public function set($key, $value, $expiration = 0)
    {
        $expiration = intval($expiration);
        $value = [
            'expiration' => $expiration > 0 ? time() + $expiration : 0,
            'value' => $value];

        $cache_path = $this->folder_path . DIRECTORY_SEPARATOR . $key . self::FILE_EXTENSION;
        $handler = fopen($cache_path, 'w');
        fwrite($handler, serialize($value));
        fclose($handler);

        return $this;
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
     * @param  string $folder_path
     * @return ICache
     */
    public function set_folder_path($folder_path)
    {
        $folder_path = (string) $folder_path;

        // if the folder path doesn't exists, try to create it
        if (!is_dir($folder_path)) {
            if (@!mkdir($folder_path, 0755, true)) {
                Error::set(sprintf(self::ERROR_FOLDER_NOT_EXISTS, $folder_path));
            }

        }

        // make sure the path is writable
        if (!is_writeable($folder_path)) {
            Error::set(sprintf(self::ERROR_FOLDER_PERMISSION, $folder_path));
        }

        $this->folder_path = $folder_path;

        return $this;
    }

}
