<?php

namespace App\Helper;

class Validator
{
    /**
     * Validate if string is not empty
     */
    public static function notEmpty($input)
    {
        return strlen(trim($input)) > 0;
    }

    /**
     * Validate email address
     */
    public static function email($input)
    {
        return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number: basic, at least 8 digits
     * Can be made for only NL number structure
     */
    public static function phone($input)
    {
        return preg_match('/^\+?\d{8,15}$/', $input);
    }

    /**
     * Validate country code: 2 uppercase letters
     * Can be made for only 'NL'
     */
    public static function countryCode($input)
    {
        return preg_match('/^[A-Z]{2}$/', $input);
    }

    /**
     * Validate zip code: non-empty
     * Can be made for only NL city codes
     */
    public static function zipCode($input)
    {
        return strlen(trim($input)) > 0;
    }
}