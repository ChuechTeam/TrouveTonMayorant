<?php

require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";

/**
 * Print le HTML d'un message de chat dans une conversation.
 * Requiert une session active et connectée (pour savoir si le message provient de soi).
 * @param int $msgId l'id du message
 * @param int $userId l'id de l'auteur
 * @param string $content le contenu du message
 * @param bool $externalView si le message est vu à l'extérieur de la conversation (exemple : depuis la liste de signalements)
 * @return void
 */
function chatMessage(int $msgId, int $userId, string $content, bool $externalView = false) {
    $myId = \UserSession\loggedUserId();
    $author = \UserDB\findById($userId);
    $authorName = $author == null ? "Utilisateur supprimé" : ($author["firstName"] . ' ' . $author["lastName"]);
    $msgClass = $externalView || $author == null || $myId !== $author["id"] ? " -other" : " -me";

    $showDelete = !$externalView 
        && (($author !== null && $myId === $author["id"]) || \User\level($myId) >= \User\LEVEL_ADMIN);
    
    $showReport = !$externalView && $author !== null && $myId !== $author["id"]
        && (User\level($myId) < \User\LEVEL_ADMIN);

    ?>
    <article class="chat-message<?= $msgClass ?>" data-id="<?= $msgId ?>">
        <header class="-head">
            <div class="-author"><?= htmlspecialchars($authorName) ?></div>
            <div class="-controls">
                <?php if ($showDelete): ?>
                    <button class="-delete"><span class="material-symbols-rounded -icon">delete</span></button>
                <?php endif; ?>
                <?php if ($showReport): ?>
                    <button class="-report"><span class="material-symbols-rounded -icon">flag</span></button>
                <?php endif; ?>
            </div>
        </header>
        <p class="-content"><?= htmlspecialchars($content) ?></p>
    </article>
<?php } ?>
