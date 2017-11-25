<?php

global $lastError;

function Store_SetLastError($err)
{
    global $lastError;
    $lastError = $err;
}

function Store_GetLastError()
{
    global $lastError;
    return $lastError;
}
