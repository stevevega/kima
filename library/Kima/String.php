<?php
/**
 * Namespace Kima
 */
namespace Kima;

/**
 * String
 *
 * A class with a number of string utilities static methods
 * @package Kima
 */
class String
{

    /**
     * Transforms camel case to underscore
     */
    public static function camel_case_to_underscore($string) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $string));
    }

}