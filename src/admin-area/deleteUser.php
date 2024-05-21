<?php

require_once "_common.php";
require_once "../member-area/_profileCard.php";

Templates\member("Supprimer un utilisateur");

$u = !is_numeric($_GET["id"]) ? null : \UserDB\findById(intval($_GET["id"]));
$err = null;

if ($u === null) {
    echo "<div class=\"not-found\">Utilisateur introuvable</div>";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $ok = \User\deleteAccount($u["id"], null, isset($_POST["ban"]));
    if ($ok === 0) {
        header("Location: /member-area/");
        exit();
    } else {
        $err = User\errToString($ok);
    }
}

// Note: we're putting the style here so we don't leak too much admin CSS to non-admin users
?>

<style>
    #title, p {
        text-align: center;
    }

    .profile-card {
        max-width: 768px;
        margin: 0 auto;
    }

    #confirm-par {
        font-weight: bold;
    }

    #controls {
        max-width: 768px;
        margin: 4px auto;

        display: flex;
        gap: 16px;

        justify-content: stretch;
    }

    #controls > button {
        flex: 1;
        font-size: 1.5em;
        padding: 12px;
    }

    #ban {
        margin: 8px auto;
        width: fit-content;
    }
</style>

<h1 id="title">Suppression d'un utilisateur</h1>
<p id="confirm-par">Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
<?php povProfileCard($u); ?>
<form method="post">
    <input type="hidden" name="id" value="<?= $u["id"] ?>">
    <div id="ban"><input type="checkbox" name="ban"> <label for="ban">Bannir l'email de l'utilisateur</label></div>
    <div id="controls">
        <button class="-cancel" type="button" onclick="history.back()">Annuler</button>
        <button class="-delete dangerous-button">Oblitérer</button>
    </div>
</form>
<?php if ($err): ?> <p id="err"><?= $err ?></p> <?php endif; ?>
