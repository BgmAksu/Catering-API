<?php
namespace App\Middleware;

use App\Plugins\Http\Response\Unauthorized;
use JetBrains\PhpStorm\NoReturn;

class Authenticate
{
    public static function check(): void
    {
        $config = include __DIR__ . '/../../config/config.php';

        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? null;
        if (!$authHeader) {
            self::unauthorized();
        }

        $parts = explode(' ', $authHeader, 2);
        if (count($parts) !== 2 || strtolower($parts[0]) !== 'bearer' || empty($parts[1])) {
            self::unauthorized();
        }

        $token = $parts[1];
        if ($token !== $config['api']['secret']) {
            self::unauthorized();
        }
    }

    #[NoReturn] private static function unauthorized(): void
    {
        (new Unauthorized(['error' => 'Unauthorized']))->send();
        exit;
    }
}
