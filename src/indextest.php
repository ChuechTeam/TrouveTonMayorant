<?php ob_start(); 
require "modules/url.php";
require "modules/userSession.php";
require_once "modules/userDB.php";
$u = \UserSession\loggedUser();
?>

<h1 id="title">Bonyour !</h1>

<?php if(\UserSession\isLogged()): ?>

<div class="user-box" id="userBox">
    <div id="userInfo">
        <span id="username"><?php echo $u["firstName"]." ".$u["lastName"] ?></span>
        <i class="arrow down" onclick="toggleOptions()">
        <img style="width : 20px;" src="data:image/png;base64,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">
        </i>
    </div>
    <div class="-options">
        <a href="<?= "$root/profile.php"?>">Profil</a>
        <a href="<?= "$root/redirect.php" ?>">Déconnexion</a>
        <!-- Ajoutez d'autres options ici -->
    </div>
    <div class="-bg" id="userBoxBg"></div>
</div>

<?php else: ?>
    <p>eeehh tu n'es pas loggé</p><br>
    <a href="<?= "$root/connexion.php" ?>"><button>Se connecter/S'inscrire</button></a>
<?php endif; ?>

<div class="background" id="background"></div> <!-- c'est quoi ??? -->


<script>
    const tb = document.getElementById("userBox");
    const tbh = document.getElementById("userInfo");
    const tbbg = document.getElementById("userBoxBg");

    function toggleOptions() { 
        if (tb.classList.contains("-open")) {
            tb.classList.remove("-open");
        } else {
            tb.classList.add("-open");
        }

        tbbg.style.height = tb.clientHeight + "px";
    }
    tbbg.style.height = tb.clientHeight + "px";

</script>

<?php $tmplContent = ob_get_clean(); include "templates/base.php"; ?>


