<?php
/**
 * Kima L10n
 * @author Steve Vega
 */
namespace Kima;

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
     * @var array
     */
    protected static $strings;

    /**
     * The cache key
     * @var string
     */
    protected static $cache_key;

    /**
     * Gets the key wanted for translation
     * @param  string $key
     * @param  array  $args
     * @param  string $language
     * @return string
     */
    public static function t($key, array $args = [], $language = '', $module = null)
    {
        $app = Application::get_instance();
        // set the language
        $language = !empty($language) ? $language : $app->get_language();

        if (!isset($module)) {
            // get the module, controller and method from the application
            $module = $app->get_module();
        }

        // check if we do have the language strings loaded
        if (empty(self::$strings[$module][$language])) {
            $controller = strtolower($app->get_controller());
            $method = $app->get_method();

            // get the string path and sets the cache key
            $strings_path = self::get_strings_path($module, $language);
            self::set_cache_key($language, $module, $controller, $method);

            // get the strings from cache
            $strings = Cache::get_instance()->get_by_file(self::$cache_key, $strings_path);

            // get the language strings from the application l10n file if the cache was empty
            if (empty($strings)) {
                $strings = self::get_strings($controller, $method, $strings_path);
            }

            self::$strings[$module][$language] = $strings;
        }

        // sends the l10n key if exists
        return !empty(self::$strings[$module][$language][$key])
            ? vsprintf(self::$strings[$module][$language][$key], $args)
            : null;
    }

    /**
     * Gets the strings path for the current module/language
     * @param  string $module
     * @param  string $language
     * @return string
     */
    private static function get_strings_path($module, $language)
    {
        // get the module and config
        $app = Application::get_instance();

        // set the strings path
        $strings_path = $app->get_l10n_folder();

        // add the module and file name to the string path
        $strings_path .= !empty($module) ? $module . DIRECTORY_SEPARATOR : '';
        $strings_path .= $language . '.ini';

        // validate the string path
        if (!is_readable($strings_path)) {
            Error::set(sprintf(self::ERROR_INVALID_STRINGS_PATH, $strings_path));
        }

        // get the string data
        return $strings_path;
    }

    /**
     * Retrieves and parse the language strings from the l10n string file
     * Sets the strings on cache
     * @param  string $controller
     * @param  string $method
     * @param  string $strings_path
     * @return array
     */
    private static function get_strings($controller, $method, $strings_path)
    {
        $strings = [];

        // get the strings data
        $strings_data = parse_ini_file($strings_path, true);
        if ($strings_data) {
            // set the global, controller and method strings
            $global_strings = self::get_section_strings($strings_data, 'global');
            $controller_strings = self::get_section_strings($strings_data, $controller);
            $method_strings = self::get_section_strings($strings_data, $controller . '-'. $method);

            // merge the strings content
            $strings = array_merge($global_strings, $controller_strings, $method_strings);
        }

        // set the strings in cache
        Cache::get_instance()->set(self::$cache_key, $strings);

        // set the class strings for future references
        return $strings;
    }

    /**
     * Gets the strings data for a section
     * @param  array  $strings_data
     * @param  string $section
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
     * @param string $language
     * @param string $module
     * @param string $controller
     * @param string $method
     */
    private static function set_cache_key($language, $module, $controller, $method)
    {
        $cache_key = 'l10n_strings_' . $language;

        // add the module if exists
        if (!empty($module)) {
            $cache_key .= '_' . $module;
        }

        $controller = str_replace('/', '_', $controller);
        $cache_key .= '_' . $controller . '_' . $method;

        self::$cache_key = $cache_key;
    }
}
