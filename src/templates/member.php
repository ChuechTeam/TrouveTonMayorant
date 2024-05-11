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
        <li><a <?php linkAttribs($homePath); ?>>Accueil</a></li>
        <li><a <?php linkAttribs($profilePath); ?>>Profil</a></li>
        <li class="-sign-out"><a href="<?= "$root/redirect.php" ?>">Déconnexion</a></li>
    </ul>
</nav>
<main>
    <?= $content ?>
</main>
