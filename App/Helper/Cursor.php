<?php

namespace App\Helper;

/**
 * Opaque, URL-safe cursor tokens (base64url of a numeric id).
 */
class Cursor
{
    /** Encode an integer cursor into a URL-safe opaque token. */
    public static function encode(int $cursor): string
    {
        $raw = (string)$cursor;
        $b64 = base64_encode($raw);
        return rtrim(strtr($b64, '+/', '-_'), '=');
    }

    /** Try to decode a URL-safe token into an integer. Returns null on failure. */
    public static function decode(string $token): ?int
    {
        if ($token === '') {
            return null;
        }
        // Backward compatibility: accept plain numeric cursor
        if (ctype_digit($token)) {
            return (int)$token;
        }
        // base64url -> base64
        $b64 = strtr($token, '-_', '+/');
        $pad = (4 - (strlen($b64) % 4)) % 4;
        if ($pad) {
            $b64 .= str_repeat('=', $pad);
        }
        $decoded = base64_decode($b64, true);
        if ($decoded === false) {
            return null;
        }
        $decoded = trim($decoded);
        if ($decoded === '' || !ctype_digit($decoded)) {
            return null;
        }
        return (int)$decoded;
    }

    /** Encode or return null when next cursor doesn't exist. */
    public static function encodeOrNull(?int $cursor): ?string
    {
        return $cursor !== null ? self::encode($cursor) : null;
    }
}
