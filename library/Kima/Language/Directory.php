<?php
/**
 * Kima Language
 */
namespace Kima\Language;

use \Kima\Application;
use \Kima\Http\Request;

/**
 * Directory
 * Kima Directory class
 */
class Directory extends ALanguage implements ILanguage
{

    /**
     * Gets the corresponding URL for a desired language
     * @param  string $language
     * @param  string $url
     * @return string
     */
    public function get_language_url($language = null, $url = null)
    {
        // get the url formatted
        $language = $this->get_implicit_language($language);
        $url_parts = $this->get_url_parts($url);
        $url_path = $url_parts['path'];

        // get the required application values
        $app = Application::get_instance();
        $app_language = $app->get_language();
        $default_language = $app->get_default_language();

        // format the URL language
        if (!empty($app_language) &&
            (($default_language !== $app_language
                && Application::LANG_DEFAULT_IMPLICIT === $app->get_default_language_type())
            || Application::LANG_DEFAULT_EXPLICIT === $app->get_default_language_type()))
        {
            $url_path = explode('/', $url_path);

            // remove the language if necessary
            $url_language = isset($url_path[1]) ? $url_path[1] : null;
            if ($app->is_language_available($url_language)) {
                $url_path = array_slice($url_path, 2);
            }

            $url_path = implode('/', $url_path);
        }

        // add the language to the URL
        $url_parts['path'] = rtrim('/' . $language . '/' . $url_path, '/');

        // build the url
        return $this->build_url($url_parts);
    }

    /**
     * Gets the language for a directory language app type
     * @return string
     */
    public function get_app_language()
    {
        // get the possible language
        $path = parse_url(Request::server('REQUEST_URI'), PHP_URL_PATH);
        $path_values = array_values(array_filter(explode('/', $path)));
        $language = array_shift($path_values);

        $app = Application::get_instance();
        if (!$app->is_language_available($language)) {
            $language = null;
        }

        return $language;
    }

}
