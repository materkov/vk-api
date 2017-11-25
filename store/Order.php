<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/errors.php';

/**
 * @param        $db
 * @param string $price
 * @param int    $customerId
 * @param string $name
 * @param string $description
 *
 * @return int|null OrderID, null on failure
 */
function Store_CreateOrder($db, string $price, int $customerId, string $name, string $description): ?int
{
    $name = mysqli_real_escape_string($db['mysqli'], $name);
    $description = mysqli_real_escape_string($db['mysqli'], $description);
    $price = mysqli_real_escape_string($db['mysqli'], $price);
    $sql = "
        INSERT INTO 
        vk.order(name, description, creator_user_id, done, price) 
        VALUES ('$name', '$description', $customerId, 0, $price)
    ";

    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        return null;
    }

    $lastId = mysqli_insert_id($db['mysqli']);
    if (!is_int($lastId) || $lastId === 0) {
        return null;
    }

    return $lastId;
}

/**
 * @param     $db
 * @param int $after
 * @param int $limit
 *
 * @return array|false Array of orders or FALSE on failure
 */
function Store_GetOrders_NotDone($db, int $after, int $limit)
{
    $sql = "SELECT id, name, description, creator_user_id, price, done FROM vk.order WHERE done = 0";
    if ($after) {
        $sql .= " AND id < $after";
    }
    $sql .= " ORDER BY id DESC LIMIT $limit";

    $result = mysqli_query($db['mysqli'], $sql);
    if ($result === false) {
        return false;
    }

    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    if ($orders === null) {
        $orders = [];
    }
    foreach ($orders as &$order) {
        $order['create_user_id'] = (int)$order['create_user_id'];
        $order['done'] = (bool)$order['done'];
    }

    return $orders;
}

/**
 * Return info about order.
 *
 * @param     $db
 * @param int $id
 *
 * @return array|false|null Info about order, null if order not found, false if storage error
 */
function Store_GetOrder($db, int $id)
{
    $sql = "SELECT id, name, description, creator_user_id, price, done FROM vk.order WHERE id = $id";
    $result = mysqli_query($db['mysqli'], $sql);
    if ($result === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }
    $order = mysqli_fetch_assoc($result);

    if (!empty($order)) {
        $order['done'] = (bool)$order['done'];
        $order['creator_user_id'] = (int)$order['creator_user_id'];
    }

    return $order;
}

/**
 * @param      $db
 * @param int  $orderId
 * @param bool $done
 *
 * @return bool
 */
function Store_UpdateOrderDone($db, int $orderId, bool $done): bool
{
    $done = (int)$done;
    $sql = "UPDATE vk.order SET done = $done WHERE id = $orderId";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        return false;
    }

    return true;
}
