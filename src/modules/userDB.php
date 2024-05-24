<?php

/**
 * userDB.php
 * ----------------
 * Stores all user data in one big JSON file. The JSON file is present in a
 * hardcoded path, and is locked when loaded in read-write mode.
 */

namespace UserDB;

/*
 *  JSON structure :
 *  "users" => associative array of users (see user.php for reference)
 *  "byEmail" => associative array [ [email] => [user id] ]
 *  "idSeq" => the id given to the next registered user, incremented on each registration
 *  "revision" => database revision, used to add/remove fields after each update
 */

// List of all database versions, each version require changes to various fields.
const REV_FIRST = 1;
const REV_NEW_DB_LOADING = 2; // Removes legacy stuff
const REV_INTERACTION_UPDATE = 3;
const REV_PROFILE_DETAILS = 4;
const REV_REG_DATE = 5;
const REV_MATHS_PREFS = 6;
const REV_SUP_ADMIN = 7;
const REV_EQUATION = 8;
const REV_PFP = 9;
const REV_LOC = 10;
const REV_LOC_STR = 11;
const REV_PICS = 12;
const REV_PFP_RESET = 13;
const REV_SUP_BOUGHT = 14;
const REV_LAST = REV_SUP_BOUGHT; // Last revision of the database

$usersFile = null; // The JSON file loaded using fopen
$usersReadOnly = false; // If the database is opened in read-only mode
$usersData = null; // The associative array containing all the JSON data
$usersDirty = false; // If changes have been made to the database.
$usersFilePath = __DIR__ . "/../../users.json"; // Location of the JSON file
$shutdownRegistered = false; // To avoid calling unload() twice at the end of the script

/**
 * Fully loads the database.
 *
 * Can be loaded in read-only mode if `$readOnly = true`,
 * which greatly improves performance when the script only reads data while not writing anything.
 *
 * If the database is already loaded, the function does nothing.
 * @param bool $readOnly if the database should be loaded in read-only mode
 * @return array a reference to the entire database data in an associative array
 */
function &load(bool $readOnly = false): array {
    global $usersData;
    global $usersReadOnly;
    global $usersFile;
    global $usersFilePath;
    global $shutdownRegistered;

    if ($usersData === null) {
        // This function will read the user database, apply any upgrade, and create the database if it doesn't exist yet.
        if (!_read($usersFilePath, $usersFile, $usersData, $readOnly)) {
            throw new \RuntimeException("Failed to load the user database.");
        }

        // Save the database once the request ends. Don't register the function twice!
        if (!$shutdownRegistered) {
            register_shutdown_function(function () {
                unload();
            });
            $shutdownRegistered = true;
        }
    }

    return $usersData;
}

/**
 * Returns false when the database is opened in write mode, or when it's not loaded.
 *
 * @return bool true if read only, false if read-write
 */
function isReadOnly(): bool {
    global $usersReadOnly;

    return $usersReadOnly;
}

/**
 * Adds or updates a user (from the `$user` array) in the database.
 * The associative array `$user` must contain all the user's information.
 *
 * If the id is not specified, then a new user will be created. The function returns the id of
 * the created or updated user.
 *
 * The database must not be loaded in read-only mode, otherwise an error will occur.
 * (If the database is not loaded, it will be loaded automatically in write mode.)
 *
 * @param array $user the associative array that contains the user's data.
 * @return int the id of the created or updated user
 */
function put(array $user): int {
    if (isReadOnly()) {
        throw new \RuntimeException("User database is opened in read-only mode!");
    }

    _validateExist($user, "email");
    _validateExist($user, "pass");
    _validateExist($user, "firstName");
    _validateExist($user, "lastName");
    _validateExist($user, "bdate");
    _validateExist($user, "gender");
    _validateExist($user, "conversations");
    _validateExist($user, "blockedUsers");
    _validateExist($user, "blockedBy");

    global $usersDirty;

    $ud = &load();

    $existingUser = isset($user["id"]) ? findById($user["id"]) : null;
    if ($existingUser === null && isset($user["id"])) {
        throw new \RuntimeException("Attempted to update an inexistant user (id={$user['id']})");
    }

    // Give a new id if the user doesn't have one already
    $id = $user["id"] ?? nextId();
    $user["id"] = $id;
    $newEmail = $user["email"];

    // Make sure we don't create a new user with the same email as another user
    if (isset($ud["byEmail"][$newEmail]) && $ud["byEmail"][$newEmail] !== $id) {
        throw new \RuntimeException("Attempted to create a user with the same email as another user.");
    }

    if ($existingUser !== null) {
        // Remove the previous email from the byEmail dictionary. We'll set the new one later.
        $prevEmail = $existingUser["email"];
        if ($prevEmail !== $newEmail) {
            unset($ud["byEmail"][$prevEmail]);
        }
    }

    // Update block relationships (blockedUsers and blockedBy arrays)
    _updateUserBlocks($id, $existingUser["blockedUsers"] ?? [], $user["blockedUsers"]);

    $ud["byEmail"][$newEmail] = $id;
    $ud["users"][$id] = $user;
    $usersDirty = true;

    return $id;
}

/**
 * Deletes a user from the database.
 * Conversations remain intact, the list of blocked users is not changed.
 *
 * @param int $id the id of the user to delete
 * @return bool true if the user was deleted, false if the user was not found
 */
function delete(int $id): bool {
    if (isReadOnly()) {
        throw new \RuntimeException("User database is opened in read-only mode!");
    }

    global $usersDirty;

    $ud = &load();

    if (!isset($ud["users"][$id])) {
        return false;
    }

    $email = $ud["users"][$id]["email"];
    unset($ud["users"][$id]);
    unset($ud["byEmail"][$email]);
    $usersDirty = true;

    return true;
}

/**
 * Finds a user using its email. Returns null if the user is not found.
 *
 * @param string $email the email
 * @return array|null the user data, or null if not found
 */
function findByEmail(string $email): ?array {
    $ud = &load();

    if (!isset($ud["byEmail"][$email])) {
        return null;
    } else {
        return $ud["users"][$ud["byEmail"][$email]];
    }
}

/**
 * Finds a user using its email and clear text password. Returns null if the user is not found.
 *
 * @param string $email the email
 * @param string $pass the clear text password
 * @return array|null the user data, or null if not found
 */
function findByEmailPassword(string $email, string $pass): ?array {
    $u = findByEmail($email);
    if ($u !== null && !password_verify($pass, $u["pass"])) {
        return null;
    }
    return $u;
}

/**
 * Returns true if the user exists with the given id.
 *
 * @param int $id the user id
 * @return bool true if the user exists
 */
function userExistsById(int $id): bool {
    $ud = &load();

    return isset($ud["users"][$id]);
}

/**
 * Finds a user by its id. Returns null if the user is not found.
 *
 * @param int $id the user id
 * @return array|null the user data, or null if not found
 */
function findById(int $id): ?array {
    $ud = &load();

    if (!isset($ud["users"][$id])) {
        return null;
    } else {
        return $ud["users"][$id];
    }
}

/**
 * Returns all users present in the database.
 * (Later there will be options to search by name, age, etc.)
 *
 * (If the database is not loaded, it will be loaded automatically in write mode.)
 * @return array an array containing all the users
 */
function query(): array {
    $ud = &load();
    return array_values($ud["users"]);
}

// Returns the next id for a user (internal)
function nextId(): int {
    global $usersDirty;

    $ud = &load();

    $id = $ud["idSeq"];
    $ud["idSeq"] = $id + 1;
    $usersDirty = true;
    return $id;
}

// Updates the block relationships between users.
// Basically, it adds and removes entries in the blockedBy array for the (un-)blocked users.
function _updateUserBlocks(int $blocker, array $oldBlocks, array &$newBlocks) {
    if ($oldBlocks == $newBlocks) {
        return;
    }

    $ud = &load();

    // All the blocks that were lifted
    $removed = array_diff_key($oldBlocks, $newBlocks);
    // All the new blocks
    $added = array_diff_key($newBlocks, $oldBlocks);

    // Remove lifted blocks from the blockedBy array of the previously blocked users
    foreach ($removed as $unblockedId => $_) {
        if (isset($ud["users"][$unblockedId])) {
            $u = &$ud["users"][$unblockedId];
            unset($u["blockedBy"][$blocker]);
        }
        // If the id is not found it's actually normal, the user has been deleted
    }

    // Add new blocks to the blockedBy array of the newly blocked users
    foreach ($added as $blockedId => $_) {
        if (isset($ud["users"][$blockedId])) {
            $u = &$ud["users"][$blockedId];
            $u["blockedBy"][$blocker] = 1;
        } else {
            trigger_error("Inexistant user id ($blockedId) has been added to the blockedUsers list! This user will be ignored.",
                E_USER_WARNING);
            unset($newBlocks[$blockedId]);
        }
    }
}

// Upgrade the database to the latest revision
function _upgrade(array &$data) {
    global $usersDirty;
    global $usersReadOnly;

    $prev = $data["revision"] ?? null;
    if ($prev === null) {
        throw new \RuntimeException("Revision property not found, the user database file is likely invalid or corrupted!");
    }
    if ($prev < REV_LAST) {
        if ($usersReadOnly) {
            throw new \RuntimeException("Cannot update the database in read-only mode!");
        }

        $cur = $prev;
        while ($cur < REV_LAST) {
            $cur++;
            trigger_error("Upgrading database to revision " . $cur . ".");

            switch ($cur) {
                case REV_NEW_DB_LOADING:
                    unset($data["users"]["_dict"]);
                    unset($data["byEmail"]["_dict"]);
                    break;
                case REV_INTERACTION_UPDATE:
                    foreach ($data["users"] as &$u) {
                        $u["conversations"] = [];
                        $u["blockedUsers"] = [];
                        $u["blockedBy"] = [];
                    }
                    break;
                case REV_PROFILE_DETAILS:
                    $year = (new \DateTime())->format("Y");
                    foreach ($data["users"] as &$u) {
                        if (!isset($u["bdate"])) {
                            $ny = $year - $u["age"];
                            $u["bdate"] = "$ny-01-01";
                        }
                        unset($u["age"]);

                        $strProps = ["gender", "orientation", "job", "situation", "dep", "city", "desc", "bio", "mathField", "eigenVal", "user_smoke", "search_smoke"];
                        foreach ($strProps as $p) {
                            if (!isset($u[$p])) {
                                $u[$p] = "";
                            }
                        }
                        $u["gender_search"] = $u["gender_search"] ?? [];
                        $u["rel_search"] = $u["rel_search"] ?? [];
                    }
                    break;
                case REV_REG_DATE:
                    foreach ($data["users"] as &$u) {
                        if (!isset($u["rdate"])) {
                            $u["rdate"] = (new \DateTime())->format("Y-m-d");
                        }
                    }
                    break;
                case REV_MATHS_PREFS:
                    foreach ($data["users"] as &$u) {
                        $u["eigenVal"] = $u["eigenVal"] ?? "";
                        $u["mathField"] = $u["mathField"] ?? "";
                    }
                    break;
                case REV_SUP_ADMIN:
                    foreach ($data["users"] as &$u) {
                        $u["supExpire"] = null;
                        $u["admin"] = false;
                    }
                    break;
                case REV_EQUATION:
                    foreach ($data["users"] as &$u) {
                        $u["equation"] = $u["equation"] ?? "";
                    }
                    break;
                case REV_PFP:
                    foreach ($data["users"] as &$u) {
                        $u["pfp"] = $u["pfp"] ?? "";
                    }
                    break;
                case REV_LOC:
                    foreach ($data["users"] as &$u) {
                        $u["dep"] = $u["dep"] ?? "";
                        $u["city"] = $u["city"] ?? "";
                    }
                    break;
                case REV_PICS:
                    foreach ($data["users"] as &$u) {
                        $u["pic1"] = $u["pic1"] ?? "";
                        $u["pic2"] = $u["pic2"] ?? "";
                        $u["pic3"] = $u["pic3"] ?? "";
                    }
                    break;
                case REV_LOC_STR:
                    foreach ($data["users"] as &$u) {
                        $u["depName"] = $u["depName"] ?? "";
                        $u["cityName"] = $u["cityName"] ?? "";
                    }
                    break;
                case REV_PFP_RESET:
                    foreach ($data["users"] as &$u) {
                        $u["pfp"] = "";
                    }
                    break;
                case REV_SUP_BOUGHT:
                    foreach ($data["users"] as &$u) {
                        $u["supBought"] = null;
                    }
                    break;
                default:
                    break;
            }
        }

        $data["revision"] = REV_LAST;
        $usersDirty = true;
    }
}

/**
 * Saves the database to the JSON file, if the data has changed (user added, removed, update).
 *
 * @return void
 */
function save() {
    global $usersData;
    global $usersFilePath;
    global $usersDirty;
    global $usersFile;
    global $usersReadOnly;

    if (!$usersFile || !$usersDirty || $usersReadOnly) {
        return;
    }

    if (!\DB\save($usersFile, $usersData)) {
        throw new \RuntimeException("Failed to save the user database!");
    }

    $usersDirty = false;
}

/**
 * Unloads the database and releases any file resource and lock.
 * @return void
 */
function unload() {
    global $usersData;
    global $usersDirty;
    global $usersFile;
    global $usersReadOnly;

    if ($usersData === null || $usersFile === null) {
        return;
    }

    if ($usersDirty) {
        save();
    }

    \DB\close($usersFile);

    $usersData = null;
    $usersFile = null;
    $usersReadOnly = false;
}

// Ensures that a property exists in a user array
function _validateExist(array $user, string $prop) {
    if (!isset($user[$prop])) {
        throw new \InvalidArgumentException("User is invalid: $prop missing.");
    }
}

// Gets the file size of a handle
function _fSize($handle) {
    $stat = fstat($handle);
    if ($stat === false) {
        throw new \RuntimeException("Failed to gather the file size!");
    }
    return $stat['size'];
}

function _default(): array {
    return [
        "users" => [],
        "byEmail" => [],
        "idSeq" => 1,
        "revision" => REV_LAST,
    ];
}

function _read(string $path, &$handle, &$data, bool $readOnly = false): bool {
    return \DB\read($path, "UserDB\_upgrade", $handle, $data, "UserDB\_default", $readOnly);
}