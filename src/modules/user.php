<?php
namespace User;

require_once __DIR__ . "/userDB.php";

define("UE_EMAIL_USED", 1);
define("UE_FIELD_MISSING", 2);
define("UE_INVALID_CREDENTIALS", 3);

// 0 --> OK
// >0 --> OH NOOO
function register(string $firstname, string $lastname, string $email, string $password, $age, &$id): int
{
    if (\UserDB\findByEmail($email) != null) {
        return UE_EMAIL_USED;
    }

    $valErr = validate([
        "firstName" => $firstname,
        "lastName" => $lastname,
        "email" => $email,
        "age" => $age
    ], null);
    if ($valErr !== 0) {
        return $valErr;
    }

    if (!isset($password)) {
        return UE_FIELD_MISSING;
    }

    $id = \UserDB\put(
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

function validate(array $userInfos, ?int $existingId): int
{
    if (empty($userInfos["firstName"])
        || empty($userInfos["lastName"])
        || empty($userInfos["email"])
        || empty($userInfos["age"])
    ) {
        return UE_FIELD_MISSING;
    }

    $age = intval($userInfos["age"]);
    if ($age < 18) {
        return UE_FIELD_MISSING; // pas le bon error type mais flemme
    }

    if ($existingId !== null) {
        $u = &\UserDB\findById($existingId);
        if ($u !== null 
            && $u["email"] != $userInfos["email"]
            && \UserDB\findByEmail($u["email"]) != null) {
            return UE_EMAIL_USED;
        }
    }

    return 0;
}

function errToString(int $err) {
    switch ($err) {
        case 1:
            return "Ce mail est deja utilisé";
        case 2:
            return "Veuillez renseigner tous les champs";
        case 3:
            return "Le mot de passe ou l'identifiant n'est pas le bon";
        default:
            return "Erreur !";
    }
}