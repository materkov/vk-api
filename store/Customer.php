<?php

function Store_GetCustomerById($db, int $id): array
{
    $sql = "SELECT id, login, name, password_hash FROM vk.order WHERE id = $id";

    $result = mysqli_query($db['mysqli'], $sql);
    $customer = mysqli_fetch_row($result);
    mysqli_free_result($result);

    return $customer;
}
