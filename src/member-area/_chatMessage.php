<?php

require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";

/**
 * Prints the HTML of a chat message in a conversation.
 * Requires an active and logged-in session (to know if the message comes from oneself).
 * @param int $msgId the message id
 * @param int $userId the author's id
 * @param string $content the message content
 * @param bool $externalView if the message is viewed outside the conversation (example: from the reports list)
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
