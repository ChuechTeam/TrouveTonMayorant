#! /usr/bin/env php
<?php
require_once __DIR__ . "/../src/modules/user.php";

/*
    Argument 1 : Path to a JSON file with user infos
*/

/* JSON contents :
{
    "firstName": string, (default : Mister)
    "lastName": string, (default : Egg)
    "email": string, (default : admin@ttm.fr)
    "password": string (default : admin)
    "birthDate": string (default : 01/01/2000)
}
*/

$json = [];
if ($argc >= 2) {
    $path = $argv[1];
    $cont = @file_get_contents($path);
    $json = $cont === null ? false : json_decode($cont, true);
    if (!is_array($json)) {
        echo "Failed to read or parse file $path. Make sure it is a valid JSON file.";
        die(1);
    }
}

$firstName = $json["firstName"] ?? "Mister";
$lastName = $json["lastName"] ?? "Egg";
$email = $json["email"] ?? "admin@ttm.fr";
$password = $json["password"] ?? "admin";
$birthDate = $json["birthDate"] ?? "2000-01-01";

$res = User\register($firstName, $lastName, $email, $password, $birthDate, User\GENDER_MAN, $id, true);
if ($res !== 0) {
    echo "Failed to create admin account: " . User\errToString($res);
    die(1);
}

UserDB\save();
echo "Admin account created with ID $id:\n";
print_r([
    "firstName" => $firstName,
    "lastName" => $lastName,
    "email" => $email,
    "password" => $password,
    "birthDate" => $birthDate
]);

exit(0);