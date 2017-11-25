<?php

function Api_Router(string $method, string $url): string
{
    if ($method == "POST" && $url == "/orders") {
        return 'Api_HandleOrderCreate';
    } elseif ($method == "GET" && $url == "/orders") {
        return 'Api_HandleOrdersList';
    } elseif ($method == "GET" && preg_match("/^\/orders\\/\d+$/", $url)) {
        return 'Api_HandleOrder';
    } elseif ($method == "POST" && preg_match("/^\\/orders\\/\d+\\/exec$/", $url)) {
        return 'Api_HandleOrder_Execute';
    } elseif ($method == "POST" && $url == "/auth") {
        return 'Api_HandleAuth';
    } elseif ($method == "GET" && $url == "/users/me") {
        return 'Api_Handle_Users_Me';
    } else {
        return '';
    }
}
