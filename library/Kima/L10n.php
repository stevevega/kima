<?php
/**
 * Kima L10n
 *
 * @author Steve Vega
 */
namespace Kima;

use Kima\Prime\App;

/**
 * Kima Localization library
 */
class L10n
{
    /**
     * Error messages
     */
    const ERROR_INVALID_STRINGS_PATH = 'Cannot access strings path "%s"';

    /**
     * The strings used on this action
     *
     * @var array
     */
    protected static $strings;

    /**
     * The cache key
     *
     * @var string
     */
    protected static $cache_key;

    /**
     * The prefix of the cache key
     *
     * @var string
     */
    protected static $cache_key_prefix;

    /**
     * Array with the paths of the l10n resources.
     *
     * @var array
     */
    protected static $l10n_paths = [];

    /**
     * Flag that indicates if store the keys
     *
     * @var bool
     */
    protected static $store_keys = true;

    /**
     * Sets the value of store_keys
     *
     * @param bool $store_keys
     */
    public static function set_store_keys($store_keys)
    {
        self::$store_keys = (bool) $store_keys;
    }

    /**
     * Sets the value of l10n_paths
     *
     * @param array $paths
     */
    public static function set_l10n_paths(array $paths)
    {
        self::$l10n_paths = $paths;
    }

    /**
     * Sets the value of cache key prefix
     *
     * @param string $prefix
     */
    public static function set_cache_key_prefix($prefix)
    {
        self::$cache_key_prefix = (string) $prefix;
    }

    /**
     * Gets the key wanted for translation
     *
     * @param string $key
     * @param array  $args
     * @param string $language
     *
     * @return string
     */
    public static function t($key, array $args = [], $language = '')
    {
        $app = App::get_instance();

        // set the language
        $language = !empty($language) ? $language : $app->get_language();

        // check if we do have the language strings loaded
        if (empty(self::$strings[$language]) || !self::$store_keys) {
            $app_controller = $app->get_controller() ?? '';
            $controller = strtolower($app_controller);
            $method = $app->get_method();

            // get the string path and sets the cache key
            $strings_paths = self::get_strings_paths($language);

            self::set_cache_key($language, $controller, $method);

            $strings = [];

            // validate whether there is any change in the files after of stored in cache.
            if (self::is_valid_strings_paths_timestamp($strings_paths) && self::$store_keys) {
                // get the strings from cache
                $strings = Cache::get_instance()->get(self::$cache_key);
            }

            // get the language strings from the application l10n file if the cache was empty
            // or some file was modified
            if (empty($strings)) {
                $strings = self::get_strings($controller, $method, $strings_paths);
            }

            self::$strings[$language] = $strings;
        }

        // sends the l10n key if exists
        return !empty(self::$strings[$language][$key])
            ? vsprintf(self::$strings[$language][$key], $args)
            : null;
    }

    /**
     * Validates whether there is any change in data of the files stored cached.
     * Using the timestamp of the file and cache.
     *
     * @param array $strings_paths
     *
     * @return bool
     */
    private static function is_valid_strings_paths_timestamp(array $strings_paths)
    {
        $cache_timestamp = Cache::get_instance()->get_timestamp(self::$cache_key);

        foreach ($strings_paths as $strings_path) {
            if (filemtime($strings_path) > $cache_timestamp) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets the strings paths for the current language
     *
     * @param string $language
     *
     * @return array
     */
    private static function get_strings_paths($language)
    {
        // set the strings paths using if exists the array of l10n paths else the default application path
        $strings_paths = empty(self::$l10n_paths)
            ? [App::get_instance()->get_l10n_folder()]
            : self::$l10n_paths;

        foreach ($strings_paths as &$strings_path) {
            // add file name to the string path
            $strings_path .= $language . '.ini';

            // validate the string path
            if (!is_readable($strings_path)) {
                Error::set(sprintf(self::ERROR_INVALID_STRINGS_PATH, $strings_path));
            }
        }

        return $strings_paths;
    }

    /**
     * Retrieves and parse the language strings from the l10n string files
     * Sets the strings on cache
     *
     * @param string $controller
     * @param string $method
     * @param array  $strings_paths
     *
     * @return array
     */
    private static function get_strings($controller, $method, array $strings_paths)
    {
        $global_strings = [];
        $controller_strings = [];
        $method_strings = [];

        foreach ($strings_paths as $strings_path) {
            // get the strings data
            $strings_data = parse_ini_file($strings_path, true);

            if ($strings_data) {
                // set the global, controller and method strings
                $global_strings = array_merge(
                    $global_strings,
                    self::get_section_strings($strings_data, 'global')
                );

                $controller_strings = array_merge(
                    $controller_strings,
                    self::get_section_strings($strings_data, $controller)
                );

                $method_strings = array_merge(
                    $method_strings,
                    self::get_section_strings($strings_data, $controller . '-' . $method)
                );
            }
        }
        // merge the strings content
        $strings = array_merge($global_strings, $controller_strings, $method_strings);

        // set the strings in cache
        Cache::get_instance()->set(self::$cache_key, $strings);

        return $strings;
    }

    /**
     * Gets the strings data for a section
     *
     * @param array  $strings_data
     * @param string $section
     *
     * @return array
     */
    private static function get_section_strings(array $strings_data, $section)
    {
        $strings = array_key_exists($section, $strings_data)
                ? $strings_data[$section]
                : [];

        return $strings;
    }

    /**
     * Sets the cache key for the strings
     *
     * @param string $language
     * @param string $controller
     * @param string $method
     */
    private static function set_cache_key($language, $controller, $method)
    {
        $cache_key = self::$cache_key_prefix . 'l10n_strings_' . $language;

        $controller = str_replace('/', '_', $controller);
        $cache_key .= '_' . $controller . '_' . $method;

        self::$cache_key = $cache_key;
    }
}
