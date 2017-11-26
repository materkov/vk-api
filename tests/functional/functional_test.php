<?php

function RegisterUser($username)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/register");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"username":"'.$username.'","password":"12345","can_create_order": true, "can_execute_order": true}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    echo "$res\n";
    curl_close($ch);

    return json_decode($res, true)['id'];
}

function Login($username)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/auth");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"username":"'.$username.'","password":"12345"}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    echo "$res\n";
    curl_close($ch);

    return json_decode($res, true)['token'];
}

function GetMyInfo($token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/users/me");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer '.$token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    echo "$res\n";
    curl_close($ch);

    return json_decode($res, true);
}

function CreateOrder($token)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/orders");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '{"name":"order1","price":123.12,"description":"order2"}');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    echo "$res\n";
    curl_close($ch);

    return json_decode($res, true)['id'];
}

function ExecuteOrder($token, $orderId)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/orders/$orderId/exec");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    echo "$res\n";
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return $status;
}

function OrdersList()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/orders?limit=4");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    echo "$res\n";
    curl_close($ch);

    return json_decode($res, true);
}

function OrderInfo($orderId)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,"http://localhost:8000/orders/$orderId");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    curl_close($ch);
    echo "$res\n";

    return json_decode($res, true);
}

$username = uniqid("", true);
echo "Registering user $username\n";
$userId = RegisterUser($username);
echo "User ID: $userId\n";

echo "Logins as user...\n";
$token = Login($username);
echo "Token: $token\n";

echo "Getting user info...\n";
$info = GetMyInfo($token);

echo "Creating order...\n";
$orderId = CreateOrder($token);
echo "Order ID: $orderId\n";

echo "Getting orders list...\n";
$ordersList = OrdersList();
echo "Last Order ID: {$ordersList['orders'][0]['id']}\n";

echo "Getting order info...\n";
$orderInfo = OrderInfo($orderId);
echo "Info order id: {$orderInfo['id']}\n";

echo "Executing order...\n";
$status = ExecuteOrder($token, $orderId);
echo "Executed order, HTTP status: $status\n";

