<?php

require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/conversationDB.php";
require_once __DIR__ . "/_chatMessage.php";

/**
 * Print le HTML complet d'une conversation (avec les messages et le formulaire pour envoyer). 
 * @param string|null $convId l'identifiant de la conversation
 * @return void
 */
function conversation(?string $convId, ?int $viewerId, bool $listEmpty = false) {
    if ($convId === null) {
       ?>
        <div class="chat-conversation -empty">
            <?php if ($listEmpty): ?>
                <p>Vous n'avez pas de conversation.</p>
                <p><a href="/member-area">Recherchez des profils</a> sur la page d'accueil pour commencer à discuter !</p>
            <?php else: ?>
                <p>Sélectionnez une conversation pour commencer à discuter !</p>
            <?php endif; ?>
        </div>
<?php
        return;
    }

    $conv = ConversationDB\find($convId);
    if ($conv === null) {
        echo <<<HTML
        <div class="chat-conversation -not-found">
            Conversation introuvable.
        </div>
HTML;
        return;
    }

    // Empêcher d'envoyer des messages si un blocage est effectué.
    $bs = \User\BS_NO_BLOCK;
    if ($viewerId == $conv["userId1"]) {
        $bs = \User\blockStatus($viewerId, $conv["userId2"]);
    } else if ($viewerId == $conv["userId2"]) {
        $bs = \User\blockStatus($viewerId, $conv["userId1"]);
    }
    ?>
    <div class="chat-conversation" data-id="<?= $convId ?>">
        <div class="-messages">
            <?php
            foreach ($conv["messages"] as $msg) {
                chatMessage($msg["id"], $msg["author"], $msg["content"]);
            }
            ?>
        </div>
        <?php if ($bs === \User\BS_NO_BLOCK): ?>
            <form class="-send">
                <input type="text" name="content" placeholder="Message..." class="-msg-input" maxlength="2000" autocomplete="off">
                <button type="submit"><span class="material-symbols-rounded -icon">send</span></button>
            </form>
        <?php else: ?>
            <div class="-send-blocked">
                <span class="icon -inl">lock</span> Vous ne pouvez pas envoyer de message à cet utilisateur.
            </div>
        <?php endif; ?>
    </div>
    <?php
}
?>
