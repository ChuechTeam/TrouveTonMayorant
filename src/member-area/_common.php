<?php
require_once __DIR__ . "/../templates/functions.php";
require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";
require __DIR__ . "/../modules/url.php";

$api = $api ?? false;
\UserSession\requireLevel(User\LEVEL_MEMBER, $api);
$user = \UserSession\loggedUser();