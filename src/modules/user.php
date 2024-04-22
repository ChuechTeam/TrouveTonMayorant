<?php

/*
 * Users database: stored in a big JSON file
 * The json is just a dictionary of:
 * {
 *   users: { id: { id, name, confidentialInfo, ... } } 
 *   byEmail: { emailStr: userId } 
 *   idSeq: 0
 * }
 */

/*
 * The user attributes are:
 * - "id"
 * - "email"
 * - "pass" (the password, not hashed!)
 * - "firstName"
 * - "lastName"
 */

$usersData = null; // The associative array of the entire database
$usersDirty = false; // If we need to save to the database
$usersFilePath = __DIR__ . "/../../users.json"; // Location of the json database file

function userLoad() {
    global $usersData;
    global $usersFilePath;
    global $usersDirty;

    if ($usersData === null) {
        $json = @file_get_contents($usersFilePath);
        if ($json === false) {
            trigger_error("Creating users database for the first time", E_USER_NOTICE);
            $usersData = [
                "users" => [ "_dict" => 1 ],
                "byEmail" => [ "_dict" => 1 ],
                "idSeq" => 1
            ];
            $usersDirty = true;
            userSave();
        }
        else {
            $usersData = json_encode($json);
        }
    }

    return $usersData;
}

function userPut(array $user) {
    if ($user === null) {
        throw new Exception("User is null!");
    }
    _userValidateExist($user, "id");
    _userValidateExist($user, "email");
    _userValidateExist($user, "pass");
    _userValidateExist($user, "firstName");
    _userValidateExist($user, "lastName");

    global $usersData;
    global $usersDirty;

    $id = intval($user["id"]);
    print_r(gettype($id));
    $email = $user["email"];
    $usersData["users"][$id] = $user;

    // did the email change? update the byEmail dict
    if (!isset($usersData["byEmail"][$email]) || 
        $usersData["users"][$usersData["byEmail"][$email]] !== $email) {
        unset($usersData["byEmail"][$email]);
        $usersData["byEmail"][$email] = $id;
    }

    $usersDirty = true;
}

function userFindByEmail(string $email): ?array {
    $ud = userLoad();

    if (!isset($ud["byEmail"][$email])) {
        return null;
    } else {
        return $ud["users"][$ud["byEmail"][$email]];
    }
}

function userFindByEmailPassword(string $email, string $pass): ?array {
    $ud = userLoad();

    $u = userFindByEmail($email);
    if ($u !== null && $u["pass"] != $pass) {
        return null;
    } 

    return $u;
}

function userFindById(int $id): ?array {
    $ud = userLoad();

    if (!isset($ud["users"][$id])) {
        return null;
    } else {
        return $ud["users"][$id];
    }
}

function userNextId(): number {
    $ud = userLoad();

    $id = $ud["idSeq"];
    $ud["idSeq"] = $id + 1;
    $usersDirty = true;
    return $id; 
}

function userSave() {
    global $usersData;
    global $usersFilePath;
    global $usersDirty;

    if (!$usersDirty) {
        return; // no changes to save!
    }

    echo "elets save";

    $ok = @file_put_contents($usersFilePath, json_encode($usersData));
    if ($ok === false) {
        throw new Exception("Couldn't write to users file: $usersFilePath");
    }

    $usersDirty = false;
}

/*
 * Pretty useful utilities
 */

function _userValidateExist(array $user, string $prop) {
    if (!isset($user[$prop])) {
        throw new Exception("User is invalid: $prop missing.");
    }
}