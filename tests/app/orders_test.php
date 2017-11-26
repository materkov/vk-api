<?php

require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../app/app.php';
require_once __DIR__ . '/../../store/memory/memory.php';

function Test_App_CreateOrder()
{
    echo "Test_App_CreateOrder...\n";

    $orderId = 0;
    $app = ['db' => Store_New_Memory()];
    $user1Id = Store_AddUser($app['db'], 'user', '', false, false);
    $user2Id = Store_AddUser($app['db'], 'user-can-add', '', true, false);

    TestCase("empty order name", function() use ($orderId, $app) {
        $res = App_CreateOrder($app, 0, "", "", "", $orderId);
        if ($res != APP_ERR_BAD_ORDER_NAME) {
            die("res should be APP_ERR_BAD_ORDER_NAME, got $res");
        }
    });

    TestCase("long order name", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, str_repeat("1", 300), "", "", $orderId);
        if ($res != APP_ERR_BAD_ORDER_NAME) {
            die("res should be APP_ERR_BAD_ORDER_NAME, got $res");
        }
    });

    TestCase("empty order description", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", "", "", $orderId);
        if ($res != APP_ERR_BAD_ORDER_DESC) {
            die("res should be APP_ERR_BAD_ORDER_DESC, got $res");
        }
    });

    TestCase("long order description", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", str_repeat("1", 15000), "", $orderId);
        if ($res != APP_ERR_BAD_ORDER_DESC) {
            die("res should be APP_ERR_BAD_ORDER_DESC, got $res");
        }
    });

    TestCase("empty price", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", "1", "", $orderId);
        if ($res != APP_ERR_BAD_ORDER_PRICE) {
            die("res should be APP_ERR_BAD_ORDER_PRICE, got $res");
        }
    });

    TestCase("3 digits price", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", "1", "1.123", $orderId);
        if ($res != APP_ERR_BAD_ORDER_PRICE) {
            die("res should be APP_ERR_BAD_ORDER_PRICE, got $res");
        }
    });

    TestCase("long price", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", "1", "1234567890111.12", $orderId);
        if ($res != APP_ERR_BAD_ORDER_PRICE) {
            die("res should be APP_ERR_BAD_ORDER_PRICE, got $res");
        }
    });

    TestCase("small price", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", "1", "0.01", $orderId);
        if ($res != APP_ERR_BAD_ORDER_PRICE) {
            die("res should be APP_ERR_BAD_ORDER_PRICE, got $res");
        }
    });

    TestCase("price equal to commission", function() use ($orderId) {
        $res = App_CreateOrder($app, 0, "1", "1", "0.12", $orderId);
        if ($res != APP_ERR_BAD_ORDER_PRICE) {
            die("res should be APP_ERR_BAD_ORDER_PRICE, got $res");
        }
    });

    TestCase("user not found", function() use ($orderId, $app) {
        $res = App_CreateOrder($app, 0, "1", "1", "10.00", $orderId);
        if ($res != APP_ERR_BAD_USER) {
            die("res should be APP_ERR_BAD_USER, got $res");
        }
    });

    TestCase("user cannot create orders", function() use ($orderId, $app, $user1Id) {
        $res = App_CreateOrder($app, $user1Id, "1", "1", "10.00", $orderId);
        if ($res != APP_ERR_USER_CANT_CREATE_ORDER) {
            die("res should be APP_ERR_USER_CANT_CREATE_ORDER, got $res");
        }
    });

    TestCase("normal flow", function() use ($orderId, $app, $user2Id) {
        $res = App_CreateOrder($app, $user2Id, "1", "2", "10.00", $orderId);
        if ($res != APP_OK) {
            die("res should be APP_OK, got $res");
        }

        $order = Store_GetOrder($app['db'], $orderId);
        if ($order['name'] !== "1") {
            die("Order name should be 1, got {$order['name']}");
        }
        if ($order['description'] !== "2") {
            die("Order description should be 2, got {$order['description']}");
        }
        if ($order['price'] !== "10.00") {
            die("Order price should be 10.00, got {$order['price']}");
        }
        if ($order['creator_user_id'] !== $user2Id) {
            die("Order creator_user_id should be $user2Id, got {$order['creator_user_id']}");
        }
        if ($order['done'] !== 0) {
            die("Order done should be 0, got {$order['done']}");
        }
    });

    echo "[OK] All tests passed\n";
}

function Test_App_OrdersList()
{
    echo "Test_App_OrdersList...\n";

    $app = ['db' => Store_New_Memory()];
    Store_AddUser($app['db'], 'user', '', false, false);
    Store_AddUser($app['db'], 'user-can-add', '', true, false);
    Store_CreateOrder($app['db'], "1.00", 10, "1", "2");
    Store_CreateOrder($app['db'], "2.00", 11, "3", "4");
    Store_CreateOrder($app['db'], "3.00", 12, "5", "6");


    $orders = App_OrdersList($app, 0, 2);
    if (count($orders) != 2) {
        die("orders length should be 2, got " . count($orders));
    }

    echo "[OK] All tests passed\n";
}

function Test_App_GetOrder()
{
    echo "Test_App_GetOrder...\n";

    $orderId = 0;
    $app = ['db' => Store_New_Memory()];
    $id = Store_CreateOrder($app['db'], "1.00", 10, "1", "2");

    $order = App_GetOrder($app, $id);
    if ($order['id'] !== $id) {
        die("order id should be $id, got {$order['id']}");
    }

    echo "[OK] All tests passed\n";
}

function TestApp_Order_Execute()
{
    echo "Test_App_GetOrder...\n";

    $app = ['db' => Store_New_Memory()];
    $user1Id = Store_AddUser($app['db'], "user1", "", false, false);
    $user2Id = Store_AddUser($app['db'], "user2", "", false, true);
    $order1Id = Store_CreateOrder($app['db'], "1.00", 10, "1", "2");
    $order2Id = Store_CreateOrder($app['db'], "1.00", 10, "1", "2");
    Store_UpdateOrderDone($app['db'], $order2Id, true);

    TestCase("normal flow", function () use (&$app, $user2Id, $order1Id) {
        $res = App_Order_Execute($app, $user2Id, $order1Id);
        if ($res != APP_OK) {
            die("res should be APP_OK, got $res");
        }

        $trans = Store_GetTransactionByOrderId($app['db'], $order1Id);
        if ($trans['order_id'] !== $order1Id) {
            die("transaction order_id should be $order1Id, got {$trans['order_id']}");
        }
        if ($trans['sum'] !== '0.88') {
            die("transaction sum should be 0.88, got {$trans['sum']}");
        }
        if ($trans['finished'] !== true) {
            die("transaction finished should be true, got {$trans['finished']}");
        }

        $balance = Store_GetTransactionUserBalance($app['db'], $user2Id);
        if ($balance !== "0.88") {
            die("user transaction balance should be 0.88, got {$balance}");
        }

        $order = Store_GetOrder($app['db'], $order1Id);
        if ($order['done'] !== true) {
            die("order done should be true, got {$order['done']}");
        }

        $user = Store_GetUserById($app['db'], $user2Id);
        if ($user['balance'] !== "0.88") {
            die("user balance should be 0.88, got {$user['balance']}");
        }
    });

    TestCase("bad user", function () use ($app) {
        $res = App_Order_Execute($app, 0, 0);
        if ($res != APP_ERR_BAD_USER) {
            die("res should be APP_ERR_BAD_USER, got $res");
        }
    });

    TestCase("user cannot execute orders", function () use (&$app, $user1Id) {
        $res = App_Order_Execute($app, $user1Id, 0);
        if ($res != APP_ERR_USER_CANT_EXECUTE_ORDER) {
            die("res should be APP_ERR_USER_CANT_EXECUTE_ORDER, got $res");
        }
    });

    TestCase("bad order", function () use (&$app, $user2Id) {
        $res = App_Order_Execute($app, $user2Id, 0);
        if ($res != APP_ERR_BAD_ORDER) {
            die("res should be APP_ERR_BAD_ORDER, got $res");
        }
    });

    TestCase("order already executed", function () use (&$app, $user2Id, $order2Id) {
        $res = App_Order_Execute($app, $user2Id, $order2Id);
        if ($res != APP_ERR_ORDER_ALREADY_EXECUTED) {
            die("res should be APP_ERR_ORDER_ALREADY_EXECUTED, got $res");
        }
    });

    TestCase("transaction already created", function () use (&$app, $user2Id, $order1Id) {
        Store_UpdateOrderDone($app['db'], $order1Id, false);
        $res = App_Order_Execute($app, $user2Id, $order1Id);
        if ($res != APP_ERR_ORDER_ALREADY_EXECUTED) {
            die("res should be APP_ERR_ORDER_ALREADY_EXECUTED, got $res");
        }

        $order = Store_GetOrder($app['db'], $order1Id);
        if ($order['done'] !== true) {
            die("order done should be true, got {$order['done']}");
        }
    });

    echo "[OK] All tests passed\n";
}

echo "Executing tests/app/orders_test.php\n";

Test_App_CreateOrder();
Test_App_OrdersList();
Test_App_GetOrder();
TestApp_Order_Execute();
