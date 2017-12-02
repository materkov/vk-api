<?php

require_once __DIR__ . '/cache.php';

const USER_CACHE_TIMEOUT = 60*60; // 1 hour

function Store_UsersCache_Get(&$db, $id)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $res = $db['redis']->get("users:$id");
    if ($res === false) {
        return null;
    } else {
        return json_decode($res, true);
    }
}

function Store_UsersCache_Set(&$db, $id, $value)
{
    if (!Store_Cache_Connect($db)) {
        return;
    }

    $db['redis']->setex("users:$id", USER_CACHE_TIMEOUT, json_encode($value) );
}

function Store_UsersCache_Invalidate(&$db, $id)
{
    if (!Store_Cache_Connect($db)) {
        return;
    }

    $db['redis']->delete("users:$id");
}
