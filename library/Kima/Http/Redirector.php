<?php
/**
 * Kima Http Redirector
 * @author Steve Vega
 */
namespace Kima\Http;

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

}