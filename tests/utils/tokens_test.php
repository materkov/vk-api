<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../utils/tokens.php';

function Test_Utils_GetUserIdFromToken()
{
    echo "Test_Utils_GetUserIdFromToken...\n";
    $invalidPayloads = [
        '{',
        '{}',
        '{"id": "qwe"}',
        '{"id": "123"}',
        '{"id": 123"}'
    ];
    $invalidTokens = [
        "qwe",
        "qwe.qwe.qwe",
        ".",
        "qwe.",
        ".qwe",
        "qwe." . hash_hmac("sha256", "qwe", "secret"),
        "+*!*@." . hash_hmac("sha256", "+*!*@", "secret"),
        "{}." . hash_hmac("sha256", "qwe", "secret"),
    ];
    foreach ($invalidPayloads as $payload) {
        $invalidTokens[] = base64url_encode($payload) . "." . hash_hmac("sha256", base64url_encode($payload), "secret");
    }

    TestCase("invalid tokens", function () use ($invalidTokens) {
        foreach ($invalidTokens as $token) {
            if (Utils_GetUserIdFromToken($token, "secret") !== null) {
                die("token $token should be invalid");
            }
        }
    });

    TestCase("valid token", function () {
        $payload = '{"id": 235}';
        $token = base64url_encode($payload) . "." . hash_hmac("sha256", base64url_encode($payload), "secret");
        $userId = Utils_GetUserIdFromToken($token, "secret");
        if ($userId !== 235) {
            die("user id should be 235, got $userId");
        }
    });

    echo "[OK] All tests passed\n";
}

Test_Utils_GetUserIdFromToken();
