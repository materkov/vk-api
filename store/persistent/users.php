<?php

require_once __DIR__ . '/../errors.php';
require_once __DIR__ . '/users_cache.php';

/**
 * @param        $db
 * @param int    $userId
 * @param string $balance
 *
 * @return bool
 */
function Store_SaveUserBalance(&$db, int $userId, string $balance): bool
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $balance = mysqli_real_escape_string($db['mysqli'], $balance);
    $sql = "UPDATE vk.user SET balance = '$balance' WHERE id = $userId";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        return false;
    }

    Store_UsersCache_Invalidate($db, $userId);

    return true;
}

/**
 * @param        $db
 * @param string $username
 * @param string $passwordHash
 * @param bool   $canCreateOrder
 * @param bool   $canExecuteOrder
 *
 * @return int|bool User ID or FALSE on failure
 */
function Store_CreateUser(&$db, string $username, string $passwordHash, bool $canCreateOrder, bool $canExecuteOrder)
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $usernameEscaped = mysqli_real_escape_string($db['mysqli'], $username);
    $passwordHashEscaped = mysqli_real_escape_string($db['mysqli'], $passwordHash);
    $canCreateOrderInt = (int)$canCreateOrder;
    $canExecuteOrderInt = (int)$canExecuteOrder;

    $sql = "
        INSERT INTO vk.user(username, password_hash, balance, can_create_order, can_execute_order) 
        VALUES ('$usernameEscaped', '$passwordHashEscaped', '0.0', $canCreateOrderInt, $canExecuteOrderInt)
    ";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(sprintf("%s: %s", mysqli_errno($db['mysqli']), mysqli_error($db['mysqli'])));
        return false;
    }

    $lastId = mysqli_insert_id($db['mysqli']);
    if (!is_int($lastId) || $lastId <= 0) {
        Store_SetLastError("Invalid last insert id");
        return false;
    }

    Store_UsersCache_Set($db, $lastId, [
        'id' => $lastId,
        'username' => $username,
        'password_hash' => $passwordHash,
        'balance' => '0.0',
        'can_creaate_order' => $canCreateOrder,
        'can_execute_order' => $canExecuteOrder,
    ]);

    return $lastId;
}

function Store_GetUser_Do(&$db, $sql)
{
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(sprintf("%s: %s", mysqli_errno($db['mysqli']), mysqli_error($db['mysqli'])));
        return false;
    }

    $res = mysqli_fetch_assoc($res);
    if ($res) {
        $res['id'] = (int)$res['id'];
        $res['can_create_order'] = (bool)$res['can_create_order'];
        $res['can_execute_order'] = (bool)$res['can_execute_order'];
    }
    return $res;
}

/**
 * @param        $db
 * @param string $username
 *
 * @return array|null|false User info, NULL if not found, FALSE on storage error
 */
function Store_GetUserByUsername(&$db, string $username)
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $username = mysqli_real_escape_string($db['mysqli'], $username);
    return Store_GetUser_Do(
        $db,
        "
            SELECT id, username, password_hash, balance, can_create_order, can_execute_order 
            FROM vk.user WHERE username = '$username'
        "
    );
}

/**
 * @param     $db
 * @param int $id
 *
 * @return array|null|false User info, NULL if not found, FALSE on storage error
 */
function Store_GetUserById(&$db, int $id)
{
    $cacheResult = Store_UsersCache_Get($db, $id);
    if ($cacheResult !== null) {
        return $cacheResult;
    }

    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $user = Store_GetUser_Do(
        $db,
        "
            SELECT id, username, password_hash, balance, can_create_order, can_execute_order 
            FROM vk.user WHERE id = $id
        "
    );

    Store_UsersCache_Set($db, $user['id'], $user);
    return $user;
}
