<?php
/**
 * Kima Util Int
 * @author Steve Vega
 */
namespace Kima\Util;

/**
 * A class with a number of integer utilities static methods
 */
class Int
{

    /**
     * Gets the integer value if is numeric, otherwise returns null
     * @param  mixed    $value
     * @return int|null
     */
    public static function cast($value)
    {
        $value = str_replace(['+', '-'], '', filter_var($value, FILTER_SANITIZE_NUMBER_INT));

        return is_numeric($value) ? (int) $value : null;
    }

}
