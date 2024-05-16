<?php

require "_common.php";
require_once "_profileCard.php";

$id = intval($_GET["id"] ?? null);
$prof = $id === 0 ? null : \UserDB\findById($_GET["id"]);
Templates\member("Profil");
?>

<?php if ($prof == null): ?>
    <div class="error">Utilisateur introuvable</div>
<?php else:
    profileCard($prof, true, User\level($user["id"]) >= User\LEVEL_ADMIN);
endif;
?>