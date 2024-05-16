<?php
require __DIR__ . "/../modules/url.php";
require_once __DIR__ . "/../modules/user.php";

$content = $tmplArgs["content"] ?? "\$content is empty!";

/*
 * Liens en tout genre
 */

$homePath = "/member-area";
$profilePath = "/member-area/profile.php";
$chatPath = "/member-area/chat.php";
$adminPath = "/admin-area";

global $curPath;
$curPath = rtrim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");

function isCurrentPage($path, bool $prefix=false): bool {
    global $curPath;
    if ($prefix) {
        return strpos($curPath, $path) === 0;
    } 
    else {
        return $path === $curPath || $path . "/index.php" === $curPath;
    }
}

function linkAttribs($path, bool $prefix=false) {
    printf('href="%s"', $path);
    if (isCurrentPage($path, $prefix)) {
        printf('class="-active"');
    }
}

/**
 * (À utiliser pour afficher le nom/prénom du profil après)
 * @var array $user
 */
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
                <span class="material-symbols-rounded -inl -icon">home</span>
                <span class="-label">Accueil</span>
            </a>
        </li>
        <li>
            <a <?php linkAttribs($profilePath); ?>>
                <span class="material-symbols-rounded -inl -icon">account_circle</span>
                <span class="-label">Profil</span>
            </a>
        </li>
        <li>
            <a <?php linkAttribs($chatPath); ?>>
                <span class="material-symbols-rounded -inl -icon">chat</span>
                <span class="-label">Chat</span>
            </a>
        </li>
        <?php if ($isAdmin): ?>
        <li>
            <a <?php linkAttribs($adminPath, true); ?>>
                <span class="material-symbols-rounded -inl -icon">admin_panel_settings</span>
                <span class="-label">Admin</span>
            </a>
        </li>
        <?php endif; ?>
        <li class="-sign-out">
            <a href="<?= "$root/redirect.php" ?>">
                <span class="material-symbols-rounded -inl -icon">logout</span>
                <span class="-label -mobile-hide">Déconnexion</span>
            </a>
        </li>
    </ul>
</nav>
<main>
    <?= $content ?>
</main>
