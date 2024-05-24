<?php
    /*
     * To use:
     *
     * require_once templates/functions.php
     * Templates\base();
     *
     * To change the head
     * <?php Templates\paramStart("head") ?>
     *      <meta name="coucou">
     * <?php Templates\paramEnd(); ?>
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
    <link rel="preload" href="/assets/matsym-rounded.woff2" as="font" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/Computer-Modern/Sans/cmun-sans.css">
    <link rel="stylesheet" href="/assets/style.css" />
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