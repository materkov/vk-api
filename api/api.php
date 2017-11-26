<?php

require_once __DIR__ . '/../app/app.php';
require_once __DIR__ . '/middleware.php';

function Api_SerializeUser(array $user): array
{
    unset($user['password_hash']);
    $user['balance'] = (float)$user['balance'];
    $user['id'] = (string)$user['id'];
    return $user;
}

function Api_SerializeOrder(array $order): array
{
    $order['id'] = (string)$order['id'];
    $order['creator_user_id'] = (string)$order['creator_user_id'];
    $order['price'] = (float)$order['price'];
    return $order;
}

function Api_Handle_Order_Create(&$app, $authData, $url, $args, $body)
{
    $orderId = 0;
    $result = App_CreateOrder(
        $app,
        $authData['user_id'],
        $body['name'] ?? "",
        $body['description'] ?? "",
        $body['price'] ?? "",
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
    } elseif ($result === APP_ERR_BAD_USER) {
        return error("access_denied", "Invalid access token");
    } elseif ($result !== APP_OK) {
        return generalError();
    }

    return [200, ['id' => (string)$orderId]];
}

function Api_Handle_Order_List(&$app, $authData, $url, $args, $body)
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

    $orders = App_OrdersList($app, $args['after'], $args['limit']);
    if ($orders === false) {
        return generalError();
    }

    $nextAfter = null;
    if (count($orders) > 0 && count($orders) == $args['limit']) {
        $nextAfter = (string)$orders[count($orders) - 1]['id'];
    }
    return [
        200,
        [
            'orders' => array_map('Api_SerializeOrder', $orders),
            'next_after' => $nextAfter
        ]
    ];
}

function Api_Handle_Order_Details(&$app, $authData, $url, $args, $body)
{
    preg_match("/^\\/orders\\/(\d+)$/", $url, $matches);
    $orderId = intval($matches[1]);
    $res = App_GetOrder($app, $orderId);
    if ($res === null) {
        return error("order_not_found", "Order $orderId not found");
    } elseif ($res === false) {
        return generalError();
    }

    return [200, Api_SerializeOrder($res)];
}

function Api_Handle_Order_Execute(&$app, $authData, $url, $args, $body)
{
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
    } elseif ($result == APP_ERR_BAD_USER) {
        return error("access_denied", "Access denied");
    } elseif ($result != APP_OK) {
        return generalError();
    }

    return [204, null];
}

function Api_Handle_Auth(&$app, $authData, $url, $args, $body)
{
    $res = App_GetToken($app, $body['username'] ?? "", $body['password'] ?? "");
    if ($res === null) {
        return error("invalid_credentials", "Wrong username or password", 403);
    } elseif ($res === false) {
        return generalError();
    }

    return [200, ['token' => $res]];
}

function Api_Handle_Users_Me(&$app, $authData, $url, $args, $body)
{
    $user = App_GetUser($app, $authData['user_id']);
    if ($user === null || $user === false) {
        return generalError();
    }

    return [200, Api_SerializeUser($user)];
}

function Api_Handle_Register(&$app, $authData, $url, $args, $body)
{
    if (!isset($body['username']) || !is_string($body['username'])) {
        return error("bad_username", "Bad username");
    } elseif (!isset($body['password']) || !is_string($body['password'])) {
        return error("bad_password", "Bad password");
    } elseif (!isset($body['can_create_order']) || !is_bool($body['can_create_order'])) {
        return error("bad_create_order", "Bad can_create_order");
    } elseif (!isset($body['can_execute_order']) || !is_bool($body['can_execute_order'])) {
        return error("bad_can_execute_order", "Bad can_execute_order");
    }

    $res = App_CreateUser(
        $app, $body['username'], $body['password'], $body['can_create_order'],
        $body['can_execute_order'], $userId
    );
    if ($res === APP_ERR_BAD_USERNAME) {
        return error("bad_username", "Bad username");
    } elseif ($res === APP_ERR_BAD_PASSWORD) {
        return error("bad_password", "Bad password");
    } elseif ($res === APP_ERR_BAD_PERMISSIONS) {
        return error("bad_permissions", "You should specify at least one of can_create_order or can_execute_order");
    } elseif ($res === APP_ERR_USERNAME_REGISTERED) {
        return error("username_already_registered", "This username already registered");
    } elseif ($res !== APP_OK) {
        return generalError();
    }

    return [200, ['id' => (string)$userId]];
}
