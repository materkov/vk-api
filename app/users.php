<?php

require_once __DIR__ . '/errors.php';

const TOKEN_LIFETIME = 60 * 60; // 1 hour
const TOKEN_SECRET_KEY = 'secret-key';

/**
 * @param mixed  $app  App
 * @param string $id   User ID
 *
 * @return mixed user info, NULL if user not found, FALSE on failure
 */
function App_GetUser(&$app, string $id)
{
    $res = Store_GetUserById($app['db'], $id);
    if ($res === false) {
        App_Log($app, Store_GetLastError());
    }

    return $res;
}

/**
 * @param mixed  $app      App instance
 * @param string $username Contractor username
 * @param string $password Contractor password
 *
 * @return string Token string, NULL if invalid username/password, FALSE on failure
 */
function App_GetToken(&$app, string $username, string $password)
{
    $user = Store_GetUserByUsername($app['db'], $username);
    if ($user === null) {
        return null;
    } elseif ($user === false) {
        App_Log($app, Store_GetLastError());
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return null;
    }

    $payload = base64url_encode(json_encode([
        'can_create_order' => $user['can_create_order'],
        'can_execute_order' => $user['can_execute_order'],
        'exp' => time() + TOKEN_LIFETIME,
        'id' => $user['id'],
        'username' => $user['username'],
    ]));
    $hash = hash_hmac('sha256', $payload, TOKEN_SECRET_KEY);
    return "$payload.$hash";
}

/**
 * @param mixed  $app             App
 * @param string $username        Username
 * @param string $password        Password
 * @param bool   $canCreateOrder  Can user create orders
 * @param bool   $canExecuteOrder Can user execute orders
 *
 * @return int|null APP_OK, APP_ERR_BAD_USERNAME, APP_ERR_BAD_PASSWORD,
 *                  APP_ERR_BAD_PERMISSIONS, APP_ERR_GENERAL
 */
function App_CreateUser(
    &$app,
    string $username,
    string $password,
    bool $canCreateOrder,
    bool $canExecuteOrder,
    &$userId
): ?int
{
    if (empty($username) || mb_strlen($username) > 255) {
        return APP_ERR_BAD_USERNAME;
    } elseif (empty($password)) {
        return APP_ERR_BAD_PASSWORD;
    } elseif (!$canCreateOrder && !$canExecuteOrder) {
        return APP_ERR_BAD_PERMISSIONS;
    }

    $res = Store_GetUserByUsername($app['db'], $username);
    if ($res === false) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    } elseif ($res !== null) {
        return APP_ERR_USERNAME_REGISTERED;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    $res = Store_CreateUser($app['db'], $username, $passwordHash, $canCreateOrder, $canExecuteOrder);
    if ($res === false) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    $userId = $res;
    return APP_OK;
}
