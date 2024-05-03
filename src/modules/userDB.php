<?php

namespace UserDB;

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
$revision = 1; // Version of the database. 

function &load()
{
    global $usersData;
    global $usersFilePath;
    global $usersDirty;

    if ($usersData === null) {
        $json = @file_get_contents($usersFilePath);
        if ($json === false) {
            trigger_error("Creating users database for the first time", E_USER_NOTICE);
            $usersData = [
                "users" => ["_dict" => 1],
                "byEmail" => ["_dict" => 1],
                "idSeq" => 1,
                "revision" => 1,
            ];
            $usersDirty = true;
            save();
        } else {
            $usersData = json_decode($json, true);
            upgrade();
        }

        register_shutdown_function(function() {
            save();
        });
    }

    return $usersData;
}

/**
 * Creates OR updates an existing user into the database.
 * Takes an associative array containing ALL properties of the user.
 * If the id isn't present, it will use the next available id.
 */
function put(array $user): int
{
    if ($user === null) {
        throw new \Exception("User is null!");
    }

    _validateExist($user, "email");
    _validateExist($user, "pass");
    _validateExist($user, "firstName");
    _validateExist($user, "lastName");

    global $usersData;
    global $usersDirty;

    load();

    $id = $user["id"] ?? nextId();
    $email = $user["email"];
    $usersData["users"][$id] = $user;

    // did the email change? update the byEmail dict
    if (
        !isset($usersData["byEmail"][$email]) ||
        $usersData["users"][$usersData["byEmail"][$email]] !== $email
    ) {
        unset($usersData["byEmail"][$email]);
        $usersData["byEmail"][$email] = $id;
    }

    $usersDirty = true;

    return $id;
}

function findByEmail(string $email): ?array
{
    $ud = &load();

    if (!isset($ud["byEmail"][$email])) {
        return null;
    } else {
        return $ud["users"][$ud["byEmail"][$email]];
    }
}

function findByEmailPassword(string $email, string $pass): ?array
{
    $u = findByEmail($email);
    if ($u !== null && !password_verify($pass, $u["pass"])) {
        return null;
    }

    return $u;
}

function findById(int $id): ?array
{
    $ud = &load();

    if (!isset($ud["users"][$id])) {
        return null;
    } else {
        return $ud["users"][$id];
    }
}

function nextId(): int
{
    global $usersDirty;
    
    $ud = &load();

    // to fix
    $id = $ud["idSeq"];
    $ud["idSeq"] = $id + 1;
    $usersDirty = true;
    return $id;
}

function upgrade() {
    global $usersData;
    global $usersDirty;
    global $revision;

    $prev = $usersData["revision"] ?? 0;
    if ($prev < $revision) {
        // todo!

        $usersData["revision"] = $revision;
        $usersDirty = true;
    }
}

function save()
{
    global $usersData;
    global $usersFilePath;
    global $usersDirty;

    if (!$usersDirty) {
        return; // no changes to save!
    }

    $ok = @file_put_contents($usersFilePath, json_encode($usersData));
    if ($ok === false) {
        throw new \Exception("Couldn't write to users file: $usersFilePath");
    }

    $usersDirty = false;
}

/*
 * Pretty useful utilities
 */

function _validateExist(array $user, string $prop)
{
    if (!isset($user[$prop])) {
        throw new \Exception("User is invalid: $prop missing.");
    }
}