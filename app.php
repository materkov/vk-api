<?php

require_once __DIR__ . '/api/Router.php';
require_once __DIR__ . '/api/Api.php';
require_once __DIR__ . '/app/App.php';

$app = App_Init();
$body = [];
$method = $_SERVER['REQUEST_METHOD'];
$url = strtok($_SERVER["REQUEST_URI"],'?');
$args = $_GET;
if ($method == "POST") {
    $body = json_decode(file_get_contents('php://input'), true);
}

header('Access-Control-Allow-Origin: ' . (empty($_SERVER['HTTP_ORIGIN']) ? "*" : $_SERVER['HTTP_ORIGIN']));
header('Access-Control-Allow-Headers: Accept, Authorization, Origin, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, PATCH, DELETE');
header('Access-Control-Max-Age: 604800');
header('Content-Type: application/json');

if ($method == "OPTIONS") {
    exit(0);
}

$funcName = Api_Router($method, $url);
$auth = Api_Middleware_Auth($_SERVER['HTTP_AUTHORIZATION'] ?? '');
if (!$funcName) {
    http_response_code(404);
    echo json_encode([
        'error' => 'route_not_found',
        'error_description' => sprintf('Url %s %s not found', $method, $url)
    ], JSON_PRETTY_PRINT);
} else {
    list($responseStatus, $responseBody) = $funcName($app, $auth, $url, $args, $body);
    http_response_code($responseStatus);
    echo json_encode($responseBody, JSON_PRETTY_PRINT);
}
