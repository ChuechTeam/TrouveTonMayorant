<?php
namespace UserSession;

require_once __DIR__ . "/userDB.php";
require_once __DIR__ . "/user.php";
require __DIR__ . "/url.php";

const SESSION_DIR = __DIR__ . "/../../sessions";

$sessionStarted = false;
$cachedId = null;

function start() {
    global $sessionStarted;

    if (!$sessionStarted) {
        session_save_path(SESSION_DIR);
        if (!file_exists(SESSION_DIR)) {
            mkdir(SESSION_DIR, 0777, true);
        }
        
        session_start();
        $sessionStarted = true;
    }
}

function isLogged(): bool
{
    return loggedUserId() !== null;
}

function  loggedUserId(): ?int {
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

function loggedUser(): ?array {
    start();

    $id = loggedUserId();
    if ($id === null) {
        return null;
    }

    return \UserDB\findById($id);
}

/**
 * Renvoie le grade du visiteur du site.
 * (Voir dans modules/user.php pour les grades disponibles, par exemple, \User\LEVEL_SUBSCRIBER)
 */
function level(): int {
    return \User\level(loggedUserId());
}

/**
 * Vérifie si le visiteur du site est de grade `$level` ou supérieur.
 * (Voir dans modules/user.php pour les grades disponibles, par exemple, \User\LEVEL_SUBSCRIBER)
 *
 * Si le visiteur un grade strictement inférieur à `$level`,
 * alors la requête sera redirigée vers la page d'accueil (index.php), sauf
 * si `$api` est `true`, auquel cas la réponse sera simplement une erreur.
 * (À utiliser pour les requêtes asynchrones.)
 *
 * @param int $level le grade minimum du visiteur du site
 */
function requireLevel(int $level, bool $api = false) {
    global $root;

    if (level() < $level) {
        http_response_code(401);

        if (!$api) {
            header("Location: $root/index.php");
        }
        exit();
    }
}

function signOut() {
    global $cachedId;

    start();
    unset($_SESSION["userId"]);
    $cachedId = null;
}

function signIn(int $id) {
    start();
    $_SESSION["userId"] = $id;
}