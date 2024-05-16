<?php
require_once __DIR__ . "/../templates/functions.php";
require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";
require __DIR__ . "/../modules/url.php";

/*
 * Fichier à inclure dans chaque page de l'espace membre.
 * Si la page est action d'API (utilisée dans du AJAX), mettez $api à true avant de l'inclure.
 */

function bail(int $code) {
    http_response_code($code);
    exit();
}

$api = $api ?? false;
\UserSession\requireLevel(User\LEVEL_ADMIN, $api);
$user = \UserSession\loggedUser();

Templates\appendParam("head", '<script src="/scripts/profile.js" type="module" defer></script>
<link rel="preload" href="/assets/sup.svg" as="image"/>');
?>
