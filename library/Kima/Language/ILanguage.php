<?php
/**
 * Kima Language Interface
 */
namespace Kima\Language;

/**
 * Interface for language library
 */
interface ILanguage
{
    /**
     * Gets the corresponding URL for a desired language
     * @param  string $language
     * @param  string $url
     * @return string
     */
    public function get_language_url($language = null, $url = null);

    /**
     * Gets the language valid for the current app
     * @return string
     */
    public function get_app_language();
}
