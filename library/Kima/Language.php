<?php
/**
 * Kima Language
 */
namespace Kima;

use	\Kima\Error;
use \Kima\Prime\App;
use	\Kima\Language\Directory;
use	\Kima\Language\QueryString;
use	\Kima\Language\Subdomain;
use	\Kima\Language\ILanguage;

/**
 * Language
 * Kima Language class
 */
class Language
{
    /**
     * Language source handler
     */
    public static $lang_source = null;

    /**
     * Error messages
     */
    const ERROR_NO_LANGUAGE_TYPE = 'No language type has been defined';
    const ERROR_INVALID_LANG_SOURCE = 'Invalid application language source "%s"';
    const ERROR_INVALID_LANG_HANDLER = 'Invalid application language source handler "%s"';

    /**
     * Application language type
     */
    const LANG_SOURCE_DIRECTORY = 'directory';
    const LANG_SOURCE_SUBDOMAIN = 'subdomain';
    const LANG_SOURCE_QUERY_STRING = 'query_string';

    /**
     * Gets the language type object the app should use
     * Handler is only set once from kima action
     * @return mixed
     */
    public static function get_instance($handler = null, $handler_params = [])
    {
        // check if a language source instance was previously set
        if (!is_null(self::$lang_source)) {
            return self::$lang_source;
        }

        // create language source from provided handler
        if (is_string($handler) && class_exists($handler, true) ) {
            $lang_source = new $handler($handler_params);
            if (!($lang_source instanceof ILanguage)) {
                $lang_source = null;
                Error::set(sprintf(self::ERROR_INVALID_LANG_HANDLER, $handler));
            }
        }

        // create language source from config
        if (!isset($lang_source) || is_null($lang_source)) {
            // get the app config
            $config = App::get_instance()->get_config();

            // validate the type
            if (!isset($config->language['source'])) {
                Error::set(self::ERROR_NO_LANGUAGE_TYPE);
            }

            // get the object depending on the language type
            switch ($config->language['source']) {
                case self::LANG_SOURCE_DIRECTORY :
                    $lang_source = new Directory();
                    break;

                case self::LANG_SOURCE_QUERY_STRING :
                    $lang_source = new QueryString();
                    break;

                case self::LANG_SOURCE_SUBDOMAIN :
                    $lang_source = new SubDomain();
                    break;

                default :
                    Error::set(sprintf(self::ERROR_INVALID_LANG_SOURCE, $config->language['source']));
                    break;
            }
        }

        // language source is always the same through the whole request
        // if a different source is required, just set it through url/routes
        self::$lang_source = $lang_source;

        return self::$lang_source;
    }

}
