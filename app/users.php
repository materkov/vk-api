<?php

require_once __DIR__ . '/errors.php';

function App_GetUser($app, string $id)
{
    return Store_GetUserById($app['db'], $id);
}

/**
 * @param mixed  $app      App instance
 * @param string $username Contractor username
 * @param string $password Contractor password
 *
 * @return string|null|false Return token if username&password is valid, false otherwise. Null means general error
 */
function App_GetToken($app, string $username, string $password)
{
    $user = Store_GetUserByUsername($app['db'], $username);
    if ($user === null) {
        return null;
    } elseif ($user === false) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    return createToken($user);
}

function createToken($user)
{
    $payload = base64url_encode(json_encode([
        'can_create_order' => $user['can_create_order'],
        'can_execute_order' => $user['can_execute_order'],
        'exp' => time() + TokenLifeTime,
        'id' => $user['id'],
        'username' => $user['username'],
    ]));
    $hash = hash_hmac('sha256', $payload, "secret-key");
    return "$payload.$hash";
}

/**
 * @param string $token
 *
 * @return int|null Return contractor id (if token valid) or null otherwise
 */
function App_GetUserIdFromToken(string $token): ?int
{
    $parts = explode(".", $token, 2);
    if (count($parts) != 2) {
        return null;
    }

    list($payload, $hash) = $parts;
    if (empty($payload) || empty($hash)) {
        return null;
    }

    if (hash_hmac('sha256', $payload, "secret-key") !== $hash) {
        return null;
    }

    $data = json_decode(base64url_decode($payload), true);
    if (empty($data['id']) || !is_int($data['id'])) {
        return null;
    }

    return $data['id'];
}
