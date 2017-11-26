<?php

require_once __DIR__ . '/../utils/base64.php';
require_once __DIR__ . '/errors.php';

const ORDER_COMMISSION = '0.12';
const CALCULATION_PRECISE = 2;

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
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    } elseif (!$user['can_create_order']) {
        return APP_ERR_USER_CANT_CREATE_ORDER;
    }

    $orderId = Store_CreateOrder($app['db'], $price, $userId, $name, $description);
    if ($orderId === false) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    return APP_OK;
}

/**
 * @param     $app
 * @param int $after Pagination, after
 * @param int $limit Pagination, limit
 *
 * @return mixed Array of orders, FALSE on failure
 */
function App_OrdersList(&$app, int $after, int $limit)
{
    $orders = Store_GetOrders_NotDone($app['db'], $after, $limit);
    if ($orders === false) {
        App_Log($app, Store_GetLastError());
        return false;
    }

    return $orders;
}

/**
 * @param mixed $app
 * @param int   $id Order ID
 *
 * @return mixed Order info, NULL if not found, FALSE on failure
 */
function App_GetOrder(&$app, int $id)
{
    $order = Store_GetOrder($app['db'], $id);
    if ($order === false) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    return $order;
}

/**
 * @param     $app
 * @param int $userId  User ID
 * @param int $orderId Order ID
 *
 * @return int APP_OK, APP_ERR_GENERAL, APP_ERR_BAD_ORDER, APP_ERR_ORDER_ALREADY_EXECUTED,
 *             APP_ERR_BAD_USER, APP_ERR_USER_CANT_EXECUTE_ORDER
 */
function App_Order_Execute(&$app, int $userId, int $orderId): int
{
    $user = Store_GetUserById($app['db'], $userId);
    if ($user === null) {
        return APP_ERR_BAD_USER;
    } elseif ($user === false) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    } elseif (!$user['can_execute_order']) {
        return APP_ERR_USER_CANT_EXECUTE_ORDER;
    }

    $order = Store_GetOrder($app['db'], $orderId);
    if ($order === null) {
        return APP_ERR_BAD_ORDER;
    } elseif ($order === false) {
        App_Log($app, Store_GetLastError());
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
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    $balance = Store_GetTransactionUserBalance($app['db'], $userId);
    if ($balance === false) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    $res = Store_SaveUserBalance($app['db'], $userId, $balance);
    if (!$res) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    $res = Store_UpdateOrderDone($app['db'], $orderId, true);
    if (!$res) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    $res = Store_FinishTransaction($app['db'], $orderId);
    if (!$res) {
        App_Log($app, Store_GetLastError());
        return APP_ERR_GENERAL;
    }

    return $returnResult;
}
