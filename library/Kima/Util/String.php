<?php
/**
 * Kima Util String
 * @author Steve Vega
 */
namespace Kima\Util;

/**
 * A class with a number of string utilities static methods
 */
class String
{

    /**
     * Transforms camel case to underscore
     * @param string
     * @return string
     */
    public static function camel_case_to_underscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', (string) $string));
    }

    /**
     * Converts a string into a slug for url
     * @param  string $string the string to convert
     * @return string
     */
    public static function to_slug($string)
    {
        // convert non ascii characters to its equivalent
        setlocale(LC_CTYPE, 'en_US.utf8');
        $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);

        // remove all non alphanumeric characters
        $string = preg_replace('/[^a-z0-9-]+/i', '-', $string);

        // remove extra hyphens
        $string = trim(preg_replace('/-+/', '-', $string), '-');

        // return lowercase string
        return strtolower($string);
    }

}
