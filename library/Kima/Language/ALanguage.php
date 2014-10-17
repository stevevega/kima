<?php
/**
 * Kima Language
 */
namespace Kima\Language;

use \Kima\Application;
use	\Kima\Http\Request;

/**
 * ALanguage
 * Kima Abstract Language class
 */
abstract class ALanguage
{

    /**
     * Gets the language for the app filtering implicit language
     * @param  string $language custom language
     * @return string
     */
    protected function get_implicit_language($language)
    {
        $app = Application::get_instance();

        // get the default language if the current one is invalid
        if (!$app->is_language_available($language)) {
            $language = $app->get_language(false);
        }

        return $language;
    }

    /**
     * Gets the URL used for the
     * @param string $url custom url
     */
    protected function get_url_parts($url)
    {
        // use the REQUEST_URI as default
        if (empty($url)) {
            $url = Request::get_request_url();
        }

        // get the url parts and format required values
        $url_parts = parse_url($url);
        $url_parts['path'] = isset($url_parts['path']) ? $url_parts['path'] : '/';

        return $url_parts;
    }

    /**
     * Builds an URL based on its parts
     * @param  array  $url_parts
     * @return string
     */
    protected function build_url(array $url_parts)
    {
        // remove unnecessary folder separators
        $url = preg_replace('/\/+/', '/', $url_parts['path']);

        // add the URL params
        $url_params = [];
        if (isset($url_parts['query'])) {
            parse_str($url_parts['query'], $url_params);
            $query_string = http_build_query($url_params);
            $url = $url . '?' . $query_string;
        }

        if (isset($url_parts['host'])) {
            $scheme = isset($url_parts['scheme']) ? $url_parts['scheme'] . '://' : '';
            $url = $scheme . $url_parts['host'] . '/' . ltrim($url, '/');
        }

        return $url;
    }

}
