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
        return isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;
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