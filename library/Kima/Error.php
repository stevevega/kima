<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * Error
 *
 * Handles errors among all the code
 * @package Kima
 */
class Error
{

    /**
     * Error class cannot be instanced directly
     * @access private
     */
    private function __contruct(){}

    /**
     * Sets an error
     * @access public
     * @static
     * @param string $caller
     * @param string $message
     * @param boolean $isFatalError
     */
    public static function set($caller, $message, $is_critical = true)
    {
        // set the error handler
        set_error_handler("self::error_handler");

        // create the error
        trigger_error($caller . ':' . $message, $is_critical ? E_USER_ERROR : E_USER_NOTICE);

        // restore the system error handler
        restore_error_handler();
    }

    /**
     * Handles user triggered errors
     * @static
     * @param int $level
     * @param string $message
     */
    private static function error_handler($level, $message)
    {
        switch ($level) {
            case E_USER_ERROR:
                echo 'Fatal Error [' . $level . ']: ' . $message. "\n";
                exit();
                break;

            case E_USER_WARNING:
                echo 'Warning [' . $level . ']: ' . $message. "\n";
                break;

            case E_USER_NOTICE:
                echo 'Notice [' . $level . ']: ' . $message. "\n";
                break;

            default:
                echo 'Unknown Error [' . $level . ']: ' . $message. "\n";
                break;
        }
        return;
    }

}