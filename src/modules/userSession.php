<?php
namespace UserSession;

require_once __DIR__ . "/userDB.php";
require_once __DIR__ . "/user.php";
require __DIR__ . "/url.php";

const SESSION_DIR = __DIR__ . "/../../sessions";

$sessionStarted = false;
$cachedId = null;

/**
 * Starts the user session if it has not yet started.
 *
 * Also configures the session directory, and attempts to create it if it doesn't exist.
 *
 * @return void
 */
function start() {
    global $sessionStarted;

    if (!$sessionStarted) {
        session_save_path(SESSION_DIR);
        if (!file_exists(SESSION_DIR)) {
            if (!mkdir(SESSION_DIR, 0777, true)) {
                throw new \RuntimeException("Failed to create session directory.");
            }
        }
        
        session_start();
        $sessionStarted = true;
    }
}

/**
 * Returns true if the site visitor is logged in.
 * @return bool true if the site visitor is logged in
 */
function isLogged(): bool
{
    return loggedUserId() !== null;
}

/**
 * Returns the user id of the current session, if there's one, and if the user exists.
 *
 * @return int|null the user id, null if not logged in or if the user doesn't exist
 */
function loggedUserId(): ?int {
    global $cachedId;

    start();

    if (!isset($_SESSION["userId"])) {
        return null;
    }

    // Cache the user id so we don't need to check the database every single time
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

/**
 * Returns the entire user data (in form on an associative array) of the currently logged-in user.
 *
 * @return array|null the user data, null if not logged in
 */
function loggedUser(): ?array {
    start();

    $id = loggedUserId();
    if ($id === null) {
        return null;
    }

    return \UserDB\findById($id);
}

/**
 * Returns the level of the site visitor.
 * (See in modules/user.php for the available levels such as {@see \User\LEVEL_SUBSCRIBER})
 */
function level(): int {
    return \User\level(loggedUserId());
}

/**
 * Checks if the site visitor is of grade `$level` or higher.
 * (See in modules/user.php for the available levels such as {@see \User\LEVEL_SUBSCRIBER})
 *
 * If the visitor is of a grade strictly lower than `$level`,
 * then the request will be redirected to the home page (index.php), unless
 * if `$api` is `true`, in which case the response will simply be an error.
 * (To be used for endpoints destined to be used with AJAX.)
 *
 * @param int $level the minimum grade of the site visitor
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

/**
 * Terminates the user session by removing the logged user id. The session is not destroyed, however.
 *
 * @return void
 */
function signOut() {
    global $cachedId;

    start();
    unset($_SESSION["userId"]);
    $cachedId = null;
}

/**
 * Starts a user session with the given user id.
 *
 * @param int $id the user id
 * @return void
 */
function signIn(int $id) {
    start();
    $_SESSION["userId"] = $id;
}