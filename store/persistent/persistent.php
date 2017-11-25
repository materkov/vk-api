<?php

require_once __DIR__ . '/../errors.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/transaction.php';

function Store_New_Persistent() {
    $mysqli = mysqli_connect('192.168.33.10', 'root', 'root', 'vk', '3306');
    return $mysqli;
}
