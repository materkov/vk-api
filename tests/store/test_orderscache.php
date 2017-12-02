<?php

require_once __DIR__ . '/../../store/persistent/persistent.php';

function TestOrdersCache(&$db)
{
    $orderId = Store_CreateOrder($db, '10.22', 13, 'test', 'test');
    $cacheData = json_decode($db['redis']->get("orders:$orderId"), true);
    if ($cacheData['id'] != $orderId) {
        die("Expected cacheData.id to be $orderId, got {$cacheData['id']}");
    }

    mysqli_query($db['mysqli'], "DELETE FROM vk.order WHERE id = $orderId");

    $orderFromCache = Store_GetOrder($db, $orderId);
    if ($orderFromCache['id'] !== $orderId) {
        die("Expected orderFromCache.id to be $orderId, got {$orderFromCache['id']}");
    }

    Store_UpdateOrderDone($db, $orderId, true);

    $cacheData = $db['redis']->get("orders:$orderId");
    if ($cacheData !== false) {
        die("Expected cache to be invalidated");
    }
}

function TestOrdersListCache(&$db)
{
    Store_FlushTransactions($db);
    $order1Id = Store_CreateOrder($db, '10.22', 13, 'test1', 'test');
    $order2Id = Store_CreateOrder($db, '10.22', 13, 'test2', 'test');
    $order3Id = Store_CreateOrder($db, '10.22', 13, 'test3', 'test');

    $orders = Store_GetOrders_NotDone($db, 0, 2);
    $cacheData = json_decode($db['redis']->hget("orders_list", "0:2"), true);
    if ($cacheData[0]['id'] != $order3Id || $cacheData[1]['id'] != $order2Id) {
        die("Expected cacheData[0].id to be $order3Id, and cacheData[1].id to be $order2Id");
    }

    $order4Id = Store_CreateOrder($db, '10.22', 13, 'test3', 'test');

    $cacheData = $db['redis']->hgetall("orders_list");
    if ($cacheData !== []) {
        die("Expected cache to be invalidated");
    }
}

echo "Starting test_cache.php\n";
$start = microtime(true);
$db = [];

TestOrdersCache($db);
TestOrdersListCache($db);

echo "[OK] Passed, " . (microtime(true) - $start)*1000 . "ms\n";
