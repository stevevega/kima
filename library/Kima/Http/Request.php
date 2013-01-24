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
        return !empty($_GET[$param]) ? $_GET[$param] : $default;
    }

    /**
     * Request POST variabless
     * @param string $param
     * @param mixed $default
     */
    public static function post($param, $default = null)
    {
        return !empty($_POST[$param]) ? $_POST[$param] : $default;
    }

    /**
     * Request COOKIE variabless
     * @param string $param
     * @param mixed $default
     */
    public static function cookie($param, $default = null)
    {
        return !empty($_COOKIE[$param]) ? $_COOKIE[$param] : $default;
    }

    /**
     * Request SERVER variabless
     * @param string $param
     * @param mixed $default
     */
    public static function server($param, $default = null)
    {
        return !empty($_SERVER[$param]) ? $_SERVER[$param] : $default;
    }

    /**
     * Request ENV variabless
     * @param string $param
     * @param mixed $default
     */
    public static function env($param, $default = null)
    {
        return !empty($_ENV[$param]) ? $_ENV[$param] : $default;
    }

    /**
     * Gets a request parameter from different sources
     * @param string $param
     * @param mixed $default
     */
    public static function get_all($param, $default = null)
    {
        // ask for the parameter
        switch (true)
        {
            // GET param
            case !empty($_GET[$param]):
                return $_GET[$param];
            // POST param
            case !empty($_POST[$param]):
                return $_POST[$param];
            // COOKIE param
            case !empty($_COOKIE[$param]):
                return $_COOKIE[$param];
            // SERVER param
            case !empty($_SERVER[$param]):
                return $_SERVER[$param];
            // ENV param
            case !empty($_ENV[$param]):
                return $_ENV[$param];
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