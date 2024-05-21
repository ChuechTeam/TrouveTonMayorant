<?php

require_once "_common.php";
require_once "../modules/moderationDB.php";
require_once "../modules/conversationDB.php";
require_once "../member-area/_chatMessage.php";
Templates\member("Admin !!!");
Templates\addStylesheet("/assets/style/report-page.css");

ModerationDB\load(true); // Read-only mode for (slightly) better performance
$reports = ModerationDB\queryReports();

$completeReports = [];
foreach ($reports as $r) {
    $u = UserDB\findById($r["userId"]);
    $msgExists = findMsgData($r["convId"], $r["msgId"], $msgContent, $msgAuthor, $msgAuthorId);
    $completeReports[] = [
        "id" => $r["id"],
        "convId" => $r["convId"],
        "msgId" => $r["msgId"],
        "reason" => $r["reason"],
        "msgExists" => $msgExists,
        "msgContent" => $msgContent,
        "msgAuthor" => $msgAuthor,
        "msgAuthorId" => $msgAuthorId,
        "user" => $u,
    ];
}

function findMsgData($convId, $msgId, &$content, &$author, &$authorId)
{
    $content = null;
    $author = null;

    $conv = \ConversationDB\find($convId);
    if ($conv === null) {
        return false;
    }

    foreach ($conv["messages"] as $m) {
        if ($m["id"] == $msgId) {
            $content = $m["content"];
            $authorId = $m["author"];
            $author = UserDB\findById($m["author"]);
        }
    }

    return true;
}

function userLink($u)
{
    if ($u === null) {
        return "[Utilisateur supprimé]";
    }
    return "<a href=\"/member-area/userProfile.php?id=" . $u["id"] . "\">" . htmlspecialchars($u["firstName"]) . " " . htmlspecialchars($u["lastName"]) . "</a>";
}
Templates\appendParam("head", '<script src="/scripts/report.js" type="module" defer></script>');
?>

<h1>Liste des signalements</h1>
<ul class="report-list">
    <?php foreach ($completeReports as $r) : ?>
        <li class="report" data-id="<?= $r["id"] ?>" data-conv-id="<?= $r["convId"] ?>">
            <header>Signalement de <?= userLink($r["user"]) ?> sur un message de <?= userLink($r["msgAuthor"]) ?></header>
            <div class="-context">
                <blockquote class="-reason"><?= htmlspecialchars($r["reason"]) ?></blockquote>
                <?php if ($r["msgExists"]) : ?>
                    <?php chatMessage($r["msgId"], $r["msgAuthorId"], $r["msgContent"], true) ?>
                <?php else : ?>
                   <br> [Message supprimé]
                <?php endif; ?>
            </div>
            <div class="-controls">
                <button class="-see-conv">Voir la conversation</button>
                <button class="-close-report">Clore le signalement</button>
            </div>
        </li>
    <?php endforeach; ?>
</ul>