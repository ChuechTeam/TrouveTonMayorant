<?php

require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/conversationDB.php";
require_once __DIR__ . "/_chatMessage.php";

function conversation(?string $convId, bool $admin = false) {
    if ($convId === null) {
        echo <<<HTML
        <div class="chat-conversation -empty">
            <div>Commencez ou sélectionnez une conversation !</div>
        </div>
HTML;
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

    ?>
    <div class="chat-conversation" data-id="<?= $convId ?>">
        <div class="-messages">
            <?php
            foreach ($conv["messages"] as $msg) {
                chatMessage($msg["id"], $msg["author"], $msg["content"]);
            }
            ?>
        </div>
        <?php if (!$admin): ?>
            <form class="-send">
                <input type="text" name="content" placeholder="Message..." class="-msg-input">
                <button type="submit">Envoyer</button>
            </form>
        <?php endif; ?>
    </div>
    <?php
} ?>
