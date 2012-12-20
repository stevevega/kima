<?php
/**
 * Namespaces to use
 */
use \Kima\Controller,
    \Kima\Http\StatusCode;

/**
 * Error
 */
class Error extends Controller
{

    /**
     * index
     */
    public function get()
    {
        $status_code = http_response_code();
        $status_message = StatusCode::get_message($status_code);
        echo 'Error ' . $status_code . ': ' . $status_message;
    }

}