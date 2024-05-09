<?php

namespace UserDB;

/*
 * Attributs du tableau associatif "user" :
 * - "id"
 * - "email"
 * - "pass" (hashé)
 * - "firstName"
 * - "lastName"
 * - "conversations" : tableau des id des conversations
 * - "blockedUsers" : tableau associatif d'id d'utilisateurs bloqués : [id]=>1
 * - "blockedBy" : tableau associatif d'id des utilisateurs qui ont bloqué celui-ci : [id]=>1
 */

/*
 *  "users" => tableau assoc des utilisateurs
 *  "byEmail" => tableau assoc [ [email] => [id utilisateur] ]
 *  "idSeq" => nombre de séquences d'identifiant
 *  "revision" => version de la base de donnée
 */

// Liste des versions de la base de donnée, chaque version requiert des changements distincts.
const REV_FIRST = 1;
const REV_NEW_DB_LOADING = 2; // Retire le "_dict: 1" dans users et byEmail
const REV_INTERACTION_UPDATE = 3; // Conversations et blocage
const REV_LAST = REV_INTERACTION_UPDATE; // Dernière version de la base de donnée

$usersFile = null; // Le fichier json chargé avec fopen
$usersReadOnly = false; // Si la base de donnée est ouverte en lecture seule
$usersData = null; // Le tableau associatif avec toutes les données du JSON
$usersDirty = false; // Si des changements ont été effectués à la base de données.
$usersFilePath = __DIR__ . "/../../users.json"; // Emplacement du fichier JSON
$shutdownRegistered = false; // Pour éviter d'appeler unload() deux fois à la fin du script

/**
 * Charge intégralement la base de donnée.
 *
 * Peut être chargée en lecture seule si `$readOnly = true`, ce qui permet de grandement
 * améliorer les performances lorsque le script ne fait que lire les données.
 *
 * Si la base de donnée est déjà chargée, la fonction ne fait rien.
 * @param bool $readOnly si la base de donnée doit être chargée en lecture seule
 * @return array une référence vers l'intégralité des données
 */
function &load(bool $readOnly = false): array {
    global $usersData;
    global $usersReadOnly;
    global $usersFile;
    global $usersFilePath;
    global $shutdownRegistered;

    if ($usersData === null) {
        $usersFile = @fopen($usersFilePath, $readOnly ? "r" : "r+");
        if ($usersFile !== false) {
            if (flock($usersFile, $readOnly ? LOCK_SH : LOCK_EX)) {
                $json = fread($usersFile, filesize($usersFilePath));
                $usersData = json_decode($json, true);
                $usersReadOnly = $readOnly;
                _upgrade($usersData);
            } else {
                fclose($usersFile);
                $usersFile = null;
                throw new \RuntimeException("Failed to lock the existing user database.");
            }
        } else if (!file_exists($usersFilePath)) {
            trigger_error("Creating users database for the first time", E_USER_NOTICE);
            $usersFile = fopen($usersFilePath, "w");
            flock($usersFile, LOCK_EX);

            $usersData = [
                "users" => [],
                "byEmail" => [],
                "idSeq" => 1,
                "revision" => REV_LAST,
            ];

            fwrite($usersFile, json_encode($usersData));
        } else {
            throw new \RuntimeException("Failed to read the existing user database.");
        }

        if (!$shutdownRegistered) {
            register_shutdown_function(function () {
                unload();
            });
            $shutdownRegistered = true;
        }
    }

    return $usersData;
}

// Renvoie false quand la base de donnée est ouverte en écriture, ou lorsqu'elle n'est pas chargée.
function isReadOnly(): bool {
    global $usersReadOnly;

    return $usersReadOnly;
}

/**
 * Ajoute ou met à jour un utilisateur (du tableau `$user`) dans la base de donnée.
 * Le tableau associatif `$user` doit contenir toutes les informations de l'utilisateur.
 *
 * Si l'id n'est pas spécifié, alors un nouvel utilisateur sera créé. La fonction renvoie l'id de
 * l'utilisateur créé ou mis à jour.
 *
 * La base de donnée ne doit pas être chargée en lecture seule, auquel cas une erreur surviendra.
 * (Si la base de donnée n'est pas chargée, elle sera chargée automatiquement en écriture.)
 *
 * @param array $user le tableau associatif qui contient les données de l'utilisateur.
 * @return int l'id de l'utilisateur créé ou mis à jour
 */
function put(array $user): int {
    if ($user === null) {
        throw new \InvalidArgumentException("User is null!");
    }
    if (isReadOnly()) {
        throw new \RuntimeException("User database is opened in read-only mode!");
    }

    _validateExist($user, "email");
    _validateExist($user, "pass");
    _validateExist($user, "firstName");
    _validateExist($user, "lastName");
    _validateExist($user, "age");
    _validateExist($user, "conversations");
    _validateExist($user, "blockedUsers");
    _validateExist($user, "blockedBy");

    global $usersDirty;

    $ud = &load();

    $existingUser = isset($user["id"]) ? findById($user["id"]) : null;
    if ($existingUser === null && isset($user["id"])) {
        throw new \RuntimeException("Attempted to update an inexistant user (id=${user['id']})");
    }

    $id = $user["id"] ?? nextId();
    $user["id"] = $id;
    $newEmail = $user["email"];

    if (isset($ud["byEmail"][$newEmail]) && $ud["byEmail"][$newEmail] !== $id) {
        throw new \RuntimeException("Attempted to create a user with the same email as another user.");
    }

    if ($existingUser !== null) {
        // Effacer le lien entre l'utilisateur et l'email d'avant si l'email a changé
        $prevEmail = $existingUser["email"];
        if ($prevEmail !== $newEmail) {
            unset($ud["byEmail"][$prevEmail]);
        }
    }

    _updateUserBlocks($id, $existingUser["blockedUsers"] ?? [], $user["blockedUsers"]);

    $ud["byEmail"][$newEmail] = $id;
    $ud["users"][$id] = $user;
    $usersDirty = true;

    return $id;
}

function findByEmail(string $email): ?array {
    $ud = &load();

    if (!isset($ud["byEmail"][$email])) {
        return null;
    } else {
        return $ud["users"][$ud["byEmail"][$email]];
    }
}

function findByEmailPassword(string $email, string $pass): ?array {
    $u = findByEmail($email);
    if ($u !== null && !password_verify($pass, $u["pass"])) {
        return null;
    }
    return $u;
}

function userExistsById(int $id): bool {
    $ud = &load();

    return isset($ud["users"][$id]);
}

function findById(int $id): ?array {
    $ud = &load();

    if (!isset($ud["users"][$id])) {
        return null;
    } else {
        return $ud["users"][$id];
    }
}

/**
 * Renvoie tous les utilisateurs présents dans la base de donnée.
 * (Plus tard il y aura des options pour chercher par nom, age, etc.)
 *
 * (Si la base de donnée n'est pas chargée, elle sera chargée automatiquement en écriture.)
 */
function query(): array {
    $ud = &load();
    return array_values($ud["users"]);
}

function nextId(): int {
    global $usersDirty;

    $ud = &load();

    $id = $ud["idSeq"];
    $ud["idSeq"] = $id + 1;
    $usersDirty = true;
    return $id;
}

function _updateUserBlocks(int $blocker, array $oldBlocks, array &$newBlocks) {
    if ($oldBlocks == $newBlocks) {
        return;
    }

    $ud = &load();
    $removed = array_diff_key($oldBlocks, $newBlocks);
    $added = array_diff_key($newBlocks, $oldBlocks);

    foreach ($removed as $unblockedId => $_) {
        if (isset($ud["users"][$unblockedId])) {
            $u = &$ud["users"][$unblockedId];
            unset($u["blockedBy"][$blocker]);
        }
        // si l'id n'est pas trouvé c'est pas grave, l'utilisateur a été supprimé
    }

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
                default:
                    break;
            }
        }

        $data["revision"] = REV_LAST;
        $usersDirty = true;
    }
}

function save() {
    global $usersData;
    global $usersFilePath;
    global $usersDirty;
    global $usersFile;
    global $usersReadOnly;

    if (!$usersFile || !$usersDirty || $usersReadOnly) {
        return;
    }

    $newJson = json_encode($usersData);
    $ok = true;
    $ok &= fseek($usersFile, 0) === 0;
    $ok &= ftruncate($usersFile, strlen($newJson)) !== false;
    $ok &= fwrite($usersFile, $newJson) !== false;

    if (!$ok) {
        throw new \RuntimeException("Couldn't write to users file: $usersFilePath");
    }

    $usersDirty = false;
}

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

    flock($usersFile, LOCK_UN);
    fclose($usersFile);

    $usersData = null;
    $usersFile = null;
    $usersReadOnly = false;
}

function _validateExist(array $user, string $prop) {
    if (!isset($user[$prop])) {
        throw new \InvalidArgumentException("User is invalid: $prop missing.");
    }
}