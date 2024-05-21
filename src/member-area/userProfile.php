<?php

require "_common.php";
require_once "_profileCard.php";
require_once "../modules/viewDB.php";

$id = intval($_REQUEST["id"] ?? null);
$prof = $id === 0 ? null : \UserDB\findById($id);
Templates\member("Profil");

$errCode = null;
if ($_SERVER["REQUEST_METHOD"] === "POST" && $prof !== null) {
    $action = $_POST["action"] ?? null;
    if ($action === "block") {
        $errCode = User\blockUser($user["id"], $id);
    } else if ($action === "unblock") {
        $errCode = User\unblockUser($user["id"], $id);
    }

    // Redirect on the same page to avoid history annoyances (i.e. resubmitting form, previous page being the same page)
    if ($errCode === 0) {
        header("Location: /member-area/userProfile.php?id=$id");
        exit;
    }
}

$error = $errCode != null && $errCode != 0 ? User\errToString($errCode) : null;

$bs = User\blockStatus($user["id"], $id);

// Add a view if we should (no blocking between users, and the user already exists)
if ($prof !== null && $bs === \User\BS_NO_BLOCK && $user["id"] != $id) {
    // "PT1M" --> one each minute
    ViewDB\registerView($id, $user["id"], new DateInterval("PT1M"));
}

?>
<?php if (!empty($error)): ?> <p id="error"><?= $error ?></p> <?php endif; ?>
<?php if ($prof == null): ?>
    <div class="error">Utilisateur introuvable</div>
<?php else:
    povProfileCard($prof, true);
endif;
?>