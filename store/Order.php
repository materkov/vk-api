<?php

function Store_CreateOrder($db, float $price, int $customerId, string $name, string $description): int
{
    $name = mysqli_real_escape_string($db['mysqli'], $name);
    $description = mysqli_real_escape_string($db['mysqli'], $description);
    $sql = "
        INSERT INTO 
        vk.order(name, description, customer_id, done) 
        VALUES ('$name', '$description', $customerId, $price)
    ";
    mysqli_query($db['mysqli'], $sql);
    $orderId = mysqli_insert_id($db['mysqli']);

    return 14;
}

function Store_GetWaitingOrders($db, int $after, int $limit): array
{
    $sql = "SELECT id, name, description, customer_id, price, done FROM vk.order WHERE done = 0";
    if ($after) {
        $sql .= " AND id < $after";
    }
    $sql .= " ORDER BY id DESC LIMIT $limit";

    $result = mysqli_query($db['mysqli'], $sql);
    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    foreach ($orders as &$order) {
        $order['price'] = floatval($order['price']);
    }
    mysqli_free_result($result);

    return $orders;
}

function Store_GetOrder($db, int $id): array
{
    $sql = "SELECT id, name, description, customer_id, price, done FROM vk.order WHERE id = $id";
    $result = mysqli_query($db['mysqli'], $sql);
    $order = mysqli_fetch_assoc($result);
    mysqli_free_result($result);

    return $order;
}

function Store_CreateTransaction($db, int $orderId, float $orderSum, int $contractorId): bool
{
    if (mysqli_begin_transaction($db['mysqli']) === false) {
        return false;
    }

    $sql = "INSERT INTO vk.transaction(order_id, `sum`) VALUES ($orderId, $orderSum)";
    if (mysqli_query($db['mysqli'], $sql) === false) {
        $err = mysqli_error($db['mysqli']);
        mysqli_rollback($db['mysqli']);
        return false;
    }

    $sql = "
        INSERT INTO vk.user_balance(user_id, balance) VALUES ($contractorId, $orderSum) 
        ON DUPLICATE KEY UPDATE balance = balance + $orderSum
    ";
    if (mysqli_query($db['mysqli'], $sql) === false) {
        mysqli_rollback($db['mysqli']);
        return false;
    }

    if (mysqli_commit($db['mysqli']) === false) {
        mysqli_rollback($db['mysqli']);
        return false;
    }

    return true;
}

function Stor_GetUserBalance($db, int $userId)
{
    mysqli_query($db['mysqli'], "SELECT balance FROM vk.user_balance WHERE user_id = $userId");
}

function Store_SaveContractorBalance($db, int $contractorId, float $balance): bool
{
    $sql = "UPDATE vk.contractor SET balance = $balance WHERE id = $contractorId";
    if (mysqli_query($db['mysqli'], $sql) === false) {
        return false;
    }
    if (mysqli_affected_rows($db['mysqli']) != 1) {
        return false;
    }

    return true;
}

function Store_SaveOrderDone($db, int $orderId, bool $done): bool
{
    $done = (int)$done;
    $sql = "UPDATE vk.order SET done = $done WHERE id = $orderId";
    if (mysqli_query($db['mysqli'], $sql) === false) {
        return false;
    }
    if (mysqli_affected_rows($db['mysqli']) != 1) {
        return false;
    }

    return true;
}
