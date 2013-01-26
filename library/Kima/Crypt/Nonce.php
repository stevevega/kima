<?php
/**
 * Kima Password
 * @author Steve Vega
 */
namespace Kima\Crypt;

use \Kima\Error;

/**
 * Creates hash passwords based on the bcrypt type
 * @see http://php.net/manual/en/function.crypt.php
 */
class Nonce
{

    /**
     * Gets a nonce string
     * @param int $size
     */
    public static function get($size = 32)
    {
        $nonce = '';
        for ($x = 0; $x < $size; $x++)
        {
            $nonce .= chr(mt_rand(0, 255));
        }

        return base64_encode($nonce);
    }

}