<?php

require_once __DIR__ . '/cache.php';

const ORDER_CACHE_TIMEOUT = 60*60; // 1 hour

function Store_OrdersCache_GetList(&$db, $after, $limit)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $res = $db['redis']->hget("orders_list", "$after:$limit");
    if ($res === false) {
        return null;
    } else {
        return json_decode($res, true);
    }
}

function Store_OrdersCache_InvalidateList(&$db)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $db['redis']->delete("orders_list");
}

function Store_OrdersCache_SetList(&$db, $after, $limit, $value)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $db['redis']->hset("orders_list", "$after:$limit", json_encode($value));
}

function Store_OrdersCache_GetOrder(&$db, $id)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $res = $db['redis']->get("orders:$id");
    if (!$res) {
        return null;
    }

    return json_decode($res, true);
}

function Store_OrdersCache_SetOrder(&$db, $id, $value)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $db['redis']->setex("orders:$id", ORDER_CACHE_TIMEOUT, json_encode($value));
}

function Store_OrdersCache_InvalidateOrder(&$db, $id)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $db['redis']->delete("orders:$id");
}
