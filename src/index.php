<?php
require "templates/functions.php";
require "modules/url.php";
require "modules/userSession.php";
if (UserSession\isLogged()) {
    header("Location: $root/member-area");
    exit();
}
Templates\base();
?>

<h1 class="title">Bonyour !</h1>

<p>eeehh tu n'es pas loggé</p>
<p>eeehh INSCRIS-TOI IMMÉDIATEMENT POUR TROUVER LE/LA MAYORANT(E) DE TES
    <span style="color: gold; text-shadow: goldenrod 0px 2px 1px, goldenrod 0px 4px 2px, goldenrod 0px 6px 3px, goldenrod 0px 8px 4px;">RÊVES</span> </p>
<a href="<?= "$root/auth.php?register" ?>">
    <button>S'inscrire</button>
</a>

<p>Déjà membre de TTM ?</p>
<a href="<?= "$root/auth.php" ?>">
    <button>Se connecter</button>
</a>



