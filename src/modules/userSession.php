<?php
namespace UserSession;

require_once __DIR__ . "/userDB.php";

$sessionStarted = false;
$cachedId = null;

function start() {
    global $sessionStarted;

    if (!$sessionStarted) {
        session_start();
        $sessionStarted = true;
    }
}

function isLogged() {
    return loggedUserId() !== null;
}

function loggedUserId(): ?int {
    global $cachedId;

    start();

    if (!isset($_SESSION["userId"])) {
        return null;
    }
    if ($cachedId !== null) {
        return $cachedId;
    }

    $id = $_SESSION["userId"];
    if (\UserDB\userExistsById($id)) {
        $cachedId = $id;
        return $id;
    } else {
        signOut();
        return null;
    }
}

function &loggedUser(): ?array {
    start();

    $id = loggedUserId();
    if ($id === null) {
        return null;
    }

    return \UserDB\findById($id);
}

function signOut() {
    start();
    unset($_SESSION["userId"]);
}

function signIn(int $id) {
    start();
    $_SESSION["userId"] = $id;
}