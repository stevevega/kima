<?php
/**
 * Namespaces to use
 */
use \Kima\Controller,
    \Kima\Http\Request,
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
        $status_code = Request::get('status_code');
        $status_message = StatusCode::get_message($status_code);
        echo 'Error ' . $status_code . ': ' . $status_message;
    }

}