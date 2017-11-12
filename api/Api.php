<?php

require_once __DIR__ . '/../app/app.php';
require_once __DIR__ . '/middleware.php';

function Api_HandleOrderCreate($app, $authData, $url, $args, $body)
{
    [$id, $error] = App_CreateOrder($app, $args['user_id'] ?? 0, $args['name'] ?? "", $args['description'] ?? "",
        $args['price'] ?? "");
    if ($error) {
        return [
            400,
            ['error' => $error],
        ];
    }
    return [
        200,
        ['id' => $id]
    ];
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
    $order = App_Order($app, intval($matches[1]));
    return [200, $order];
}

function Api_HandleOrder_Execute($app, $authData, $url, $args, $body)
{
    if (!$authData['contractor_id']) {
        return authError();
    }

    preg_match('/^\\/orders\\/(\d+)\\/exec$/', $url, $matches);
    if (!isset($matches[1]) || !intval($matches[1])) {
        return generalError();
    }

    $result = App_Order_Execute($app, $authData['contractor_id'], (int)$matches[1]);
}

function Api_HandleAuth_Contractor($app, $authData, $url, $args, $body)
{
    $result = App_GetToken_Contractor($app, $body['username'] ?? "", $body['password'] ?? "");
    if ($result === null) {
        return generalError();
    } elseif ($result === false) {
        return error("invalid_credentials", "Wrong username or password", 403);
    }
    return [200, ['token' => $result]];
}

function Api_HandleContractor_Me($app, $authData, $url, $args, $body)
{
    if (!$authData['contractor_id']) {
        return authError();
    }

    $result = App_GetContractorInfo($app, $authData['contractor_id']);
    if ($result === null) {
        return generalError();
    }

    return [200, $result];
}
