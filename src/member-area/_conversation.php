<?php

require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/conversationDB.php";
require_once __DIR__ . "/_chatMessage.php";

/**
 * Prints the entire HTML for a given conversation.
 * If the conversation id is null or couldn't be found, a message will be displayed.
 * If `$listEmpty` is true, a "go create a conversation" message will be shown; else,
 * the user will be invited to select a conversation.
 *
 * @param string|null $convId the conversation id, or null to get the default view
 * @param int|null $viewerId the id of the user viewing the conversation
 * @param bool $listEmpty whether to show a message if the conversation list is empty
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

    // Prevent sending messages when blocked.
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
                <button type="submit"><span class="icon">send</span></button>
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
