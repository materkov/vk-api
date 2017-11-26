<?php

require_once __DIR__ . '/base64.php';

/**
 * @param string $token  Token
 * @param string $secret Secret
 *
 * @return int|null Return user id (if token valid) or NULL
 */
function Utils_GetUserIdFromToken(string $token, string $secret): ?int
{
    $parts = explode(".", $token, 2);
    if (count($parts) != 2) {
        return null;
    }

    list($payload, $hash) = $parts;
    if (empty($payload) || empty($hash)) {
        return null;
    }

    if (hash_hmac('sha256', $payload, $secret) !== $hash) {
        return null;
    }

    $data = json_decode(base64url_decode($payload), true);
    if (empty($data['id']) || !is_int($data['id'])) {
        return null;
    }

    return $data['id'];
}
