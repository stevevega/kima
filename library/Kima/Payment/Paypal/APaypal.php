<?php
/**
 * Namespace Kima
 */
namespace Kima\Payment\Paypal;

/**
 * Namespaces to use
 */
use \Kima\Error;

/**
 * Abstract Paypal
 *
 * Paypal Abstract class
 */
abstract class APaypal
{

    /**
     * Makes an API request
     * @see method documentation URL
     * @param array $params
     */
    abstract public function request($params);

    /**
     * API endpoints
     */
    const API_ENDPOINT = 'https://api-3t.paypal.com/nvp';
    const API_SANDBOX_ENDPOINT = 'https://api-3t.sandbox.paypal.com/nvp';

    /**
     * API Version
     * @see https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_PayPalAPIWhatsNewNVP
     */
    const API_VERSION = '89.0';

    /**
     * General Paypal properties
     */
    private $_use_sandbox = false;
    private $_api_username;
    private $_api_password;
    private $_api_signature;

    /**
     * Error properties
     */
    private $_last_error;

    /**
     * Constructor
     * @param array $credentials ['username', 'password', 'signature']
     * @param bool  $use_sandbox
     */
    public function __construct($credentials, $use_sandbox = false)
    {
        $this->set_credentials($credentials);

        if ($use_sandbox) {
            $this->use_sandbox();
        }
    }

    /**
     * Switch to Sandbox mode
     */
    public function use_sandbox()
    {
        $this->_use_sandbox = true;
    }

    /**
     * Returns the Paypal API Endpoint
     */
    public function get_api_endpoint()
    {
        return $this->_use_sandbox ? self::API_SANDBOX_ENDPOINT : self::API_ENDPOINT;
    }

    /**
     * Sets the Paypal user credentials
     * @param  array  $credentials ['username', 'password', 'signature']
     * @return Paypal
     */
    public function set_credentials($credentials)
    {
        if (isset($credentials['username'])) {
            $this->_api_username = $credentials['username'];
        }

        if (isset($credentials['password'])) {
            $this->_api_password = $credentials['password'];
        }

        if (isset($credentials['signature'])) {
            $this->_api_signature = $credentials['signature'];
        }

        return $this;
    }

    /**
     * Check for the required user credentials to exists
     * @return void
     */
    public function validate_credentials()
    {
        if (empty($this->_api_username)) {
            throw new Exception('Missing username credential needed for API call');
        }

        if (empty($this->_api_password)) {
            throw new Exception('Missing password credential needed for API call');
        }

        if (empty($this->_api_signature)) {
            throw new Exception('Missing signature credential needed for API call');
        }
    }

    /**
     * Returns the last error message
     * generated from an API request
     */
    public function get_last_error()
    {
        return $this->_last_error;
    }

    /**
     * Sets the last error message
     * generated from an API request
     * @param string $error_message
     */
    protected function _set_last_error($error_message)
    {
        $this->_last_error = (string) $error_message;
    }

    /**
     * Receives an http response string,
     * returns the reponse in a formated array
     * @param string http_response
     */
    private function _format_http_response($http_response)
    {
        $response = array();
        $response_fields = explode('&', $http_response);

        foreach ($response_fields as $i => $value) {
            $field = explode('=', $value);
            if (sizeof($field) > 1) {
                $response[urldecode($field[0])] = urldecode(strtoupper($field[1]));
            }
        }

        return $response;
    }

    /**
     * Formats and sets and error response send by Paypal
     */
    protected function set_error_response($response, $method)
    {
        $error_number = $response['L_ERRORCODE0'];
        $error_message = $response['L_SHORTMESSAGE0'];
        $error_description = $response['L_LONGMESSAGE0'];

        $this->_set_last_error($method . ' failed: ' .
            $error_message . ' - ' . $error_description . ' (' . $error_number . ')');
    }

    /**
     * Sends an API request
     * @param string $method
     * @param array  $params
     */
    public function api_request($method, $params)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->get_api_endpoint());
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        $api_fields = $this->_prepare_request_fields($params, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $api_fields);

        $http_response = curl_exec($ch);
        if (!$http_response) {
            $this->_set_last_error($method . ' failed: (' . curl_errno($ch) . ') ' . curl_error($ch));

            return false;
        }

        $response = $this->_format_http_response($http_response);
        if (empty($response) || !isset($response['ACK'])) {
            $this->_set_last_error('Invalid HTTP Response for POST request using ' . $api_fields);

            return false;
        }

        return $response;
    }

    /**
     * Prepare the params and adds the general paypal fields
     * for the API request
     * @param array  $params
     * @param string $method
     */
    private function _prepare_request_fields($params, $method)
    {
        $paypal_fields = array(
            'METHOD' => $method,
            'VERSION' => self::API_VERSION,
            'PWD' => $this->_api_password,
            'USER' => $this->_api_username,
            'SIGNATURE' => $this->_api_signature);

        $params = array_merge($paypal_fields, $params);

        return http_build_query($params);
    }

    /**
     * Validates a set of params to make sure
     * the required fields for an API request are there
     * @param array  $params
     * @param array  $required_fields
     * @param string $method
     */
    protected function _validate_required_fields($params, $required_fields, $method)
    {
        $this->validate_credentials();

        $missing_fields = array();
        foreach ($required_fields as $field) {
            if (!isset($params[$field]) || empty($params[$field])) {
                $missing_fields[] = $field;
            }
        }

        if (!empty($missing_fields)) {
            Error::set(__METHOD__,
                $method . ' failed: Missing required fields ' . implode(', ', $missing_fields));

            return false;
        }

        return true;
    }

}
