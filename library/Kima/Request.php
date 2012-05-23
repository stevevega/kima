<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Request
 *
 * Request class
 * @package Kima
 */
class Request
{

    /**
     * Gets a request parameter
     * @param string $param
     * @param mixed $default
     */
    public static function get($param, $default=null)
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