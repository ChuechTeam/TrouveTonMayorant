<?php

require "_common.php";
require_once "../member-area/_profileCard.php";

Templates\member("Tous les utilisateurs");
Templates\addStylesheet("/assets/style/all-users-page.css");

$users = UserDB\query();
?>

<h1>Liste des utilisateurs</h1>
<div id="people">
    <?php foreach ($users as $u):
        echo "<div class='profile-card-container'>";
        profileCard($u, false, false, true);
        echo "</div>";
    endforeach; ?>
</div>