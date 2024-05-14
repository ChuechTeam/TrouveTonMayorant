<?php

require "_common.php";
require_once "_profileCard.php";
require_once "_conversation.php";

Templates\member("Messagerie");
Templates\appendParam("head", '<script src="/scripts/chat.js" type="module" defer></script>');


$selectedConvId = null;

if (!empty($_GET["startNew"])) {
    $otherId = intval($_GET["startNew"]);
    if ($otherId !== $user["id"]) {
        // Si la conversation existe déjà, elle sera mise dans selectedConvId.
        User\startConversation($user["id"], $otherId, $selectedConvId, $user);
    }
}

if ($selectedConvId === null && !empty($_GET["conv"])) {
    $selectedConvId = $_GET["conv"];
}

$conversations = [];
foreach ($user["conversations"] as $convId) {
    $conv = ConversationDB\find($convId);
    if ($conv !== null) {
        $otherUserId = $conv["userId1"] == $user["id"] ? $conv["userId2"] : $conv["userId1"];
        $otherUser = UserDB\findById($otherUserId);
        $conversations[] = [
            "id" => $convId,
            "userName" => $otherUser !== null ? $otherUser["firstName"] . " " . $otherUser["lastName"] : "Utilisateur supprimé",
            "lastMsg" => !empty($conv["messages"]) ?  $conv["messages"][count($conv["messages"]) - 1]["content"] : "",
            "selectedClass" => $selectedConvId === $convId ? " -selected" : ""
        ];
    }
}
?>

<div id="chat-box">
    <aside class="-people-slot">
        <ul class="chat-people">
            <?php foreach ($conversations as $conv): ?>
                <li class="chat-person<?= $conv["selectedClass"] ?>" data-id="<?= $conv["id"] ?>">
                    <div class="-name"><?= htmlspecialchars($conv["userName"]) ?></div>
                    <div class="-last-msg"><?= htmlspecialchars($conv["lastMsg"]) ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
        <button id="create-conv">Commencer une conversation</button>
    </aside>
    <section class="-conversation-slot">
        <?php conversation($selectedConvId); ?>
    </section>
</div>