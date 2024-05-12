<?php
    /*
     * Tuto :
     *
     * require_once templates/functions.php
     * Templates\base();
     *
     *
     * Pour changer le head :
     * <?php Templates\paramStart("head") ?>
     *      <meta name="coucou">
     * <?php Templates\paramEnd(); ?>
     */

$content = $tmplArgs["content"] ?? "\$content is empty!";
$title = empty($tmplArgs["title"]) ? "TrouveTonMayorant" : "${tmplArgs["title"]} - TTM";
$head = $tmplArgs["head"] ?? "";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?></title>
    <link rel="preload" href="/assets/matsym-rounded.woff2" as="font" crossorigin="anonymous">
    <link href="/assets/style.css" rel="stylesheet" />
    <?= $head ?>
</head>
<body>
<?= $content ?>
</body>
</html>