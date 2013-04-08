<?php
/**
 * Kima Http Request
 * @author Steve Vega
 */
namespace Kima\Http;

/**
 * Request
 * HTTP Request handler class
 * @package Kima
 */
class Request
{

    /**
     * Request GET variabless
     * @param string $param
     * @param mixed $default
     */
    public static function get($param, $default = null)
    {
        return isset($_GET[$param]) ? $_GET[$param] : $default;
    }

    /**
     * Request POST variabless
     * @param string $param
     * @param mixed $default
     */
    public static function post($param, $default = null)
    {
        return isset($_POST[$param]) ? $_POST[$param] : $default;
    }

    /**
     * Request COOKIE variabless
     * @param string $param
     * @param mixed $default
     */
    public static function cookie($param, $default = null)
    {
        return isset($_COOKIE[$param]) ? $_COOKIE[$param] : $default;
    }

    /**
     * Request SERVER variabless
     * @param string $param
     * @param mixed $default
     */
    public static function server($param, $default = null)
    {
        return isset($_SERVER[$param]) ? $_SERVER[$param] : $default;
    }

    /**
     * Request ENV variabless
     * @param string $param
     * @param mixed $default
     */
    public static function env($param, $default = null)
    {
        return isset($_ENV[$param]) ? $_ENV[$param] : $default;
    }

    /**
     * Request ENV variabless
     * @param string $param
     * @param mixed $default
     * @param string $namespace
     */
    public static function session($param, $default = null, $namespace = '')
    {
        if (empty($namespace))
        {
            return isset($_SESSION[$param]) ? $_SESSION[$param] : $default;
        }
        else
        {
            return isset($_SESSION[$namespace][$param]) ? $_SESSION[$namespace][$param] : $default;
        }
    }

    /**
     * Gets a request parameter from different sources
     * @param string $param
     * @param mixed $default
     * @param string $namespace Only affects session variables
     */
    public static function get_all($param, $default = null, $namespace = '')
    {
        // ask for the parameter
        switch (true)
        {
            // GET param
            case isset($_GET[$param]):
                return $_GET[$param];
            // POST param
            case isset($_POST[$param]):
                return $_POST[$param];
            // COOKIE param
            case isset($_COOKIE[$param]):
                return $_COOKIE[$param];
            // SERVER param
            case isset($_SERVER[$param]):
                return $_SERVER[$param];
            // ENV param
            case isset($_ENV[$param]):
                return $_ENV[$param];
            // SESSION param
            case isset($_SESSION[$param]):
                return empty($namespace) ? $_SESSION[$param] : $_SESSION[$namespace][$param];
            // send the default value if nothing found
            default:
                return $default;
        }
    }

    /**
     * Gets the requested method to the server
     * @return string
     */
    public static function get_method()
    {
        return $_SERVER['REQUEST_METHOD'];
    }

}