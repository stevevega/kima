<?php
/**
 * Kima Error
 * @author Steve Vega
 */
namespace Kima;

use \Exception,
    \Kima\Application,
    \Kima\Logger;

/**
 * Handles user trigger errors in the application
 */
class Error
{

    /**
     * Error message format
     */
     const ERROR_FORMAT =
        '<h1>Kima %s: %s</h1>
        <div>Trigger by <strong>%s</strong> function <strong>%s</strong></div>
        <div>File: <strong>%s.</strong></div>
        <div>Args: %s.</div>';

    /**
     * Error levels
     */
    const ERROR = E_USER_ERROR;
    const WARNING = E_USER_WARNING;
    const NOTICE = E_USER_NOTICE;

    /**
     * Error levels list
     */
    private static $error_levels = [
        self::ERROR => Logger::ERROR,
        self::WARNING => Logger::WARNING,
        self::NOTICE => Logger::NOTICE];

    /**
     * Error class cannot be instanced directly
     */
    private function __contruct(){}

    /**
     * Sets an application error
     * @param string $message the error message
     * @param boolean $is_critical whether is a critical error or not
     */
    public static function set($message, $level = null)
    {
        // sets the error handler
        set_error_handler("self::error_handler");

        // make sure the error level is valid
        $error_level = array_key_exists($level, self::$error_levels) ? $level : self::ERROR;
        $error_level_name = self::$error_levels[$error_level];

        // get the error caller
        $error_caller = self::get_error_caller();

        // sets the error message
        $error_message = sprintf(self::ERROR_FORMAT,
            $error_level_name,
            (string)$message,
            $error_caller['class'],
            $error_caller['function'],
            $error_caller['file'],
            print_r($error_caller['args'], true));

        $config = Application::get_config();
        if (isset($config->database) && !empty($config->database['mongo']['host']))
        {
            Logger::log($error_message, 'error', $error_level_name);
        }

        // send the error
        trigger_error($error_message, $error_level);

        // restore the system error handler
        restore_error_handler();
    }

    /**
     * Gets the error caller
     * @return array
     */
    private static function get_error_caller()
    {
        $e = new Exception();
        $trace = $e->getTrace();
        return $trace[2];
    }

    /**
     * Handles user triggered errors
     * @param int $level the error level
     * @param string $message the error message
     */
    private static function error_handler($level, $message)
    {
        switch ($level) {
            case E_USER_ERROR:
                echo $message;
                exit();

            default:
                echo $message;
                break;
        }
        return;
    }

}