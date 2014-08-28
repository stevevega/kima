<?php
/**
 * Kima Password
 * @author Steve Vega
 */
namespace Kima\Crypt;

/**
 * Creates hash passwords based on the bcrypt type
 * @see http://php.net/manual/en/function.crypt.php
 */
class Nonce
{

    /**
     * Gets a nonce string
     * @param int $size size in bytes
     */
    public static function get($size = 32)
    {
        $random = openssl_random_pseudo_bytes($size);

        // convert the random value to hexadecimal
        return bin2hex($random);
    }

}
