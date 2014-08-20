<?php
namespace Kima\Google;
use Kima\Error;

/**
 * Url Shortener class
 *
 * Handles url shortener Google services
 *
 * @category Google
 * @package Kima
 */
class UrlShortener
{

    /**
     * Shortener endpoints
     * @var string
     */
    const GOOGLE_ENDPOINT = 'https://www.googleapis.com/urlshortener/v1/url';

    /**
     * The shortener service API key
     * @var string
     */
    private $_api_key;

    /**
     * construct method
     *
     * @param array the options to set
     */
    public function __construct($options = array())
    {
        if (isset($options['api']) && isset($options['key'])) {
            $this->_apiKey = $options['key'];
        }
    }

    /**
     * Shorten URL
     *
     * @param string the api key
     * @return string shortened url
     */
    public function shorten($url)
    {
        // set the URL
        $endpoint = self::GOOGLE_ENDPOINT . (isset($this->_api_key) ? '?key=' . $this->_api_key : '');

        // initialize the curl connection
        $ch = curl_init($endpoint);

        // curl options
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(array('longUrl' => $url)));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // perform the request
        if (!$result = curl_exec($ch)) {
            Error::set(__METHOD__, ' Couldn\'t connect with shorten service', false);

            return null;
        }

        // close curl connection
        curl_close($ch);

        // get the result
        $result = json_decode($result, true);

        // error?
        if (isset($result['error'])) {
            Error::set(__METHOD__, ' Error on the shorten request ' .
                $result['error']['errors']['locationType'] . ' ' .
                $result['error']['errors']['location'] . ' ' .
                $result['error']['errors']['message'], false);

            return null;
        }

        // decode and return the JSON response
        return isset($result['id']) ? $result['id'] : null;
    }

}
