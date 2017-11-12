<?php

require_once __DIR__ . '/../app/app.php';

const TOKEN_PREFIX = "Bearer ";

function generalError()
{
    return [
        500,
        [
            'error' => 'internal_server_error',
            'error_description' => 'Internal server error. Please, try again later.'
        ]
    ];
}

function authError()
{
    return [
        403,
        [
            'error' => 'auth_error',
            'error_description' => 'Invalid (or expired) auth token'
        ]
    ];
}

function error(string $error, string $errorDescription, int $status = 400)
{
    return [
        $status,
        [
            'error' => $error,
            'error_description' => $errorDescription
        ]
    ];
}

function Api_Middleware_Auth(string $authToken): array
{
    $authData = [
        'contractor_id' => 0
    ];

    if ($authToken == "") {
        return $authData;
    }

    if (strpos($authToken, TOKEN_PREFIX) !== 0 || strlen($authToken) <= strlen(TOKEN_PREFIX)) {
        return $authData;
    }
    $authToken = substr($authToken, strlen(TOKEN_PREFIX));

    $contractorId = App_GetContractorFromToken($authToken);
    if ($contractorId) {
        $authData['contractor_id'] = $contractorId;
    }

    return $authData;
}