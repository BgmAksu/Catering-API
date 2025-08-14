<?php

namespace App\Helper;

/**
 * Helper class for sanitization data from client
 */
class Sanitizer
{
    /**
     * Sanitize a string: remove HTML tags and trim whitespace
     * @param $input
     * @return string
     */
    public static function string($input): string
    {
        return trim(strip_tags((string)$input));
    }

    /**
     * Sanitize an email: lowercase, trim, validate format (returns empty string if invalid)
     * @param $input
     * @return string
     */
    public static function email($input): string
    {
        $email = trim(strtolower($input));
        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
    }

    /**
     * Sanitize a phone number: remove all chars except digits and '+'
     * Can be made for only NL number structure
     * @param $input
     * @return array|string|null
     */
    public static function phone($input): array|string|null
    {
        return preg_replace('/[^\d+]/', '', $input);
    }

    /**
     * Sanitize a country code: 2 uppercase letters
     * Can be made for only 'NL'
     * @param $input
     * @return string
     */
    public static function countryCode($input): string
    {
        $code = strtoupper(trim($input));
        return preg_match('/^[A-Z]{2}$/', $code) ? $code : '';
    }

    /**
     * Sanitize a zip code: remove spaces and uppercase
     * Can be made for only NL city codes
     * @param $input
     * @return string
     */
    public static function zipCode($input): string
    {
        return strtoupper(str_replace(' ', '', trim($input)));
    }
}