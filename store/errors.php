<?php

const STORAGE_OK = 0;
const STORAGE_ERR_GENERAL = 1;
const STORAGE_ERR_TRANSACTION_EXISTS = 2;
const STORAGE_ERR_USER_NOT_FOUND = 3;
const STORAGE_ERR_ORDER_NOT_FOUND = 4;
const STORAGE_ERR_TRANSACTION_NOT_FOUND = 4;

global $lastError;

function Store_SetLastError($err)
{
    global $lastError;
    $lastError = $err;
}

function Store_GetLastError()
{
    global $lastError;
    return "storage error: " . $lastError;
}
