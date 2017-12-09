<?php

require_once __DIR__ . '/../errors.php';

const MysqlErrorDuplicateEntry = 1062;

function Store_FlushTransactions(&$db)
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    mysqli_query($db['mysqli'], "DELETE FROM vk.user");
    mysqli_query($db['mysqli'], "DELETE FROM vk.order");
    mysqli_query($db['mysqli'], "DELETE FROM vk.transaction");

    Store_Cache_Truncate($db);
}

/**
 * @param        $db
 * @param int    $orderId
 * @param string $orderSum
 * @param int    $contractorId
 *
 * @return int STORAGE_OK, STORAGE_ERROR or TRANSACTION_EXISTS
 *
 */
function Store_CreateTransaction(&$db, int $orderId, string $orderSum, int $contractorId): int
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $sql = "INSERT INTO vk.transaction (order_id, sum, finished, user_id, balance) 
        VALUES
        (
            $orderId, $orderSum, 0, $contractorId, 
            (
                SELECT old_balance
                FROM (
                    SELECT COALESCE(
                        (SELECT balance FROM vk.transaction WHERE user_id = $contractorId ORDER BY id DESC LIMIT 1),
                        0
                    ) AS old_balance
                ) AS old_balance
            ) + $orderSum
        );
    ";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        $errNo = mysqli_errno($db['mysqli']);
        if ($errNo == MysqlErrorDuplicateEntry) {
            return STORAGE_ERR_TRANSACTION_EXISTS;
        } else {
            Store_SetLastError(mysqli_error($db['mysqli']));
            return STORAGE_ERR_GENERAL;
        }
    }

    return 0;
}

/**
 * @param     $db
 * @param int $orderId
 *
 * @return bool
 */
function Store_FinishTransaction(&$db, int $orderId): bool
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $sql = "UPDATE vk.transaction SET finished = 1 WHERE order_id = $orderId";
    $result = mysqli_query($db['mysqli'], $sql);
    if ($result === false) {
        return false;
    }

    return true;
}

/**
 * @param     $db
 * @param int $userId
 *
 * @return string|null User balance, false on error
 */
function Store_GetTransactionUserBalance(&$db, int $userId): ?string
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $sql = "
        SELECT COALESCE(
            (SELECT balance FROM vk.transaction WHERE user_id = $userId ORDER BY id DESC LIMIT 1),
            '0.00'
        ) as balance
     ";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }

    $res = mysqli_fetch_assoc($res);
    return $res['balance'] ?? '0.0';
}

function Store_GetTransactionByOrderId(&$db, int $orderId)
{
    if (!Store_Connect_MySQL($db)) {
        return false;
    }

    $sql = "SELECT * FROM vk.transaction WHERE order_id = $orderId";
    $res = mysqli_query($db['mysqli'], $sql);
    $res = mysqli_fetch_assoc($res);
    if (!empty($res)) {
        $res['finished'] = (bool)((int)$res['finished']);
    }

    return $res;
}
