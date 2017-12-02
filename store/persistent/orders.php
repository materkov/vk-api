<?php

require_once __DIR__ . '/../errors.php';
require_once  __DIR__ . '/orders_cache.php';

/**
 * @param        $db
 * @param string $price
 * @param int    $customerId
 * @param string $name
 * @param string $description
 *
 * @return int|bool Order ID, FALSE on failure
 */
function Store_CreateOrder(&$db, string $price, int $customerId, string $name, string $description)
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $nameEscaped = mysqli_real_escape_string($db['mysqli'], $name);
    $descriptionEscaped = mysqli_real_escape_string($db['mysqli'], $description);
    $priceEscaped = mysqli_real_escape_string($db['mysqli'], $price);
    $sql = "
        INSERT INTO 
        vk.order(name, description, creator_user_id, done, price) 
        VALUES ('$nameEscaped', '$descriptionEscaped', $customerId, 0, '$priceEscaped')
    ";

    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }

    $lastId = mysqli_insert_id($db['mysqli']);
    if (!is_int($lastId) || $lastId === 0) {
        Store_SetLastError("Invalid last insert id: $lastId");
        return false;
    }

    Store_OrdersCache_SetOrder($db, $lastId, [
        'id' => $lastId,
        'name' => $name,
        'description' => $description,
        'creator_user_id' => $customerId,
        'done' => false,
        'price' => $price
    ]);
    Store_OrdersCache_InvalidateList($db);

    return $lastId;
}

/**
 * @param     $db
 * @param int $after
 * @param int $limit
 *
 * @return array|false Array of orders or FALSE on failure
 */
function Store_GetOrders_NotDone(&$db, int $after, int $limit)
{
    $cacheRes = Store_OrdersCache_GetList($db, $after, $limit);
    if ($cacheRes !== null) {
        return $cacheRes;
    }

    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $sql = "SELECT id, name, description, creator_user_id, price, done FROM vk.order WHERE done = 0";
    if ($after) {
        $sql .= " AND id < $after";
    }
    $sql .= " ORDER BY id DESC LIMIT $limit";

    $result = mysqli_query($db['mysqli'], $sql);
    if ($result === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }

    $orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
    if ($orders === null) {
        $orders = [];
    }
    foreach ($orders as &$order) {
        $order['id'] = (int)$order['id'];
        $order['done'] = (bool)$order['done'];
        $order['creator_user_id'] = (int)$order['creator_user_id'];
    }

    Store_OrdersCache_SetList($db, $after, $limit, $orders);

    return $orders;
}

/**
 * Return info about order.
 *
 * @param     $db
 * @param int $id
 *
 * @return array|false|null Info about order, NULL if order not found, FALSE if storage error
 */
function Store_GetOrder(&$db, int $id)
{
    $cacheRes = Store_OrdersCache_GetOrder($db, $id);
    if ($cacheRes !== null) {
        return $cacheRes;
    }
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $sql = "SELECT id, name, description, creator_user_id, price, done FROM vk.order WHERE id = $id";
    $result = mysqli_query($db['mysqli'], $sql);
    if ($result === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }
    $order = mysqli_fetch_assoc($result);

    if (!empty($order)) {
        $order['id'] = (int)$order['id'];
        $order['done'] = (bool)$order['done'];
        $order['creator_user_id'] = (int)$order['creator_user_id'];
    }

    Store_OrdersCache_SetOrder($db, $order['id'], $order);

    return $order;
}

/**
 * @param      $db
 * @param int  $orderId
 * @param bool $done
 *
 * @return bool
 */
function Store_UpdateOrderDone(&$db, int $orderId, bool $done): bool
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $done = (int)$done;
    $sql = "UPDATE vk.order SET done = $done WHERE id = $orderId";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }

    Store_OrdersCache_InvalidateOrder($db, $orderId);
    Store_OrdersCache_InvalidateList($db);

    return true;
}
