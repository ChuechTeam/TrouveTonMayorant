<?php

$api = true;
require "../_common.php";
require_once "../_chatMessage.php";

if (empty($_GET["id"])) {
    bail(400);
}

$convId = $_GET["id"];
$conv = User\findConversation($user["id"], $convId);

if ($conv === null) {
    bail(404);
}

// Id du dernier message vu
$since = is_numeric($_GET["since"] ?? null) ? intval($_GET["since"]) : null;

function lastMessages(array $conv, ?int $since) {
    if (!empty($conv["messages"])) {
        header("First-Message-Id: " . $conv["messages"][0]["id"]);
        header("Last-Message-Id: " . $conv["messages"][count($conv["messages"]) - 1]["id"]);
    }
    foreach ($conv["messages"] as $msg) {
        if ($since === null || $msg["id"] > $since) {
            chatMessage($msg["id"], $msg["author"], $msg["content"]);
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (empty($conv["messages"])) {
        bail(204); // No content
    }

    lastMessages($conv, $since);
} else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        bail(400);
    }

    $content = trim($data["content"] ?? "");
    if (empty($content)) {
        bail(400);
    }

    $msgId = ConversationDB\addMessage($convId, $user["id"], $content, $conv);
    if ($msgId === false) {
        bail(500);
    }

    if ($since !== null) {
        lastMessages($conv, $since);
    } else {
        chatMessage($msgId, $user["id"], $content);
    }
} else {
    bail(405); // Method not allowed
}