<?php

require "_common.php";
require_once "_profileCard.php";
require_once "_conversation.php";

Templates\member("Messagerie");
Templates\appendParam("head", '<script src="/scripts/chat.js" type="module" defer></script>');

// Allow viewing conversations of other users if the user is an admin.
// That's by all means very unethical, but it's required sooo :))
$u = null;
$impersonate = false;
if (isset($_GET["impersonate"]) && UserSession\level() >= \User\LEVEL_ADMIN) {
    $u = UserDB\findById(intval($_GET["impersonate"]));
    $impersonate = true;
    if ($u === null) {
        echo '<div class="not-found">Utilisateur introuvable</div>';
        http_response_code(404);
        exit();
    }
} else {
    $u = $user;
}

$hasRights = $impersonate || \UserSession\level() >= \User\LEVEL_SUBSCRIBER;
$selectedConvId = null;

if (!empty($_GET["startNew"]) && $hasRights) {
    $otherId = intval($_GET["startNew"]);
    if ($otherId !== $u["id"]) {
        // If the conversation already exists, it will be put into $selectedConvId,
        // so there's no need to check if the operation succeeded.
        User\startConversation($u["id"], $otherId, $selectedConvId, $u);
    }
}

if ($selectedConvId === null && !empty($_GET["conv"])) {
    $selectedConvId = $_GET["conv"];
}

if ($hasRights) {
    $conversations = [];
    foreach ($u["conversations"] as $convId) {
        $conv = ConversationDB\find($convId);
        if ($conv !== null) {
            $otherUserId = $conv["userId1"] == $u["id"] ? $conv["userId2"] : $conv["userId1"];
            $otherUser = UserDB\findById($otherUserId);
            $conversations[] = [
                "id" => $convId,
                "userName" => $otherUser !== null ? $otherUser["firstName"] . " " . $otherUser["lastName"] : "Utilisateur supprimé",
                "lastMsg" => !empty($conv["messages"]) ? $conv["messages"][count($conv["messages"]) - 1]["content"] : "",
                "selectedClass" => $selectedConvId === $convId ? " -selected" : "",
                "profileLink" => $otherUser !== null ? "/member-area/userProfile.php?id=$otherUserId" : null
            ];
        }
    }
}

$boxClass = $hasRights ? "" : " -not-sub";
?>

<?php if ($impersonate) : ?>
    <h1 id="impersonate-title">Messagerie de <?= $u["firstName"] . ' ' . $u["lastName"] ?></h1>
<?php endif; ?>

<div id="chat-box" class="<?= $boxClass ?>">
    <?php if ($hasRights) : ?>
        <aside class="-people-slot">
            <ul class="chat-people">
                <?php foreach ($conversations as $conv) : ?>
                    <li class="chat-person<?= $conv["selectedClass"] ?>" data-id="<?= $conv["id"] ?>">
                        <div class="-name"><?= htmlspecialchars($conv["userName"]) ?></div>
                        <div class="-last-msg"><?= htmlspecialchars($conv["lastMsg"]) ?></div>
                        <?php if (!empty($conv["profileLink"])) : ?>
                            <a class="-profile-link" href="<?= $conv["profileLink"] ?>" title="Voir le profil">
                                <span class="icon">account_circle</span>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <section class="-conversation-slot">
            <?php conversation($selectedConvId, $user["id"], empty($conversations)); ?>
        </section>
    <?php else : ?>
        <aside class="-people-slot">
            <ul class="chat-people">
                <?php for ($i = 0; $i < 8; $i++): ?>
                    <li class="chat-person <?= $i == 2 ? " -selected" : "" ?>">
                        <div class="-name"><?php for ($j=0; $j < rand(1, 3); $j++) echo "Quelqu'un $j " ?></div>
                        <div class="-last-msg"><?php for ($j=0; $j < rand(0, 9); $j++) echo 'Texte ' ?></div>
                    </li>
                <?php endfor; ?>
            </ul>
        </aside>
        <section class="-conversation-slot">
            <article class="chat-message -me">
                <header class="-head">
                    <div class="-author">P1</div>
                </header>
                <p class="-content">Intégrale ipsum constante \( \delta \), où \( \sigma \) tend vers l'infini. La limite de \( f(x) \) quand \( x \) approche de zéro est **indéterminée**. En considérant le théorème de Pythagore, \( a^2 + b^2 = c^2 \)</p>
            </article>
            <article class="chat-message -other">
                <header class="-head">
                    <div class="-author">P2</div>
                </header>
                <p class="-content">
                Quadratum infinitum divisibilis, in numeris primis consistit. Axiomata Euclidis, fundamentum theoriae numerorum, sine finem veritatis explorant.
                </p>
            </article>
            <article class="chat-message -me">
                <header class="-head">
                    <div class="-author">P1</div>
                </header>
                <p class="-content">
                Integralis indefinita, calculus differentialis, limites transcendunt. Conjectura Poincaré, simplex et complexa, topologiae faciem revelat. In geometria fractali, Mandelbrot set infinitam complexitatem ostendit. 
                </p>
            </article>

            <article class="chat-message -other">
                <header class="-head">
                    <div class="-author">P2</div>
                </header>
                <p class="-content">
                In geometria fractali, Mandelbrot set infinitam complexitatem ostendit.
                “Fonction différentiable en un point, la dérivée révèle la pente d’une courbe. Le théorème de Rolle garantit qu’il existe un point où la dérivée est nulle. Intégrale définie, bornes et aires, dans le domaine de l’analyse. Séries infinies, convergence ou divergence, un mystère mathématique.
                </p>
            </article>

            <article class="chat-message -me">
                <header class="-head">
                    <div class="-author">P1</div>
                </header>
                <p class="-content">
                Théorème de Fermat, points critiques, maxima et minima, optimisation. Équations différentielles, solutions générales, équilibres stables. Théorie des ensembles, cardinalité, infini, Cantor l’a exploré. Probabilités, espérance, variance, lois discrètes et continues. Algèbre linéaire, matrices, vecteurs, transformations linéaires. Géométrie projective, points à l’infini, dualité, une vision différente.                
            </p>
            </article>
        </section>
        <div class="-overlay">
            <div class="-contents">
                <div class="icon">lock</div>
                <h2>Vous n'êtes pas abonné à TTM <img src="/assets/sup.svg" class="-sup"></h2>
                <p>Abonnez-vous pour accéder à la messagerie et rencontrer le mayorant de vos rêves !</p>
            </div>
        </div>
    <?php endif; ?>
</div>