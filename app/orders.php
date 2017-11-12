<?php

require_once __DIR__ . '/../store/Store.php';
require_once __DIR__ . '/../utils/base64.php';

const ErrorBadOrderName = 'bad_order_name';
const ErrorBadOrderPrice = 'bad_price';
const ErrorBadOrderDescription = 'bad_description';
const ErrorInternal = 'internal_error';

const TokenLifeTime = 60 * 60; // 1 hour
const ORDER_COMMISSION = 1.23;

function App_CreateOrder($app, int $customerId, string $name, string $description, string $price): array
{
    if (mb_strlen($name) == 0 || mb_strlen($name) > 255) {
        return [0, ErrorBadOrderName];
    }
    if (!preg_match("/^\\d+\\.\\d\\d$/", $price)) {
        return [0, ErrorBadOrderPrice];
    }
    if (mb_strlen($description) == 0 || mb_strlen($description) > 20000) {
        return [0, ErrorBadOrderDescription];
    }
    $orderId = Store_CreateOrder($app['db'], $price, $customerId, $name, "desc");
    if (empty($orderId)) {
        return [0, ErrorInternal];
    }

    return $orderId;
}

function App_OrdersList($app, int $after, int $limit): array
{
    $orders = Store_GetWaitingOrders($app['db'], $after, $limit);
    return [$orders, null];
}

function App_Order($app, int $id): array
{
    $order = Store_GetOrder($app['db'], $id);
    return $order;
}

/**
 * @param mixed  $app      App instance
 * @param string $username Contractor username
 * @param string $password Contractor password
 *
 * @return string|null|false Return token if username&password is valid, false otherwise. Null means general error
 */
function App_GetToken_Contractor($app, string $username, string $password)
{
    $contractor = Store_GetContractorByUsername($username);
    if ($contractor === null) {
        return null;
    } elseif ($contractor === false) {
        return false;
    }

    if (!password_verify($password, $contractor['password_hash'])) {
        return false;
    }

    return createContractorToken($contractor['id'], $contractor['username']);
}

/**
 * @param mixed $app App instance
 * @param int   $id  Contractor id
 *
 * @return array|null Data about contractor, false if invalid token. Null - general error.
 */
function App_GetContractorInfo($app, int $id)
{
    $data = Store_GetContractorById($id);
    if ($data === null || $data === false) {
        return null;
    }

    unset($data['password_hash']);

    return $data;
}

/**
 * @param     $app
 * @param int $contractorId
 * @param int $orderId
 *
 * @return null|bool Null=General error, False=order not found, already executed, successfully executed
 */
function App_Order_Execute($app, int $contractorId, int $orderId): ?bool
{
    // Get order, sum, check if done
    $order = Store_GetOrder($app['db'], $orderId);
    if ($order === null) {
        return null;
    } elseif ($order === false) {
        return false;
    }
    if ($order['done']) {
        return false;
    }

    // Get order
    $contractor = Store_GetContractorById($contractorId);
    if ($contractor === false || $contractor === null) {
        return null;
    }

    $orderSum = $order['sum'] - ORDER_COMMISSION;

    // Create transaction
    $ok = Store_CreateTransaction($app['db'], $orderId, $orderSum, $contractorId);
    if ($ok == null) {
        return null;
    }

    // Update orders table
    Store_SaveOrderDone($app['db'], $orderId, true);

    // Update contractor table
    //Store_SaveContractorBalance($app['db'], $contractorId, )
}

function createContractorToken(int $id, string $username)
{
    $payload = base64url_encode(json_encode([
        'type' => 'contractor',
        'exp' => time() + TokenLifeTime,
        'id' => $id,
        'username' => $username,
    ]));
    $hash = hash_hmac('sha256', $payload, "secret-key");
    return "$payload.$hash";
}

/**
 * @param string $token
 *
 * @return int|null Return contractor id (if token valid) or null otherwise
 */
function App_GetContractorFromToken(string $token): ?int
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
    if (empty($data['type']) || empty($data['exp']) || empty($data['id']) || empty($data['username'])) {
        return null;
    }

    if ($data['type'] != 'contractor') {
        return null;
    }
    if (!is_int($data['exp']) || $data['exp'] < time()) {
        return null;
    }

    return $data['id'];
}
