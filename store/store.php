<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/Contractor.php';
require_once __DIR__ . '/Customer.php';
require_once __DIR__ . '/Order.php';
require_once __DIR__ . '/user.php';
require_once __DIR__ . '/transaction.php';

function CreateStorePersistent() {
    $mysqli = mysqli_connect('192.168.33.10', 'root', 'root', 'vk', '3306');
    return $mysqli;
}
