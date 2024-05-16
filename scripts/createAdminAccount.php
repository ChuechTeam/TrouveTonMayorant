<?php
require_once __DIR__ . "/../src/modules/user.php";

/*
Argument 1 : Chemin vers un fichier JSON contenant les informations requises pour créer l'utilisateur
*/

/* Contenu du JSON :
{
    "firstName": string, (par défaut : Mister)
    "lastName": string, (par défaut : Egg)
    "email": string, (par défaut : admin@ttm.fr)
    "password": string (par défaut : admin)
    "birthDate": string (par défaut : 01/01/2000)
}
*/

$json = [];
if ($argc >= 2) {
    $path = $argv[1];
    $cont = @file_get_contents($path);
    $parsed = $cont === null ? false : json_decode($cont, true);
    if ($parsed === false) {
        echo "Failed to read file $path.";
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
echo "Admin account created with ID $id.";

exit(0);