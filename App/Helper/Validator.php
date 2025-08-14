<?php

namespace App\Helper;

/**
 * Helper class for validation data from client
 */
class Validator
{
    /**
     * Validate if string is not empty
     * @param $input
     * @return bool
     */
    public static function notEmpty($input): bool
    {
        return strlen(trim($input)) > 0;
    }

    /**
     * Validate email address
     * @param $input
     * @return bool
     */
    public static function email($input): bool
    {
        return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number: basic, at least 8 digits
     * Can be made for only NL number structure
     * @param $input
     * @return false|int
     */
    public static function phone($input): false|int
    {
        return preg_match('/^\+?\d{8,15}$/', $input);
    }

    /**
     * Validate country code: 2 uppercase letters
     * Can be made for only 'NL'
     * @param $input
     * @return false|int
     */
    public static function countryCode($input): false|int
    {
        return preg_match('/^[A-Z]{2}$/', $input);
    }

    /**
     * Validate zip code: non-empty
     * Can be made for only NL city codes
     * @param $input
     * @return bool
     */
    public static function zipCode($input): bool
    {
        return strlen(trim($input)) > 0;
    }
}