<?php

require "_common.php";
require_once "_profileCard.php";
require_once "_conversation.php";

Templates\member("Messagerie");
Templates\appendParam("head", '<script src="/scripts/chat.js" type="module" defer></script>');

// Pouvoir voir la messagerie de quelqu'un d'autre si l'on est admin uniquement
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

$selectedConvId = null;

if (!empty($_GET["startNew"])) {
    $otherId = intval($_GET["startNew"]);
    if ($otherId !== $u["id"]) {
        // Si la conversation existe déjà, elle sera mise dans selectedConvId.
        User\startConversation($u["id"], $otherId, $selectedConvId, $u);
    }
}

if ($selectedConvId === null && !empty($_GET["conv"])) {
    $selectedConvId = $_GET["conv"];
}

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
?>

<?php if ($impersonate): ?>
    <h1 id="impersonate-title">Messagerie de <?= $u["firstName"] .' '. $u["lastName"] ?></h1>
<?php endif; ?>

<div id="chat-box">
    <aside class="-people-slot">
        <ul class="chat-people">
            <?php foreach ($conversations as $conv): ?>
                <li class="chat-person<?= $conv["selectedClass"] ?>" data-id="<?= $conv["id"] ?>">
                    <div class="-name"><?= htmlspecialchars($conv["userName"]) ?></div>
                    <div class="-last-msg"><?= htmlspecialchars($conv["lastMsg"]) ?></div>
                    <?php if (!empty($conv["profileLink"])): ?>
                        <a class="-profile-link" href="<?= $conv["profileLink"] ?>" title="Voir le profil">
                            <span class="material-symbols-rounded -icon">account_circle</span>
                        </a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </aside>
    <section class="-conversation-slot">
        <?php conversation($selectedConvId, $user["id"], empty($conversations)); ?>
    </section>
</div>