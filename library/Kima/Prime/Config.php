<?php
namespace Kima\Prime;

use \Kima\Error;

/**
 * Kima Config Handler
 * Handles the app configuration
 * The config has 3 layers:
 * - Global config: provided by the DEFAULT_CONFIG_PATH file
 * - Module config: provided by the DEFAULT_CONFIG_MODULE_FOLDER file
 * - Custom config: inyected by the app with a custom file. (useful for environment configs)
 */
class Config
{

    /**
     * Error messages
     */
    const ERROR_NO_KEY = 'Config key "%s" doesn\'t exists';
    const ERROR_CONFIG_PATH = 'Cannot access config file on "%s"';

    const DEFAULT_CONFIG_PATH = 'config/application.ini';
    const DEFAULT_CONFIG_MODULE_FOLDER = 'module/%s/config/application.ini';

    /**
     * Config associative array
     * @var array
     */
    private $config = [];

    /**
     * App instance
     * @var App
     */
    private $app;

    /**
     * Path of a custom config file that will be merge with the default config
     * @var string
     */
    private $custom_file;

    /**
     * Constructor
     * @param string $custom_file
     */
    public function __construct($custom_file = null)
    {
        $this->app = App::get_instance();
        $this->set_custom_file($custom_file)->parse_config();
    }

    /**
     * Magic function to get a config value
     * @param  string $key
     * @return string
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * Gets a config key
     * @param  string $key
     * @return string
     */
    public function get($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Sets a custom config value
     * @param  string $key
     * @param  mixed  $value
     * @return $this
     */
    public function set($key, $value)
    {
        $this->config = array_merge_recursive($this->config, $this->parse_keys($key, $value));

        return $this;
    }

    /**
     * Sets and validates the custom config file
     * @param  string $custom_file
     * @return Config
     */
    private function set_custom_file($custom_file)
    {
        if (isset($custom_file) && !is_readable($custom_file)) {
            Error::set(sprintf(self::ERROR_CONFIG_PATH, $custom_file));
        }

        $this->custom_file = $custom_file;

        return $this;
    }

    /**
     * Parse the global and module config file and parse the keys
     * @return Config
     */
    private function parse_config()
    {
        return $this->set_global_config()
            ->set_module_config()
            ->set_custom_config()
            ->set_config_keys();
    }

    /**
     * Sets the global config values
     * @return Config
     */
    private function set_global_config()
    {
        $path = $this->get_config_path();

        if (!is_readable($path)) {
            Error::set(sprintf(self::ERROR_CONFIG_PATH, $path));
        }

        $this->config = parse_ini_file($path);

        return $this;
    }

    /**
     * Sets custom config for a module
     * @return Config
     */
    private function set_module_config()
    {
        $module = $this->app->get_module();
        if (isset($module)) {
            $path = $this->get_config_path($module);

            if (is_readable($path)) {
                $this->add_custom_config($path);
            }
        }

        return $this;
    }

    /**
     * Sets custom config
     * @return Config
     */
    private function set_custom_config()
    {
        if (isset($this->custom_file)) {
            $this->add_custom_config($this->custom_file);
        }

        return $this;
    }

    /**
     * Set the config keys as an associate array using keys
     * @return $this
     */
    private function set_config_keys()
    {
        $config = [];

        // create an associative array using the keys
        foreach ($this->config as $key => $value) {
            $config = array_merge_recursive($config, $this->parse_keys($key, $value));
        }

        $this->config = $config;

        return $this;
    }

    /**
     * Gets the path to a configuration file
     * @param  string $module
     * @return string
     */
    private function get_config_path($module = null)
    {
        return $this->app->get_application_folder() .
            (isset($module)
                ? sprintf(self::DEFAULT_CONFIG_MODULE_FOLDER, $module)
                : self::DEFAULT_CONFIG_PATH);
    }

    /**
     * Merges a custom config from a ini file with the current config
     * @param  string $file
     * @return Config
     */
    private function add_custom_config($file)
    {
        $this->config = array_merge($this->config, parse_ini_file($file));

        return $this;
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
        }

        return [$key => $value];
    }

}
