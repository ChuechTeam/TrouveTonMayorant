<?php

$api = true;
require "../_common.php";
require_once "../_chatMessage.php";

/*
 * GET /member-area/api/convMessages.php
 * Sends the HTML of a conversation's messages, sent after a chosen message.
 *
 * Parameters (URL):
 * ?id: the conversation id
 * ?since: the id of the last seen message, messages with an id equal to or lower than this will not be sent
 *         (if not specified, all messages will be sent)
 *
 * Returns:
 * 200 OK: the HTML of the messages
 *         the headers First-Message-Id and Last-Message-Id are filled with their respective ids
 * 204 No Content: empty response, no messages found
 *
 * ---
 *
 * POST /member-area/api/convMessages.php
 * Sends a message in the conversation, and sends all messages sent after a chosen message.
 *
 * Parameters (URL):
 * ?id: the conversation id
 * ?since=int: see the GET method
 *
 * Parameters (JSON):
 * {
 *    "content": string // the message's content
 * }
 *
 * Returns:
 * 200 OK: the HTML of the messages, including the new message (see GET)
 *
 * ---
 *
 * DELETE /member-area/api/convMessages.php
 * Deletes a message from the conversation.
 *
 * Parameters (URL):
 * ?id: the conversation id
 * ?msgId: the message to delete
 *
 * Returns:
 * 200 OK: the message has been deleted
 *
 */
UserSession\requireLevel(User\LEVEL_SUBSCRIBER);

// Validate the id
if (empty($_GET["id"])) {
    bail(400);
}

// Get the conversation
$convId = $_GET["id"];
$conv = User\findConversation($user["id"], $convId);
if ($conv === null) {
    bail(404);
}

// Id of the last seen message
$since = is_numeric($_GET["since"] ?? null) ? intval($_GET["since"]) : null;

// Fills the Deleted-Messages header with a comma-separated list of the last deleted messages.
// The list only contains messages deleted AT THE SAME TIME OR AFTER the $since message.
// Already deleted messages can be sent when no messages have been deleted after the last deletion.
function fillDeletedMessagesHeader(array $conv, ?int $since) {
    if ($since != null) {
        $delMessagesIds = [];
        $ev = &$conv["deleteEvents"];
        for ($i = count($ev) - 1; $i >= 0; $i--) {
            // Stop there, this event is too old.
            if ($ev[$i]["lastMsgId"] < $since) {
                break;
            }

            $delMessagesIds[] = $ev[$i]["deletedId"];
        }

        if (!empty($delMessagesIds)) {
            $delStr = implode(",", $delMessagesIds);
            header("Deleted-Messages: " . $delStr);
        }
    }
}

// Prints out the last messages of the conversation, posted after the "since" message.
// Also fills the First-Message-Id and Last-Message-Id headers.
function lastMessages(array $conv, ?int $since)
{
    $first = null;
    $last = null;

    // Keep the chatMessage HTML inside a string
    // so we can edit the headers afterward.
    ob_start();

    foreach ($conv["messages"] as $msg) {
        if ($since === null || $msg["id"] > $since) {
            if ($first === null) {
                $first = $msg["id"];
            }
            $last = $msg["id"];

            chatMessage($msg["id"], $msg["author"], $msg["content"]);
        }
    }

    if ($first !== null) {
        header("First-Message-Id: " . $first);
    }
    if ($last !== null) {
        header("Last-Message-Id: " . $last);
    }

    if ($first === null && $last === null) {
        bail(204); // No content
    }

    echo ob_get_clean();
}

// Send the block status to the client so it knows if it can send messages again
if ($user["id"] == $conv["userId1"]) {
    $bs = \User\blockStatus($conv["userId1"], $conv["userId2"]);
} else if ($conv["userId2"] == $user["id"]) {
    $bs = \User\blockStatus($conv["userId2"], $conv["userId1"]);
} else {
    $bs = \User\BS_NO_BLOCK;
}
// Blocked --> header sent
// Not blocked --> no header
if ($bs !== \User\BS_NO_BLOCK) {
    header("Is-Blocked: true");
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    fillDeletedMessagesHeader($conv, $since);
    lastMessages($conv, $since);
} else if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if ($bs != \User\BS_NO_BLOCK) {
        bail(403); // Forbidden
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        bail(400);
    }

    $content = substr(trim($data["content"] ?? ""), 0, 2000);
    if (empty($content)) {
        bail(400);
    }

    $msgId = ConversationDB\addMessage($convId, $user["id"], $content, $conv);
    if ($msgId === false) {
        bail(500);
    }

    if ($since !== null) {
        fillDeletedMessagesHeader($conv, $since);
        lastMessages($conv, $since);
    } else {
        header("First-Message-Id: " . $msgId);
        header("Last-Message-Id: " . $msgId);
        fillDeletedMessagesHeader($conv, $since);

        chatMessage($msgId, $user["id"], $content);
    }
} else if ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    if (!is_numeric($_GET["msgId"] ?? null)) {
        bail(404);
    }

    $msgId = intval($_GET["msgId"]);

    // Make sure that we can delete this message
    // (It's a O(n) operation but... no worries, everything's fine)
    if (User\level($user["id"]) < User\LEVEL_ADMIN) {
        foreach ($conv["messages"] as $msg) {
            if ($msg["id"] === $msgId) {
                if ($msg["author"] !== $user["id"]) {
                    bail(403); // Forbidden
                }
                break;
            }
        }
    }

    if (!ConversationDB\deleteMessage($convId, $msgId, $conv)) {
        bail(404);
    }
} else {
    bail(405); // Method not allowed
}
