<?php

function Api_Router(string $method, string $url): string
{
    if ($method == "POST" && $url == "/orders") {
        return 'Api_Handle_Order_Create';
    } elseif ($method == "GET" && $url == "/orders") {
        return 'Api_Handle_Order_List';
    } elseif ($method == "GET" && preg_match("/^\/orders\\/\d+$/", $url)) {
        return 'Api_Handle_Order_Details';
    } elseif ($method == "POST" && preg_match("/^\\/orders\\/\d+\\/exec$/", $url)) {
        return 'Api_Handle_Order_Execute';
    } elseif ($method == "POST" && $url == "/auth") {
        return 'Api_Handle_Auth';
    } elseif ($method == "GET" && $url == "/users/me") {
        return 'Api_Handle_Users_Me';
    } elseif ($method == "POST" && $url == "/register") {
        return 'Api_Handle_Register';
    } else {
        return '';
    }
}
