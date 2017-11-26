<?php
/**
 * Тест, проверяющий что флоу "выполнения заказа" в сторадже является идемпотентным
 * и система обеспечивает eventual consistency. То есть, даже если flow будет прерван
 * на каком либо шаге, при повторном выполнении этих же операций в конечном счете
 * система придет в согласованное состояние.
 *
 * Препполагается, что таблица users находится
 * на одном сервере, таблица orders на втором, таблицы transactions и transactions_user_balance
 * на третьем - то есть обернуть все операции в большую транзакцию нельзя.
 */

require_once __DIR__ . '/../../store/persistent/persistent.php';

$db = [];

function CheckFinal(&$db, $orderId, $orderSum)
{
    $order = Store_GetOrder($db, $orderId);
    if ($order['done'] !== true) {
        die("[FAIL] Order should be done, actual: {$order['done']}");
    }

    $user = Store_GetUserByUsername($db, "user1");
    if ($user['balance'] !== "45.11") {
        die("[FAIL] Balance should be 45.11, actual: {$user['balance']}");
    }

    $trans = Store_GetTransactionByOrderId($db, $orderId);
    if ($trans['finished'] !== true) {
        die("[FAIL] Finished should be 1, actual: {$trans['finished']}");
    }
    if ($trans['sum'] !== $orderSum) {
        die("[FAIL] Sum should be $orderSum, actual: {$trans['sum']}");
    }
}

function Setup(&$db)
{
    Store_FlushTransactions($db);
    $contractorId = Store_CreateUser($db, "user1", "", true, true);
    $orderId = Store_CreateOrder($db, "45.11", $contractorId, "test order", "");
    $order = Store_GetOrder($db, $orderId);

    return [$contractorId, $orderId, $order];
}

function TestNormal(&$db)
{
    [$contractorId, $orderId, $order] = Setup($db);

    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    $balance = Store_GetTransactionUserBalance($db, $contractorId);
    Store_SaveUserBalance($db, $contractorId, $balance);
    Store_UpdateOrderDone($db, $orderId, true);
    Store_FinishTransaction($db, $orderId);

    CheckFinal($db, $orderId, "45.11");
}

function RunFlow(&$db, $orderId, $order, $contractorId)
{
    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    $balance = Store_GetTransactionUserBalance($db, $contractorId);
    Store_SaveUserBalance($db, $contractorId, $balance);
    Store_UpdateOrderDone($db, $orderId, true);
    Store_FinishTransaction($db, $orderId);
}

function Test_Fail_CreateTransaction(&$db)
{
    [$contractorId, $orderId, $order] = Setup($db);

    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    // Fail was here

    RunFlow($db, $orderId, $order, $contractorId);

    CheckFinal($db, $orderId, "45.11");
}

function Test_Fail_GetUserBalance(&$db)
{
    [$contractorId, $orderId, $order] = Setup($db);

    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    $balance = Store_GetTransactionUserBalance($db, $contractorId);
    // Fail was here

    RunFlow($db, $orderId, $order, $contractorId);

    CheckFinal($db, $orderId, "45.11");
}

function Test_Fail_SaveUserBalance(&$db)
{
    [$contractorId, $orderId, $order] = Setup($db);

    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    $balance = Store_GetTransactionUserBalance($db, $contractorId);
    Store_SaveUserBalance($db, $contractorId, $balance);
    // Fail was here

    RunFlow($db, $orderId, $order, $contractorId);

    CheckFinal($db, $orderId, "45.11");
}

function Test_Fail_UpdateOrderDone(&$db)
{
    [$contractorId, $orderId, $order] = Setup($db);

    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    $balance = Store_GetTransactionUserBalance($db, $contractorId);
    Store_SaveUserBalance($db, $contractorId, $balance);
    Store_UpdateOrderDone($db, $orderId, true);
    // Fail was here

    RunFlow($db, $orderId, $order, $contractorId);

    CheckFinal($db, $orderId, "45.11");
}

function Test_Fail_FinishTransaction(&$db)
{
    [$contractorId, $orderId, $order] = Setup($db);

    Store_CreateTransaction($db, $orderId, $order['price'], $contractorId);
    $balance = Store_GetTransactionUserBalance($db, $contractorId);
    Store_SaveUserBalance($db, $contractorId, $balance);
    Store_UpdateOrderDone($db, $orderId, true);
    Store_FinishTransaction($db, $orderId);
    // Fail was here

    RunFlow($db, $orderId, $order, $contractorId);

    CheckFinal($db, $orderId, "45.11");
}

echo "Starting test_transactions.php\n";
$start = microtime(true);

TestNormal($db);
Test_Fail_CreateTransaction($db);
Test_Fail_GetUserBalance($db);
Test_Fail_SaveUserBalance($db);
Test_Fail_UpdateOrderDone($db);
Test_Fail_FinishTransaction($db);

echo "[OK] Passed, " . (microtime(true) - $start)*1000 . "ms\n";
