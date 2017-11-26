<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../app/app.php';
require_once __DIR__ . '/../../store/memory/memory.php';

function Test_App_GetUser()
{
    echo "Test_App_GetUser...\n";

    $app = ['db' => Store_New_Memory()];
    $userId = Store_AddUser($app['db'], "user1", "", false, false);

    TestCase("user not found", function () use (&$app) {
        $res = App_GetUser($app, 0);
        if ($res !== null) {
            die("res should be NULL, got $res");
        }
    });

    TestCase("user found", function () use (&$app, $userId) {
        $res = App_GetUser($app, $userId);
        if ($res === null) {
            die("res should be not NULL, got $res");
        } elseif ($res['id'] !== $userId) {
            die("res id should be $userId, got: {$res['id']}");
        }
    });

    echo "[OK] All tests passed\n";
}

function Test_App_GetToken()
{
    echo "Test_App_GetToken...\n";

    $app = ['db' => Store_New_Memory()];
    Store_AddUser($app['db'], "user1", password_hash("123", PASSWORD_BCRYPT), false, false);

    TestCase("user not found", function () use (&$app) {
        $res = App_GetToken($app, "", "");
        if ($res !== null) {
            die("res should be APP_ERR_BAD_CREDENTIALS, got $res");
        }
    });

    TestCase("invalid password found", function () use (&$app) {
        $res = App_GetToken($app, "user1", "1234");
        if ($res !== null) {
            die("res should be APP_ERR_BAD_CREDENTIALS, got $res");
        }
    });

    echo "[OK] All tests passed\n";
}

function Test_App_CreateUser()
{
    echo "Test_App_GetToken...\n";

    $app = ['db' => Store_New_Memory()];
    $userId = Store_AddUser($app['db'], "user1", password_hash("123", PASSWORD_BCRYPT), false, false);

    TestCase("empty username", function () use (&$app) {
        $res = App_CreateUser($app, "", "", false, false, $userId);
        if ($res !== APP_ERR_BAD_USERNAME) {
            die("res should be APP_ERR_BAD_USERNAME, got $res");
        }
    });

    TestCase("long username", function () use (&$app) {
        $res = App_CreateUser($app, str_repeat("1", 300), "", false, false, $userId);
        if ($res !== APP_ERR_BAD_USERNAME) {
            die("res should be APP_ERR_BAD_USERNAME, got $res");
        }
    });

    TestCase("empty password", function () use (&$app) {
        $res = App_CreateUser($app, "1", "", false, false, $userId);
        if ($res !== APP_ERR_BAD_PASSWORD) {
            die("res should be APP_ERR_BAD_PASSWORD, got $res");
        }
    });

    TestCase("bad permissions", function () use (&$app) {
        $res = App_CreateUser($app, "1", "1", false, false, $userId);
        if ($res !== APP_ERR_BAD_PERMISSIONS) {
            die("res should be APP_ERR_BAD_PERMISSIONS, got $res");
        }
    });

    TestCase("duplicate user", function () use (&$app) {
        $res = App_CreateUser($app, "user1", "1", false, true, $userId);
        if ($res !== APP_ERR_USERNAME_REGISTERED) {
            die("res should be APP_ERR_USERNAME_REGISTERED, got $res");
        }
    });

    TestCase("normal flow", function () use (&$app) {
        $res = App_CreateUser($app, "user2", "1", false, true, $userId);
        if ($res !== APP_OK) {
            die("res should be APP_OK, got $res");
        }

        $newUser = Store_GetUserByUsername($app['db'], "user2");
        if (!password_verify("1", $newUser['password_hash'])) {
            die("Password 1 should match to new user");
        } elseif ($newUser['username'] !== "user2") {
            die("User should have user2 username, got: {$newUser['username']}");
        }
    });

    echo "[OK] All tests passed\n";
}


Test_App_GetUser();
Test_App_GetToken();
Test_App_CreateUser();
