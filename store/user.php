<?php


/**
 * @param        $db
 * @param int    $userId
 * @param string $balance
 *
 * @return bool
 */
function Store_SaveUserBalance($db, int $userId, string $balance): bool
{
    $balance = mysqli_real_escape_string($db['mysqli'], $balance);
    $sql = "UPDATE vk.user SET balance = '$balance' WHERE id = $userId";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        return false;
    }

    return true;
}

function Store_CreateUser($db, string $username, string $passwordHash)
{
    $username = mysqli_real_escape_string($db['mysqli'], $username);
    $passwordHash = mysqli_real_escape_string($db['mysqli'], $passwordHash);
    $sql = "INSERT INTO vk.user(username, password_hash) VALUES ('$username', '$passwordHash')";
    mysqli_query($db['mysqli'], $sql);
    return mysqli_insert_id($db['mysqli']);
}

function Store_GetUser_Do($db, $sql)
{
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
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
 * @return array|null|false User info, null if not found, false on storage error
 */
function Store_GetUserByUsername($db, string $username)
{
    $username = mysqli_real_escape_string($db['mysqli'], $username);
    return Store_GetUser_Do($db, "SELECT * FROM vk.user WHERE username = '$username'");
}

/**
 * @param     $db
 * @param int $id
 *
 * @return array|null|false User info, null if not found, false on storage error
 */
function Store_GetUserById($db, int $id)
{
    return Store_GetUser_Do($db, "SELECT * FROM vk.user WHERE id = $id");
}
