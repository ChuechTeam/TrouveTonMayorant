<?php
require_once "templates/functions.php";
require_once "modules/userSession.php";
require "modules/url.php";
if (UserSession\isLogged()) {
    header("Location: $root/member-area");
    exit();
}
Templates\base();
?>

<style>

.spin{
    animation-name: spin;
    animation-duration: 2000ms;
    animation-iteration-count: infinite;
    animation-timing-function: linear;
    transform-origin: center;

    &:hover  { animation-duration: 100ms; }
}

@keyframes spin {
    0% {
        transform:rotate(0deg);
    }
    25% {
        transform:rotate(15deg);
    }

    50% {
        transform:rotate(0deg);
    }

    75% {
        transform:rotate(-15deg);
    }
    100% {
        transform: rotate(0deg);
    }
}

</style>

<h1 class="title">Bonyour !</h1>

<p >Bienvenue sur Trouve Ton Mayorant, le site de recontre pour matheux !</p>
<p >eeehh INSCRIS-TOI IMMÉDIATEMENT POUR TROUVER LE/LA MAYORANT(E) DE TES
    <div class="spin" style="margin 0 auto; font-size: 8vw  ; font-family: system-ui; display: flex; align-items: center; justify-content: center; color: gold; text-shadow: goldenrod 0px 2px 1px, goldenrod 0px 4px 2px, goldenrod 0px 6px 3px, goldenrod 0px 8px 4px;">RÊVES</div> </p>
<a  href="<?= "$root/auth.php?register" ?>">
    <button >S'inscrire</button>
</a>

<p >Déjà membre de TTM ?</p>
<a href="<?= "$root/auth.php" ?>">
    <button >Se connecter</button>
</a>



