<?php
require_once __DIR__ . "/../templates/functions.php";
require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";

function bail(int $code) {
    http_response_code($code);
    exit();
}

$api = $api ?? false;
// Make sure our user is an admin
\UserSession\requireLevel(User\LEVEL_ADMIN, $api);
$user = \UserSession\loggedUser();

Templates\appendParam("head", '<script src="/scripts/profile.js" type="module" defer></script>
<link rel="preload" href="/assets/sup.svg" as="image"/>');
?>
