<?php

require_once __DIR__ . '/../errors.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/transaction.php';

function Store_New_Persistent() {
    $mysqli = mysqli_connect('localhost', 'root', 'root', 'vk', '3306');
    return $mysqli;
}

/**
 * @param $db
 *
 * @return bool|string TRUE in success, string with error on failure
 */
function Store_Connect_MySQL(&$db) {
    if (isset($db['mysqli'])) {
        return true;
    }

    $mysqli = mysqli_init();
    mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    $res = mysqli_real_connect($mysqli, '127.0.0.1', 'root', 'root', 'vk', '3306');
    if (!$res) {
        Store_SetLastError(sprintf("%s: %s", mysqli_connect_errno(), mysqli_connect_error()));
        return false;
    }

    $db['mysqli'] = $mysqli;
    return true;
}
