<?php

require_once __DIR__ . '/orders.php';

function App_Init(): array
{
    $mysqli = mysqli_connect('192.168.33.10', 'root', 'root', 'vk', '3306');

    return [
        'db' => [
            'mysqli' => $mysqli,
        ]
    ];
}
