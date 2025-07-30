<?php

namespace App\Helper;

class Request
{
    public static function cursorDecider()
    {
        return isset($_GET['cursor']) && is_numeric($_GET['cursor']) ? (int)$_GET['cursor'] : 0;
    }

    public static function limitDecider($default = 20)
    {
        return isset($_GET['limit']) && is_numeric($_GET['limit']) && $_GET['limit'] > 0
            ? (int)$_GET['limit'] : $default;
    }

    public static function getJsonData()
    {
        return json_decode(file_get_contents('php://input'), true) ?: [];
    }
}