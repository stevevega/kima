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
     * @param  mixed $value
     * @return  int|null
     */
    public static function cast($value, $strict = true)
    {
        if ($strict)
        {
            $value = is_numeric($value) ? (int)$value : null;
        }
        else
        {
            $value = (int)preg_replace('/[^0-9]/', '', $value);
        }

        return $value;
    }

}