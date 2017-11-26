<?php

require_once __DIR__ . '/../errors.php';

function Store_New_Memory()
{
    $storage = [
        'users' => [],
        'orders' => [],
        'transactions' => [],
        'transaction_user_balance' => [],
    ];
    return $storage;
}

function Store_GetUserById(&$db, int $id)
{
    foreach ($db['users'] as $user) {
        if ($user['id'] === $id) {
            return $user;
        }
    }
    return null;
}

function Store_AddUser(&$db, string $username, string $passwordHash, bool $canCreateOrder, bool $canExecuteOrder): ?int
{
    $id = rand(1, getrandmax());
    $db['users'][] = [
        'id' => $id,
        'password_hash' => $passwordHash,
        'username' => $username,
        'can_create_order' => $canCreateOrder,
        'can_execute_order' => $canExecuteOrder,
        'balance' => '0.00',
    ];

    return $id;
}

function Store_CreateOrder(&$db, string $price, int $userId, string $name, string $description): ?int
{
    $id = rand(1, getrandmax());
    $db['orders'][] = [
        'name' => $name,
        'description' => $description,
        'creator_user_id' => $userId,
        'done' => 0,
        'price' => $price,
        'id' => $id,
    ];
    return $id;
}

function Store_GetOrder(&$db, int $id)
{
    foreach ($db['orders'] as $order) {
        if ($order['id'] === $id) {
            return $order;
        }

    }
    return null;
}

function Store_GetOrders_NotDone(&$db, int $after, int $limit)
{
    usort($db['orders'], function($order1, $order2) {
        return $order2['id'] <=> $order1['id'];
    });

    $ordersCopy = $db['orders'];
    array_filter($ordersCopy, function($order) use ($after) {
        return $order['id'] > $after;
    });
    return array_slice($ordersCopy, 0, $limit);
}

function Store_CreateTransaction(&$db, int $orderId, string $orderSum, int $userId): int
{
    foreach ($db['transactions'] as $transaction) {
        if ($transaction['order_id'] == $orderId) {
            return STORAGE_ERR_TRANSACTION_EXISTS;
        }
    }
    $db['transactions'][] = [
        'id' => rand(1, getrandmax()),
        'order_id' => $orderId,
        'sum' => $orderSum,
        'finished' => 0,
    ];

    foreach ($db['transaction_user_balance'] as $item) {
        if ($item['user_id'] == $userId) {
            $item['balance'] = bcadd($item['balance'], $orderSum);
            return STORAGE_OK;
        }
    };

    $db['transaction_user_balance'][] = [
        'id' => rand(1, getrandmax()),
        'user_id' => $userId,
        'balance' => $orderSum,
    ];
    return STORAGE_OK;
}

function Store_GetTransactionUserBalance(&$db, int $userId): ?string
{
    foreach ($db['transaction_user_balance'] as $item) {
        if ($item['user_id'] == $userId) {
            return $item['balance'];
        }
    };
    return '0.0';
}

function Store_SaveUserBalance(&$db, int $userId, string $balance): bool
{
    foreach ($db['users'] as &$user) {
        if ($user['id'] == $userId) {
            $user['balance'] = $balance;
        }
    };
    return true;
}

function Store_UpdateOrderDone(&$db, int $orderId, bool $done): bool
{
    foreach ($db['orders'] as &$order) {
        if ($order['id'] == $orderId) {
            $order['done'] = $done;
        }
    };
    return true;
}

function Store_FinishTransaction(&$db, int $orderId): bool
{
    foreach ($db['transactions'] as &$transaction) {
        if ($transaction['order_id'] == $orderId) {
            $transaction['finished'] = true;
        }
    };
    return true;
}

function Store_GetTransactionByOrderId(&$db, int $orderId)
{
    foreach ($db['transactions'] as $transaction) {
        if ($transaction['order_id'] == $orderId) {
            return $transaction;
        }
    };
    return null;
}

function Store_GetUserByUsername(&$db, string $username)
{
    foreach ($db['users'] as $user) {
        if ($user['username'] === $username) {
            return $user;
        }
    };
    return null;
}

function Store_CreateUser(&$db, string $username, string $passwordHash, bool $canCreateOrder, bool $canExecuteOrder)
{
    $id = rand(1, getrandmax());
    $db['users'][] = [
        'username' => $username,
        'password_hash' => $passwordHash,
        'can_create_order' => (int)$canCreateOrder,
        'can_execute_order' => (int)$canExecuteOrder,
        'balance' => '0.0',
        'id' => $id,
    ];
    return null;
}
