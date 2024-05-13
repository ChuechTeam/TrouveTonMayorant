<?php

require_once __DIR__ . "/../modules/user.php";
require_once __DIR__ . "/../modules/userSession.php";

function chatMessage(int $msgId, int $userId, string $content) {
    $myId = \UserSession\loggedUserId();
    $author = \UserDB\findById($userId);
    $authorName = $author == null ? "Utilisateur supprimé" : ($author["firstName"] . ' ' . $author["lastName"]);
    $msgClass = $author == null || $myId !== $author["id"] ? " -other" : " -me";
?>
    <article class="chat-message<?= $msgClass ?>" data-id="<?= $msgId ?>">
        <div class="-author"><?= htmlspecialchars($authorName) ?></div>
        <p class="-content"><?= htmlspecialchars($content) ?></p>
    </article>
<?php } ?>
