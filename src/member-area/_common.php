<?php
require_once __DIR__ . "/../templates/functions.php";
require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";

/*
 * File to include in each page inside the member area
 * If the page is an API action (used in AJAX), make sure to set $api to true before including this file.
 */

/**
 * Exit the script with the given HTTP status code. (Helper function for APIs)
 * @param int $code the HTTP code
 * @return void
 */
function bail(int $code) {
    http_response_code($code);
    exit();
}

// Set the $api variable to false by default if it hasn't been specified *before* including this file.
$api = $api ?? false;
// Make sure the user is a member, and not a guest.
\UserSession\requireLevel(User\LEVEL_MEMBER, $api);
$user = \UserSession\loggedUser();

// Add some common scripts
Templates\appendParam("head", '<script src="/scripts/profile.js" type="module" defer></script>
<link rel="preload" href="/assets/sup.svg" as="image"/>');
?>
