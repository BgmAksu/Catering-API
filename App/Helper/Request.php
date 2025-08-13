<?php

namespace App\Helper;

/**
 * Helper class for Request related
 */
class Request
{
    /**
     * Decide cursor value for cursor based pagination
     * @return int
     */
    public static function cursorDecider(): int
    {
        $param = $_GET['cursor'] ?? null;
        if ($param === null) {
            return 0;
        }
        $token = trim((string)$param);

        // Fast path: plain number
        if ($token !== '' && ctype_digit($token)) {
            return (int)$token;
        }

        // Try base64url
        $decoded = Cursor::decode($token);
        return $decoded ?? 0;
    }

    /**
     * Decide limit value for cursor based pagination
     * @param int $default
     * @return int
     */
    public static function limitDecider(int $default = 20): int
    {
        return isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0
            ? (int)$_GET['limit'] : $default;
    }

    /**
     * @return array|mixed
     */
    public static function getJsonData(): mixed
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}