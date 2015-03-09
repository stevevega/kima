<?php
/**
 * Kima Config
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Prime\App;

/**
 * Config
 * Application config
 */
class Config
{

    /**
     * Error messages
     */
    const ERROR_NO_ENVIRONMENT = 'Environment "%s" not found in application ini';
    const ERROR_NO_KEY = 'Config key "%s" doesn\'t exists';
    const ERROR_CONFIG_PATH = 'Cannot access config file on "%s"';

    const DEFAULT_CONFIG_FOLDER = 'config/';
    const DEFAULT_CONFIG_MODULE_FOLDER = 'module/%s/config/';
    const DEFAULT_CONFIG_FILE = 'application.ini';

    /**
     * Config associative array
     * @var array
     */
    private $config = [];

    /**
     * Constructor
     * @param string $path
     */
    public function __construct($path = '')
    {
        $app_folder = App::get_instance()->get_application_folder();
        $path = !empty($path)
            ? $path
            : $app_folder . self::DEFAULT_CONFIG_FOLDER . self::DEFAULT_CONFIG_FILE;
        $this->parse_config($path);
    }

    /**
     * Magic function to get a config value
     * @param string $value
     */
    public function __get($key)
    {
        // set the key value if exists
        isset($this->config[$key])
            ? $value = $this->config[$key]
            : Error::set(sprintf(self::ERROR_NO_KEY, $key));

        return isset($value) ? $value : null;
    }

    /**
     * Parse the config file
     * @param string $path
     */
    private function parse_config($path)
    {
        // gets the enviroment
        $environment = App::get_instance()->get_environment();

        // parse using ini file if file exists
        is_readable($path)
            ? $config = parse_ini_file($path, true)
            : Error::set(sprintf(self::ERROR_CONFIG_PATH, $path));

        // get the merged configuration
        $config = $this->get_environment_config($config, $environment);
        $module_config = $this->get_module_config($environment);

        // merge the module config (if exists) with the main config
        if (!empty($module_config)) {
            $config = array_merge($config, $module_config);
        }

        // create an associative array using the keys
        foreach ($config as $key => $value) {
            $this->config = array_merge_recursive($this->config, $this->parse_keys($key, $value));
        }
    }

    /**
     * Make sure the require environments are setup
     * @param  array  $config
     * @param  string $environment
     * @return array
     */
    private function get_environment_config(array $config, $environment)
    {
        // check if default environment exists
        if (!isset($config['default'])) {
            Error::set(sprintf(self::ERROR_NO_ENVIRONMENT, 'default'));
        }

        // check if custom environment exists
        if (!isset($config[$environment])) {
            Error::set(sprintf(self::ERROR_NO_ENVIRONMENT, $environment));
        }

        // merge the environment values with the default
        $config = 'default' !== $environment
            ? array_merge($config['default'], $config[$environment])
            : $config['default'];

        return $config;
    }

    /**
     * Gets the module config
     * @param  string $environment
     * @return array
     */
    private function get_module_config($environment)
    {
        // if there is a module, lets get the custom config also
        $app = App::get_instance();
        $module = $app->get_module();
        if (!empty($module)) {
            $app_path = $app->get_application_folder();
            $path =  $app_path
                . sprintf(self::DEFAULT_CONFIG_MODULE_FOLDER, $module)
                . self::DEFAULT_CONFIG_FILE;

            // parse using ini file if file exists
            if (is_readable($path)) {
                $config = parse_ini_file($path, true);

                return $this->get_environment_config($config, $environment);
            }
        }

        return [];
    }

    /**
     * Parse the keys on the config file
     * @param  string $key
     * @param  string $value
     * @return array
     */
    private function parse_keys($key, $value)
    {
        // search for a key separator
        if (false !== strpos($key, '.')) {
            // split the key in 2 parts and parse recursively the following key
            $keys = explode('.', $key, 2);

            return [$keys[0] => $this->parse_keys($keys[1], $value)];
        } else {
            // return the key value
            return [$key => $value];
        }
    }

}
