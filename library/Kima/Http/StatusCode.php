<?php
/**
 * Kima Status Code
 * @author Steve Vega
 */
namespace Kima\Http;

use Kima\Error;

/**
 * Status Code
 * Handles the status codes
 */
class StatusCode
{

    /**
     * Error messages
     */
    const ERROR_UNKNOWN_STATUS_CODE = 'Unknown status code "%d"';

    /**
     * Status codes messages
     * @var array $status_code_messages
     */
    public static $status_code_messages = [
        // Informational
        100 => 'Continue',
        101 => 'Switching Protocols',

        // Success
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',

        // Redirection
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Moved Temporarily',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',

        // Client Error
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',

        // Server Error
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        509 => 'Bandwidth Limit Exceeded',
    ];

    /**
     * Get the message of a status code
     * @param  string $code The status code
     * @return string
     */
    public static function get_message($code)
    {
        $code = (int) $code;
        if (array_key_exists($code, self::$status_code_messages)) {
            $message = self::$status_code_messages[$code];
        } else {
            $message = sprintf(self::ERROR_UNKNOWN_STATUS_CODE, $code);
            Error::set($message, false);
        }

        return $message;
    }

}
