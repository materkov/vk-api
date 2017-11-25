<?php

require_once __DIR__ . '/../errors.php';

function Store_FlushTransactions($db)
{
    mysqli_query($db['mysqli'], "TRUNCATE TABLE vk.user");
    mysqli_query($db['mysqli'], "TRUNCATE TABLE vk.transaction");
    mysqli_query($db['mysqli'], "TRUNCATE TABLE vk.transaction_user_balance");
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
function Store_CreateTransaction($db, int $orderId, string $orderSum, int $contractorId): int
{
    Store_SetLastError(null);

    $res = mysqli_begin_transaction($db['mysqli']);
    if ($res === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return STORAGE_ERR_GENERAL;
    }

    $sql = "INSERT IGNORE INTO vk.transaction(order_id, `sum`, finished) VALUES ($orderId, $orderSum, 0)";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return STORAGE_ERR_GENERAL;
    }

    $affectedRows = mysqli_affected_rows($db['mysqli']);
    if ($affectedRows === 0) {
        mysqli_rollback($db['mysqli']);
        return STORAGE_ERR_TRANSACTION_EXISTS;
    } elseif ($affectedRows === -1) {
        mysqli_rollback($db['mysqli']);
        return STORAGE_ERR_GENERAL;
    }

    $sql = "
        INSERT INTO vk.transaction_user_balance(user_id, balance) VALUES ($contractorId, $orderSum) 
        ON DUPLICATE KEY UPDATE balance = balance + $orderSum
    ";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        mysqli_rollback($db['mysqli']);
        Store_SetLastError(mysqli_error($db['mysqli']));
        return STORAGE_ERR_GENERAL;
    }

    $res = mysqli_commit($db['mysqli']);
    if ($res === false) {
        mysqli_rollback($db['mysqli']);
        Store_SetLastError(mysqli_error($db['mysqli']));
        return STORAGE_ERR_GENERAL;
    }

    return 0;
}

/**
 * @param     $db
 * @param int $orderId
 *
 * @return bool
 */
function Store_FinishTransaction($db, int $orderId): bool
{
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
function Store_GetTransactionUserBalance($db, int $userId): ?string
{
    $sql = "SELECT balance FROM vk.transaction_user_balance WHERE user_id = $userId";
    $res = mysqli_query($db['mysqli'], $sql);
    if ($res === false) {
        Store_SetLastError(mysqli_error($db['mysqli']));
        return false;
    }

    $res = mysqli_fetch_assoc($res);
    return $res['balance'] ?? '0.0';
}

function Store_GetTransactionByOrderId($db, int $orderId)
{
    $sql = "SELECT * FROM vk.transaction WHERE order_id = $orderId";
    $res = mysqli_query($db['mysqli'], $sql);
    $res = mysqli_fetch_assoc($res);
    if (!empty($res)) {
        $res['finished'] = (bool)((int)$res['finished']);
    }

    return $res;
}
