<?php
    /**
     * To use:
     * 
     * <?php ob_start(); ?>
     * <p>My content...</p>
     * <?php $tmplContent = ob_get_clean(); include "template.php"; ?>
     * 
     * ---
     * You can also set other variables using either:
     *   - direct assignment:   <?php $tmplTitle = "Mayorant" ?>
     *   - ob_start() & ob_get_clean()
     */
    $tmplContent =  $tmplContent ?? "\$content is empty!";
    $tmplTitle = $tmplTitle ?? "TrouveTonMayorant";
    $tmplScripts = $tmplScripts ?? "";
    $tmplHead = $tmplHead ?? "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $tmplTitle ?></title>
    <link href="/assets/style.css" rel="stylesheet" />
    <?= $tmplHead ?>
</head>
<body>
    <?= $tmplContent ?>
    <?= $tmplScripts ?>
</body>
</html>