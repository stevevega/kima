<?php
/**
 * Kima Password
 * @author Steve Vega
 */
namespace Kima\Crypt;

use Kima\Error;

/**
 * Creates hash passwords based on the bcrypt type
 * @see http://php.net/manual/en/function.crypt.php
 */
class BCrypt
{

    /**
     * Error messages
     */
    const ERROR_INVALID_ITERATION_NUMBER = 'Iteration number must be a value between 4 and 31';

    /**
     * Salt format
     */
    const SALT_FORMAT = '$2y$%02d$%s$';

    /**
     * Default iteration value
     */
    const DEFAULT_ITERATION_NUMBER = 10;

    /**
     * Hash a password using bcrypt
     * @param string $password
     * @param int    $iteration_number
     */
    public static function hash($password, $iteration_number = 0)
    {
        $iteration_number = self::get_iteration_number($iteration_number);

        // generate the salt and add the bcrypt params
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        $salt = sprintf(self::SALT_FORMAT, $iteration_number, $salt);

        // return the bcrypt hash
        return crypt((string) $password, $salt);
    }

    /**
     * Verifies whether a password match or not with the hashed password
     * @param  string  $password
     * @param  string  $hashed_password
     * @return boolean
     */
    public static function verify($password, $hashed_password)
    {
        return $hashed_password === crypt((string) $password, (string) $hashed_password)
            ? true
            : false;
    }

    /**
     * Gets the iteration number used for bcrypt hashing
     * @param  int $iteration_number
     * @return int
     */
    private static function get_iteration_number($iteration_number)
    {
        // get the iteration number if set
        if (!empty($iteration_number)) {
            $iteration_number = (int) $iteration_number;
            if (4 > $iteration_number || 31 < $iteration_number) {
                Error::set(self::ERROR_INVALID_ITERATION_NUMBER);
            }
        } else {
            $iteration_number = self::DEFAULT_ITERATION_NUMBER;
        }

        return $iteration_number;
    }

}
