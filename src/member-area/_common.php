<?php
require_once __DIR__ . "/../templates/functions.php";
require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";
require __DIR__ . "/../modules/url.php";

/*
 * File to include in each page inside the member area
 * If the page is an API action (used in AJAX), make sure to set $api to true before including this file.
 */

function bail(int $code) {
    http_response_code($code);
    exit();
}

$api = $api ?? false;
\UserSession\requireLevel(User\LEVEL_MEMBER, $api);
$user = \UserSession\loggedUser();

Templates\appendParam("head", '<script src="/scripts/profile.js" type="module" defer></script>
<link rel="preload" href="/assets/sup.svg" as="image"/>');
?>
