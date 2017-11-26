<?php

require_once __DIR__ . '/errors.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/users.php';

function App_Init(): array
{
    return [
        'db' => [],
        'log_file' => null,
    ];
}

function App_Log(&$app, $entry)
{
    if (!$app['log_file']) {
        $app['log_file'] = fopen('app.log', 'a');
    }
    $entry = sprintf("[%s]: %s\n", date(DATE_RFC822), $entry);
    fwrite($app['log_file'], $entry);
}

function App_CloseLog(&$app)
{
    if ($app['log_file'] !== null) {
        fclose($app['log_file']);
        $app['log_file'] = null;
    }
}
