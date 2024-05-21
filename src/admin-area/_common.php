<?php
require_once __DIR__ . "/../templates/functions.php";
require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";
require __DIR__ . "/../modules/url.php";

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
