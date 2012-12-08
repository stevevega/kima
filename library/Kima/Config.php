<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Namespaces to use
 */
use \Kima\Error;

/**
 * Config
 *
 * Framework Config
 * @package Kima
 */
class Config
{

    /**
     * Config associative array
     * @access private
     * @var array
     */
    private $_config = array();

    /**
     * Constructor
     * @access public
     * @param string $path
     */
    public function __construct($path)
    {
        $this->_parse_config($path);
    }

    /**
     * Magic function to get a config value
     * @param string $value
     */
    public function __get($key)
    {
        // set the key value if exists
        isset($this->_config[$key])
            ? $value = $this->_config[$key]
            : Error::set(__METHOD__, 'Config key ' . $key . ' doesn\'t exists', false);

        return isset($value) ? $value : null;
    }

    /**
     * Parse the config file
     * @param string $path
     */
    private function _parse_config($path)
    {
        // gets the enviroment
        $environment = getenv('ENVIRONMENT') ? getenv('ENVIRONMENT') : 'default';

        // parse using ini file if file exists
        is_readable($path)
            ? $config = parse_ini_file($path, true)
            : Error::set(__METHOD__, ' Cannot access config file on ' . $path);

        // merge the environment values with the default
        $config = $environment!='default'
            ? array_merge($config['default'], $config[$environment])
            : $config['default'];

        // create an associative array using the keys
        foreach ($config as $key => $value) {
            $this->_config = array_merge_recursive($this->_config, $this->_parse_keys($key, $value));
        }
    }

    /**
     * Parse the keys on the config file
     * @param string $key
     * @param string $value
     */
    private function _parse_keys($key, $value)
    {
        // search for a key separator
        if (strpos($key, '.')!==false)
        {
            // split the key in 2 parts and parse recursively the following key
            $keys = explode('.', $key, 2);
            return array($keys[0] => $this->_parse_keys($keys[1], $value));
        }
        else
        {
            // return the key value
            return array($key => $value);
        }
    }

}