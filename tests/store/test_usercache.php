<?php

require_once __DIR__ . '/../../store/persistent/persistent.php';

function TestUserCache(&$db)
{
    $userName = uniqid();
    $userId = Store_CreateUser($db, $userName, "hash", true, false);
    $cacheData = json_decode($db['redis']->get("users:$userId"), true);
    if ($cacheData['id'] != $userId) {
        die("Expected cacheData.id to be $userId, got {$cacheData['id']}");
    }

    mysqli_query($db['mysqli'], "DELETE FROM vk.user WHERE id = $userId");

    $userFromCache = Store_GetUserById($db, $userId);
    if ($userFromCache['id'] !== $userId) {
        die("Expected userFromCache.id to be $userId, got {$cacheData['id']}");
    }

    Store_SaveUserBalance($db, $userId, '12.33');

    $cacheData = $db['redis']->get("users:$userId");
    if ($cacheData !== false) {
        die("Expected cache to be invalidated");
    }
}

echo "Starting test_cache.php\n";
$start = microtime(true);
$db = [];

TestUserCache($db);

echo "[OK] Passed, " . (microtime(true) - $start)*1000 . "ms\n";
