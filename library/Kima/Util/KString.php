<?php
/**
 * Kima Util String
 *
 * @author Steve Vega
 */
namespace Kima\Util;

/**
 * A class with a number of string utilities static methods
 */
class KString
{
    /**
     * Transforms camel case to underscore
     *
     * @param string
     * @param mixed $string
     *
     * @return string
     */
    public function camel_case_to_underscore($string)
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', (string) $string));
    }

    /**
     * Converts a string into a slug for url
     *
     * @param string $string the string to convert
     *
     * @return string
     */
    public function to_slug($string)
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

    /**
     * Builds a comma separated list with an special separator for the last item
     *
     * @param array  $data
     * @param string $last_item_separator a separator to use with grammatical meaning
     *
     * @return string
     */
    public function build_comma_list($data, $last_item_separator = ' and ')
    {
        $count = count($data);
        $list = [];

        if ($count > 1) {
            for ($i = 0; $i < $count; $i++) {
                array_unshift($list, array_pop($data));
                if ($i === 0) {
                    array_unshift($list, $last_item_separator);
                } elseif (isset($data[0])) {
                    array_unshift($list, ', ');
                }
            }
        } else {
            $list = $data;
        }

        return implode($list);
    }

    /**
     * Get a random string from a determinate size
     *
     * @param int $size
     *
     * @return string random string of the value size
     */
    public function rand($size)
    {
        // Convert the number to negative
        // The idea is to get the rand string backwards so the uniqueness is better
        $size *= -1;

        // Adding  additional entropy
        return substr(uniqid('', true), $size);
    }
}
