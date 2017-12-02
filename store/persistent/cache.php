<?php

const REDIS_CONNECT_TIMEOUT = 5.0;

function Store_Cache_Connect(&$db)
{
    if (isset($db['redis'])) {
        return true;
    }

    $redis = new \Redis();
    $res = $redis->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT'), REDIS_CONNECT_TIMEOUT);
    if ($res) {
        $db['redis'] = $redis;
    }

    return $res;
}

function Store_Cache_Truncate(&$db)
{
    if (!Store_Cache_Connect($db)) {
        return null;
    }

    $db['redis']->flushDb();
}
