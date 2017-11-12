<?php

function Api_Router(string $method, string $url): string
{
    if ($method == "POST" && $url == "/orders") {
        return 'Api_HandleOrderCreate';
    } else if ($method == "GET" && $url == "/orders") {
        return 'Api_HandleOrdersList';
    } else if ($method == "GET" && preg_match("/^\/orders\\/\d+$/", $url)) {
        return 'Api_HandleOrder';
    } else if ($method == "POST" && preg_match("/^\\/orders\\/\d+\\/exec$/", $url)) {
        return 'Api_HandleOrder_Execute';
    } else if ($method == "POST" && $url == "/auth/contractor") {
        return 'Api_HandleAuth_Contractor';
    } else if ($method == "GET" && $url == "/contractors/me") {
        return 'Api_HandleContractor_Me';
    } else {
        return '';
    }
}
