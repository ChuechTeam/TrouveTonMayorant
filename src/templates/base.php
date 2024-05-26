<?php
/*
 * To use:
 *
 * require_once templates/functions.php;
 * Templates\base();
 */

$content = $tmplArgs["content"] ?? "\$content is empty!";
$title = empty($tmplArgs["title"]) ? "TrouveTonMayorant" : "{$tmplArgs["title"]} - TTM";
$head = $tmplArgs["head"] ?? "";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <meta property="og:title" content="TrouveTonMayorant"/>
    <meta property="og:description" content="Rencontrez le/la mayorant(e) de vos rêves sur TTM, le site de rencontre pour matheux !"/>
    <link rel="preload" href="/assets/matsym-rounded.woff2" as="font" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/Computer-Modern/Sans/cmun-sans.css">
    <link rel="stylesheet" href="/assets/style/_root.css"/>
    <link rel="stylesheet" href="/assets/style/profile.css">
    <link rel="stylesheet" href="/assets/style/nav.css">
    <link rel="stylesheet" href="/assets/style/chat.css">
    <script src="/scripts/form.js" type="module" defer></script>
    <script src="/scripts/romu.js" defer></script>
    <?= $head ?>
</head>
<body>
<?= $content ?>
</body>
</html>