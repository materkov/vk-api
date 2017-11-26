<?php

require_once __DIR__ . '/../errors.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/transaction.php';

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
    $res = mysqli_real_connect($mysqli, getenv('DB_HOST'), getenv('DB_USER'), getenv('DB_PASS'), 'vk', getenv('DB_PORT'));
    if (!$res) {
        Store_SetLastError(sprintf("%s: %s", mysqli_connect_errno(), mysqli_connect_error()));
        return false;
    }

    $db['mysqli'] = $mysqli;
    return true;
}
