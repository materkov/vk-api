<?php

require_once __DIR__ . '/../utils/base64.php';
require_once __DIR__ . '/errors.php';

const ErrorBadOrderName = 'bad_order_name';
const ErrorBadOrderPrice = 'bad_price';
const ErrorBadOrderDescription = 'bad_description';
const ErrorInternal = 'internal_error';

const TokenLifeTime = 60 * 60; // 1 hour
const ORDER_COMMISSION = '0.12';

/**
 * @param        $app
 * @param int    $userId      User ID
 * @param string $name        Order name
 * @param string $description Order description
 * @param string $price       Order price
 * @param int    $orderId     Output value, order id if order was created successfully
 *
 * @return int APP_OK if successful, possible errors:
 *             APP_ERR_BAD_ORDER_NAME, APP_ERR_BAD_ORDER_PRICE, APP_ERR_BAD_ORDER_DESC, APP_ERR_BAD_USER
 *             APP_ERR_USER_CANT_CREATE_ORDER, APP_ERR_GENERAL
 */
function App_CreateOrder(&$app, int $userId, string $name, string $description, string $price, int &$orderId): int
{
    if (mb_strlen($name) == 0 || mb_strlen($name) > 255) {
        return APP_ERR_BAD_ORDER_NAME;
    } elseif (mb_strlen($description) == 0 || mb_strlen($description) > 10000) {
        return APP_ERR_BAD_ORDER_DESC;
    } elseif (!preg_match("/^\\d{1,10}\\.\\d\\d$/", $price)) {
        return APP_ERR_BAD_ORDER_PRICE;
    } elseif (bccomp($price, ORDER_COMMISSION, CALCULATION_PRECISE) !== 1) {
        return APP_ERR_BAD_ORDER_PRICE;
    }

    $user = Store_GetUserById($app['db'], $userId);
    if ($user === null) {
        return APP_ERR_BAD_USER;
    } elseif ($user === false) {
        return APP_ERR_GENERAL;
    } elseif (!$user['can_create_order']) {
        return APP_ERR_USER_CANT_CREATE_ORDER;
    }

    $orderId = Store_CreateOrder($app['db'], $price, $userId, $name, $description);
    if ($orderId === null) {
        return APP_ERR_GENERAL;
    }

    return APP_OK;
}

/**
 * @param     $app
 * @param int $after Pagination, after
 * @param int $limit Pagination, limit
 *
 * @return array|null Array of orders, null on failure
 */
function App_OrdersList(&$app, int $after, int $limit): ?array
{
    $orders = Store_GetOrders_NotDone($app['db'], $after, $limit);
    if ($orders === false) {
        return null;
    } else {
        return $orders;
    }
}

/**
 * @param     $app
 * @param int $id
 *
 * @return array
 */
function App_GetOrder($app, int $id): array
{
    $order = Store_GetOrder($app['db'], $id);
    if ($order === false) {

    }
    return $order;
}

const CALCULATION_PRECISE = 2;

/**
 * @param     $app
 * @param int $userId
 * @param int $orderId
 *
 * @return int APP_OK, APP_ERR_GENERAL, APP_ERR_ORDER_NOT_FOUND, APP_ERR_ORDER_ALREADY_EXECUTED
 */
function App_Order_Execute(&$app, int $userId, int $orderId): int
{
    $user = Store_GetUserById($app['db'], $userId);
    if ($user === null) {
        return APP_ERR_BAD_USER;
    } elseif ($user === false) {
        return APP_ERR_GENERAL;
    }
    if (!$user['can_execute_order']) {
        return APP_ERR_USER_CANT_EXECUTE_ORDER;
    }

    // Get order, sum, check if done
    $order = Store_GetOrder($app['db'], $orderId);
    if ($order === null) {
        return APP_ERR_BAD_ORDER;
    } elseif ($order === false) {
        return APP_ERR_GENERAL;
    } elseif ($order['done']) {
        return APP_ERR_ORDER_ALREADY_EXECUTED;
    }

    $orderSum = bcsub($order['price'], ORDER_COMMISSION, CALCULATION_PRECISE);
    $returnResult = APP_OK;

    $result = Store_CreateTransaction($app['db'], $orderId, $orderSum, $userId);
    if ($result == STORAGE_ERR_TRANSACTION_EXISTS) {
        $returnResult = APP_ERR_ORDER_ALREADY_EXECUTED;
    } elseif ($result != STORAGE_OK) {
        return APP_ERR_GENERAL;
    }

    $balance = Store_GetTransactionUserBalance($app['db'], $userId);
    if ($balance === false) {
        return APP_ERR_GENERAL;
    }

    $res = Store_SaveUserBalance($app['db'], $userId, $balance);
    if (!$res) {
        return APP_ERR_GENERAL;
    }

    $res = Store_UpdateOrderDone($app['db'], $orderId, true);
    if (!$res) {
        return APP_ERR_GENERAL;
    }

    $res = Store_FinishTransaction($app['db'], $orderId);
    if (!$res) {
        return APP_ERR_GENERAL;
    }

    return $returnResult;
}
