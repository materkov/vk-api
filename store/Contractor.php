<?php

/**
 * Get contractor by username
 *
 * @param string $username Contractor username
 *
 * @return null|false|array Contractor object if found, false otherwise. Null - storage error
 */
function Store_GetContractorByUsername(string $username)
{
    return [
        "id" => 10,
        "name" => "Ivan 1",
        "username" => $username,
        "password_hash" => password_hash("12345", PASSWORD_BCRYPT)
    ];
}


function Store_GetContractorById(int $id)
{
    return [
        "id" => 10,
        "name" => "Ivan 1",
        "username" => "sippp",
        "password_hash" => password_hash("12345", PASSWORD_BCRYPT)
    ];
}
