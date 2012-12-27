<?php
/**
 * Kima Config
 * @author Steve Vega
 */
namespace Kima;

use \Kima\Error;

/**
 * Config
 * Application config
 */
class Config
{

    /**
     * Error messages
     */
     const ERROR_NO_KEY = 'Config key "%s" doesn\'t exists';
     const ERROR_CONFIG_PATH = 'Cannot access config file on "%s"';

    /**
     * Config associative array
     * @var array
     */
    private $config = [];

    /**
     * Constructor
     * @param string $path
     */
    public function __construct($path)
    {
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
            : Error::set(sprintf(self::ERROR_NO_KEY, $key), false);

        return isset($value) ? $value : null;
    }

    /**
     * Parse the config file
     * @param string $path
     */
    private function parse_config($path)
    {
        // gets the enviroment
        $environment = getenv('ENVIRONMENT') ? getenv('ENVIRONMENT') : 'default';

        // parse using ini file if file exists
        is_readable($path)
            ? $config = parse_ini_file($path, true)
            : Error::set(sprintf(ERROR_CONFIG_PATH, $path));

        // merge the environment values with the default
        $config = 'default' !== $environment
            ? array_merge($config['default'], $config[$environment])
            : $config['default'];

        // create an associative array using the keys
        foreach ($config as $key => $value)
        {
            $this->config = array_merge_recursive($this->config, $this->parse_keys($key, $value));
        }
    }

    /**
     * Parse the keys on the config file
     * @param string $key
     * @param string $value
     * @return array
     */
    private function parse_keys($key, $value)
    {
        // search for a key separator
        if (false !== strpos($key, '.'))
        {
            // split the key in 2 parts and parse recursively the following key
            $keys = explode('.', $key, 2);
            return [$keys[0] => $this->parse_keys($keys[1], $value)];
        }
        else
        {
            // return the key value
            return [$key => $value];
        }
    }

}