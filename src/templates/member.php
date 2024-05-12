<?php
require __DIR__ . "/../modules/url.php";

$content = $tmplArgs["content"] ?? "\$content is empty!";

/*
 * Liens en tout genre
 */

$homePath = "/member-area";
$profilePath = "/member-area/profile.php";

global $curPath;
$curPath = rtrim(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), "/");

function isCurrentPage($path): bool {
    global $curPath;
    return $path === $curPath || $path . "/index.php" === $curPath;
}

function linkAttribs($path) {
    printf('href="%s"', $path);
    if (isCurrentPage($path)) {
        printf('class="-active"');
    }
}

/**
 * (À utiliser pour afficher le nom/prénom du profil après)
 * @var array $user
 */
$user = $tmplArgs["user"] ?? die("The member template requires a logged user!");
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
