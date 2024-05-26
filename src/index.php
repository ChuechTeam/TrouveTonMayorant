<?php
require_once "templates/functions.php";
require_once "modules/userSession.php";

if (UserSession\isLogged()) {
    header("Location: /member-area");
    exit();
}
Templates\base();
Templates\addStylesheet("/assets/style/landing-page.css");
Templates\addStylesheet("/assets/style/nav.css");
?>
<nav id="member-nav" class="-keep-mobile-labels">
    <div class="-ttm">
        TTM
    </div>
    <div class="-sep"></div>
    <ul class="-links">
        <li>
            <a href="/auth.php?register">
                <span class="icon -inl">person_add</span>
                <span class="-label">Inscription</span>
            </a>
        </li>
        <li>
            <a href="/auth.php">
                <span class="icon -inl">account_box </span>
                <span class="-label">Connexion</span>
            </a>
        </li>
    </ul>
</nav>

<h1 class="title">Bonyour !</h1>

<div class="welcome-parent">
    <h2 class="welcome">Bienvenue sur TrouveTonMayorant, le site de rencontre pour matheux !</h2>
</div>
<p class="aggressive-marketing">Eeehh INSCRIS-TOI IMMÉDIATEMENT POUR TROUVER LE/LA MAYORANT(E) DE TES
<div class="spin">RÊVES</div>
</p>




<section id="showcase">
    <div class="feature">
        <h2 class="-title">Exprimez-vous mathématiquement.</h2>
        <p class="-desc">
            TTM est le <b>premier</b> site de rencontre à offrir des
            <b>capacités de formattage mathématique</b> à ses membres.<br>
            Montrez votre <b>problème mathématique favori</b>, écrivez des équations dans votre biographie...
            Vous pouvez même <b>écrire des équations dans vos messages privés</b> ! <br>
            Le tout avec une syntax TeX que tout mathématicien se doit de connaître.
        </p>
        <div class="-image"><img src="/assets/img/feature_math.png"></div>
    </div>
    <div class="feature">
        <h2 class="-title">Draguez en toute sécurité.</h2>
        <p class="-desc">Notre équipe de modération travaille 24h/24
            pour vous bannir toute personne agressive ou abusive du site.<br>
            Vous pouvez bloquer un utilisateur abusif à tout moment, et celui-ci
            n'aura aucun moyen de vous contacter, ni de voir votre profil.<br>
        </p>
        <div class="-image"><img src="/assets/img/feature_block.png"></div>
    </div>
    <div class="feature">
        <h2 class="-title">Récuperez des statistiques pousées.</h2>
        <p class="-desc">
            Vous pouvez savoir qui a visité votre profil dernièrement, et vous avez accès à 
            un large panel de statistiques pour bien savoir <i>qui</i> est le genre de personne qui
            visite votre profil.<br> 
            Essayez de faire le plus de vues pour trouver votre mayorant(e) idéal(e) !
        </p>
        <div class="-image"><img src="/assets/img/feature_stats.png"></div>
    </div>
    <div class="feature">
        <h2 class="-title">Partagez vos moments en images.</h2>
        <p class="-desc">
            Chaque membre dispose d'une galerie pour mettre en ligne plusieurs images de leur choix.
            Montrez-vous sous votre meilleur jour (ou le pire, si vous préfèrez), et admirez les
            images des autres membres de TTM.
        </p>
        <div class="-image"><img src="/assets/img/feature_gallery.png"></div>
    </div>
</section>

<section id="feedbacks">
    <h2 style="color: darkblue">« Qu'avez-vous aimé sur le site de rencontre TrouveTonMayorant ? »</h2>
    <div class="-container">
        <div class="-profile">
            <div class="-image">
                <img src="/assets/img/reviewer_shrek.png" width="210" height="210">
            </div>
            <div class="-text">
                <p class="-name">Shrek - créature en couple libre de 22 ans</p>
                <span>« J'ai pu rencontrer 10 Nico toutes aussi appréciables les unes que les autres. »</span>
            </div>
        </div>
        <div class="-profile">
            <div class="-image">
                <img src="/assets/img/reviewer_pana.jpg" width="210" height="210">
            </div>
            <div class="-text">
                <p class="-name">Panayotis - homme célibataire de 35 ans</p>
                <span>« J'ai trouvé quelqu'un pour résoudre mon équation de Schrödinger indépendante du temps à une dimension, je pense l'épouser prochainement. »</span>
            </div>
        </div>
        <div class="-profile">
            <div class="-image">
                <img src="/assets/img/reviewer_luna.jpg" width="210" height="210">
            </div>
            <div class="-text">
                <p class="-name">Luna - femme célibataire de 28 ans</p>
                <span>« Depuis que j'ai rencontré <a href="#" onclick="flipRomu(true);return false;" class="romu">Romuald</a>
                    ma vie n'est plus la même... Merci TTM&nbsp;!&nbsp;»</span>
            </div>
        </div>
        <div class="-profile">
            <div class="-image">
                <img src="/assets/img/reviewer_david.jpg" width="210" height="210">
            </div>
            <div class="-text">
                <p class="-name">David - homme célibataire de 40 ans</p>
                <span>« J'ai pu découvrir que je préferais les hommes complexes, mais grâce à TTM, ils ne sont pas imaginaires pour autant. »</span>
            </div>
        </div>
        <div class="-profile">
            <div class="-image">
                <img src="/assets/img/reviewer_cecilia.jpg" width="210" height="210">
            </div>
            <div class="-text">
                <p class="-name">Cécilia - femme célibataire de 22 ans</p>
                <span>« En réalité je n'aime pas les maths, mais j'ai quand même réussi à trouver un mayorant pour m'apprendre le théorème de Cauchy-Schwarz. »</span>
            </div>
        </div>
        <div class="-profile">
            <div class="-image">
                <img src="/assets/img/randomprofile.png" width="210" height="210" style="image-rendering: pixelated;">
            </div>
            <div class="-text">
                <p class="-name">Fabien - homme célibataire de 47 ans</p>
                <span>« On peut même y trouver des ondes planes progressives, c'est merveilleux. »</span>
            </div>
        </div>
    </div>
</section>

<section id="actions">
    <div class="action">
        <p>Qu'attendez-vous ? <br> Inscrivez-vous dès maintenant !</p>
        <a href="/auth.php?register" class="sub">
            S'inscrire
        </a>
    </div>

    <div class="-sep"></div>

    <div class="action">
        <p>Déjà membre de TTM ?</p>
        <a href="/auth.php">
            <button class="sub">Se connecter</button>
        </a>
    </div>
</section>