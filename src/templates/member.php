<?php
require_once __DIR__ . "/../modules/user.php";

$content = $tmplArgs["content"] ?? "\$content is empty!";

/*
 * (Not so) Random links
 */

$homePath = "/member-area";
$profilePath = "/member-area/profile.php";
$profileVisitsPath = "/member-area/profileVisits.php";
$userProfilePath = "/member-area/userProfile.php";
$chatPath = "/member-area/chat.php";
$shopPath = "/member-area/shop.php";
$adminPath = "/admin-area";

// Get the current path and remove the last / character so we can compare it more easily
global $curPath;
$curPath = rtrim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");

// Returns true when the current page is the one specified in $path.
// If $path is true, also returns true when the current page starts with $path.
function isCurrentPage($path, bool $prefix=false): bool {
    global $curPath;
    if ($prefix) {
        // Check if the current path starts with $path
        return strpos($curPath, $path) === 0;
    } 
    else {
        // Check if the $path is identical to the current path. Also checks for [folder]/index.php.
        return $path === $curPath || $path . "/index.php" === $curPath;
    }
}

// Prints a href="[URL]" attribute and a class="-active" attribute if the current page is contained in $path.
// $path can either be an array or a single string,
// either way, the first element is the link URL, and other elements
// are links that also make the link show as being selected.
function linkAttribs($path, bool $prefix=false) {
    $array = is_array($path) ? $path : [$path];
    printf('href="%s"', $array[0]);

    foreach ($array as $p) {
        if (isCurrentPage($p, $prefix)) {
            printf('class="-active"');
            return;
        }
    }
}

// Those variables are filled in the "member" function (see functions.php)
$user = $tmplArgs["user"] ?? die("The member template requires a logged user!");
$isAdmin = $tmplArgs["userLevel"] >= User\LEVEL_ADMIN;
?>

<nav id="member-nav">
    <div class="-ttm">
        TTM
    </div>
    <div class="-sep"></div>
    <ul class="-links">
        <li>
            <a <?php linkAttribs($homePath); ?>>
                <span class="icon -inl">home</span>
                <span class="-label">Accueil</span>
            </a>
        </li>
        <li>
            <a <?php linkAttribs([$profilePath, $profileVisitsPath, $userProfilePath]); ?>>
                <span class="icon -inl">account_circle</span>
                <span class="-label">Profil</span>
            </a>
        </li>
        <li>
            <a <?php linkAttribs($chatPath); ?>>
                <span class="icon -inl">chat</span>
                <span class="-label">Chat</span>
            </a>
        </li>
        <li>
            <a <?php linkAttribs($shopPath); ?>>
                <span class="icon -inl">shopping_cart</span>
                <span class="-label">Boutique</span>
            </a>
        </li>
        <?php if ($isAdmin): ?>
        <li>
            <a <?php linkAttribs($adminPath, true); ?>>
                <span class="icon -inl">admin_panel_settings</span>
                <span class="-label">Admin</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="-sign-out">
            <a href="/signOut.php">
                <span class="icon -inl">logout</span>
                <span class="-label -mobile-hide">Déconnexion</span>
            </a>
        </li>
    </ul>
</nav>
<main>
    <?= $content ?>
</main>
