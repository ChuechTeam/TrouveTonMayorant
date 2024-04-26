<?php ob_start(); 
session_start();

require "modules/url.php"
?>

<h1>Bonyour !</h1>
<p><?php print("Ici le php !!") ?></p>

<?php if($_SESSION["loggedIn"] == 1) :?>
    <p>eeeh tu es loggé</p>
    <a href="<?= "$root/redirect.php" ?>"><button>Déconnexion</button></a>
<?php else: ?>
    <p>eeehh tu n'es pas loggé</p><br>
    <a href="<?= "$root/connexion.php" ?>"><button>Se connecter/S'inscrire</button></a>
<?php endif ?>

<?php $tmplContent = ob_get_clean(); include "templates/base.php"; ?>


