<?php
/**
 * Kima Language
 */
namespace Kima;

use	\Kima\Error;
use	\Kima\Language\Directory;
use	\Kima\Language\QueryString;
use	\Kima\Language\Subdomain;

/**
 * Language
 * Kima Language class
 */
class Language
{

    /**
     * Error messages
     */
    const ERROR_NO_LANGUAGE_TYPE = 'No language type has been defined';
    const ERROR_INVALID_LANG_SOURCE = 'Invalid application language source "%s"';

    /**
     * Application language type
     */
    const LANG_SOURCE_DIRECTORY = 'directory';
    const LANG_SOURCE_SUBDOMAIN = 'subdomain';
    const LANG_SOURCE_QUERY_STRING = 'query_string';

    /**
     * Gets the language type object the app should use
     * @return mixed
     */
    public static function get_instance()
    {
        // get the app config
        $config = Application::get_instance()->get_config();

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

        return $lang_source;
    }

}
