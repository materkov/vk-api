<?php

$lastError = "";

function SetLastError(string $err)
{
    global $lastError;
    $lastError = $err;
}

function GetLastError()
{
    global $lastError;
    return $lastError;
}
