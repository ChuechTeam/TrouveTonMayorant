<?php
namespace User;

require __DIR__ . "/userDB.php";

define("UE_EMAIL_USED", 1);
define("UE_FIELD_MISSING", 2);

// 0 --> OK
// >0 --> OH NOOO
function register(string $firstname, string $lastname, string $email, string $password, $age): int
{
    if (\UserDB\findByEmail($email) != null) {
        return UE_EMAIL_USED;
    }

    if (empty($firstname)
        || empty($lastname)
        || empty($email)
        || empty($password)
        || empty($age)
    ) {
        return UE_FIELD_MISSING;
    }

    $age = intval($age);
    if ($age < 18) {
        return UE_FIELD_MISSING; // pas le bon error type mais flemme
    }


    \UserDB\put(
        array(
            "email" => $email,
            "pass" => password_hash($password, PASSWORD_DEFAULT),
            "firstName" => $firstname,
            "lastName" => $lastname,
            "age" => $age
        )
    );

    return 0;
}