<?php
require_once "templates/functions.php";
require_once "modules/userSession.php";
require "modules/url.php";
if (UserSession\isLogged()) {
    header("Location: $root/member-area");
    exit();
}
Templates\base();
Templates\addStylesheet("/assets/style/landing-page.css");
?>

<h1 class="title">Bonyour !</h1>

<p >Bienvenue sur Trouve Ton Mayorant, le site de recontre pour matheux !</p>
<p >eeehh INSCRIS-TOI IMMÉDIATEMENT POUR TROUVER LE/LA MAYORANT(E) DE TES
    <div class="spin">RÊVES</div> </p>
<a  href="<?= "$root/auth.php?register" ?>">
    <button >S'inscrire</button>
</a>

<p >Déjà membre de TTM ?</p>
<a href="<?= "$root/auth.php" ?>">
    <button >Se connecter</button>
</a>



