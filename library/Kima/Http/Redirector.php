<?php
/**
 * Kima Http Redirector
 * @author Steve Vega
 */
namespace Kima\Http;

use \Kima\Http\Request;

/**
 * Redirector
 * HTTP Redirector library
 * @package Kima
 */
class Redirector
{

    /**
     * Redirects the user to another path
     * @param string $destination
     * @param int $status_code
     */
    public static function redirect($destination, $status_code = null)
    {
        // send the user to the desired location
        header('Location:' . (string)$destination, true, (int)$status_code);
        exit;
    }

    /**
     * Redirects the user to the http url
     */
    public static function http()
    {
        // get the host and url
        $host = Request::server('HTTP_HOST');
        $url = Request::server('REQUEST_URI');

        $http = 'http://' . $host . $url;
        header('Location: ' . $http);
        exit;
    }

    /**
     * Redirects the user to the secure url
     */
    public static function https()
    {
        // get the host and url
        $host = Request::server('HTTP_HOST');
        $url = Request::server('REQUEST_URI');

        $https = 'https://' . $host . $url;
        header('Location: ' . $https);
        exit;
    }

}