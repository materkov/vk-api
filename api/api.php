<?php

require_once __DIR__ . '/../app/app.php';
require_once __DIR__ . '/middleware.php';

function Api_HandleOrderCreate($app, $authData, $url, $args, $body)
{
    $orderId = 0;
    $result = App_CreateOrder(
        $app,
        $authData['user_id'],
        $body['name'],
        $body['description'],
        $body['price'],
        $orderId
    );
    if ($result === APP_ERR_BAD_ORDER_NAME) {
        return error("bad_order_name", "Bad order name");
    } elseif ($result === APP_ERR_BAD_ORDER_DESC) {
        return error("bad_order_desc", "Bad order deescription");
    } elseif ($result === APP_ERR_BAD_ORDER_PRICE) {
        return error("bad_order_price", "Bad order price");
    } elseif ($result === APP_ERR_USER_CANT_CREATE_ORDER) {
        return error("user_cant_create_order", "Current user cannot create orders");
    } elseif ($result !== APP_OK) {
        return generalError();
    }

    return [200, ['id' => $orderId]];
}

function Api_HandleOrdersList($app, $authData, $url, $args, $body)
{
    if (!isset($args['after']) || !is_numeric($args['after'])) {
        $args['after'] = 0;
    } else {
        $args['after'] = intval($args['after']);
    }
    if (!isset($args['limit']) || !is_numeric($args['limit'])) {
        $args['limit'] = 20;
    } else {
        $args['limit'] = intval($args['limit']);
    }
    if ($args['limit'] < 1) {
        $args = 1;
    }
    if ($args['limit'] > 50) {
        $args = 50;
    }

    [$orders, $error] = App_OrdersList($app, $args['after'], $args['limit']);
    $nextAfter = count($orders) > 0 && count($orders) == $args['limit'] ? $orders[count($orders) - 1]['id'] : null;
    if ($error) {
        return [
            400,
            ['error' => $error],
        ];
    }
    return [
        200,
        [
            'orders' => $orders,
            'next_after' => $nextAfter
        ]
    ];
}

function Api_HandleOrder($app, $authData, $url, $args, $body)
{
    preg_match("/^\\/orders\\/(\d+)$/", $url, $matches);
    $order = App_GetOrder($app, intval($matches[1]));
    return [200, $order];
}

function Api_HandleOrder_Execute($app, $authData, $url, $args, $body)
{
    if (!$authData['user_id']) {
        return authError();
    }

    preg_match('/^\\/orders\\/(\d+)\\/exec$/', $url, $matches);
    if (!isset($matches[1]) || !intval($matches[1])) {
        return generalError();
    }
    $orderId = (int)$matches[1];

    $result = App_Order_Execute($app, $authData['user_id'], $orderId);
    if ($result == APP_ERR_ORDER_ALREADY_EXECUTED) {
        return error("order_already_executed", "This order already executed");
    } elseif ($result == APP_ERR_BAD_ORDER) {
        return error("order_not_found", "Order $orderId not found");
    } elseif ($result == APP_ERR_USER_CANT_EXECUTE_ORDER) {
        return error("user_cant_execute_order", "Current user cannot execute orders");
    } elseif ($result != APP_OK) {
        return generalError();
    }

    return [204, null];
}

function Api_HandleAuth($app, $authData, $url, $args, $body)
{
    $result = App_GetToken($app, $body['username'] ?? "", $body['password'] ?? "");
    if ($result === null) {
        return generalError();
    } elseif ($result === false) {
        return error("invalid_credentials", "Wrong username or password", 403);
    }
    return [200, ['token' => $result]];
}

function Api_Handle_Users_Me($app, $authData, $url, $args, $body)
{
    $result = App_GetUser($app, $authData['user_id']);
    return [200, $result];
}
