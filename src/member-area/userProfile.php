<?php

require "_common.php";
require_once "_profileCard.php";

$id = intval($_REQUEST["id"] ?? null);
$prof = $id === 0 ? null : \UserDB\findById($id);
Templates\member("Profil");

$errCode = null;
if ($_SERVER["REQUEST_METHOD"] === "POST" && $prof !== null) {
    $action = $_POST["action"] ?? null;
    if ($action === "block") {
        $errCode = User\blockUser($user["id"], $id);
    }
}

$error = $errCode != null && $errCode != 0 ? User\errToString($errCode) : null;

$bs = User\blockStatus($user["id"], $id);
?>
<?php if (!empty($error)): ?> <p id="error"><?= $error ?></p> <?php endif; ?>
<?php if ($prof == null): ?>
    <div class="error">Utilisateur introuvable</div>
<?php elseif ($bs !== User\BS_NO_BLOCK):
?>
<div class="blocked-profile">
    <h2>
        <?php if ($bs === User\BS_ME): ?>
            Vous avez bloqué cet utilisateur
        <?php elseif ($bs === User\BS_THEM): ?>
            Vous êtes bloqué par cet utilisateur
        <?php endif; ?>
    </h2>
</div>
<?php
else:
    profileCard($prof, true, User\level($user["id"]) >= User\LEVEL_ADMIN);
endif;
?>