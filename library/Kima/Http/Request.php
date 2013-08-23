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
        $value = isset($_GET[$param]) ? $_GET[$param] : null;
        return self::clean_value($value, $default);
    }

    /**
     * Request POST variabless
     * @param string $param
     * @param mixed $default
     */
    public static function post($param, $default = null)
    {
        $value = isset($_POST[$param]) ? $_POST[$param] : null;
        return self::clean_value($value, $default);
    }

    /**
     * Request COOKIE variabless
     * @param string $param
     * @param mixed $default
     */
    public static function cookie($param, $default = null)
    {
        $value = isset($_COOKIE[$param]) ? $_COOKIE[$param] : null;
        return self::clean_value($value, $default);
    }

    /**
     * Request SERVER variabless
     * @param string $param
     * @param mixed $default
     */
    public static function server($param, $default = null)
    {
        $value = isset($_SERVER[$param]) ? $_SERVER[$param] : null;
        return self::clean_value($value, $default);
    }

    /**
     * Request ENV variabless
     * @param string $param
     * @param mixed $default
     */
    public static function env($param, $default = null)
    {
        $value = isset($_ENV[$param]) ? $_ENV[$param] : null;
        return self::clean_value($value, $default);
    }

    /**
     * Request ENV variabless
     * @param string $param
     * @param mixed $default
     * @param string $namespace
     */
    public static function session($param, $default = null, $namespace = null)
    {
        $session_var = isset($namespace) && isset($_SESSION[$namespace])
            ? $_SESSION[$namespace]
            : $_SESSION;

        $value = isset($session_var[$param]) ? $session_var[$param] : null;

        return self::clean_value($value, $default);
    }

    /**
     * Gets a request parameter from different sources
     * @param string $param
     * @param mixed $default
     * @param string $namespace Only affects session variables
     */
    public static function get_all($param, $default = null, $namespace = null)
    {
        // ask for the parameter
        switch (true)
        {
            // GET param
            case isset($_GET[$param]):
                return self::get($param, $default);
            // POST param
            case isset($_POST[$param]):
                return self::post($param, $default);
            // COOKIE param
            case isset($_COOKIE[$param]):
                return self::cookie($param, $default);
            // SERVER param
            case isset($_SERVER[$param]):
                return self::cookie($param, $default);
            // ENV param
            case isset($_ENV[$param]):
                return self::cookie($param, $default);
            // SESSION param
            case isset($_SESSION[$param]):
                return self::session($param, $default, $namespace);
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

    /**
     * Cleans a http value
     * @param  string $value
     * @param  mixed $default
     * @return mixed
     */
    private static function clean_value($value, $default)
    {
        if (isset($value))
        {
            $value = is_array($value) ? array_map('trim', $value) : trim($value);
        }
        else
        {
            $value = $default;
        }

        return $value;
    }

}