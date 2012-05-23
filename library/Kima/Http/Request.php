<?php
/**
 * Namespace Kima
 */
namespace Kima\Http;

/**
 * Request
 *
 * Request class
 * @package Kima
 */
class Request
{

    /**
     * Request GET variabless
     * @param string $param
     * @param mixed $default
     */
    public static function get($param, $default=null)
    {
        return isset($_GET[$param]) ? $_GET[$param] : $default;
    }

    /**
     * Request POST variabless
     * @param string $param
     * @param mixed $default
     */
    public static function post($param, $default=null)
    {
        return isset($_POST[$param]) ? $_POST[$param] : $default;
    }

    /**
     * Request COOCKIE variabless
     * @param string $param
     * @param mixed $default
     */
    public static function coockie($param, $default=null)
    {
        return isset($_COOKIE[$param]) ? $_COOKIE[$param] : $default;
    }

    /**
     * Request SERVER variabless
     * @param string $param
     * @param mixed $default
     */
    public static function server($param, $default=null)
    {
        return isset($_SERVER[$param]) ? $_SERVER[$param] : $default;
    }

    /**
     * Request ENV variabless
     * @param string $param
     * @param mixed $default
     */
    public static function env($param, $default=null)
    {
        return isset($_ENV[$param]) ? $_ENV[$param] : $default;
    }

    /**
     * Gets a request parameter from different sources
     * @param string $param
     * @param mixed $default
     */
    public static function getAll($param, $default=null)
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
            // COOCKIE param
            case isset($_COOKIE[$param]):
                return $_COOKIE[$param];
            // SERVER param
            case isset($_SERVER[$param]):
                return $_SERVER[$param];
            // ENV param
            case isset($_ENV[$param]):
                return $_ENV[$param];
            // send the default value if nothing found
            default:
                return $default;
        }
    }

}