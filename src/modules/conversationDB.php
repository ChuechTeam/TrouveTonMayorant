<?php

namespace ConversationDB;

/*
 * Structure of the database :
 * - id => string
 * - userId1 => int
 * - userId2 => int
 * - messages => array of
 *   [
 *       id => int
 *       author => int
 *       content => string
 *   ]
 * - deleteEvents => array of
 *   [
 *      deletedId => int
 *      lastMsgId => int
 *   ]
 * - msgIdSeq => int
 * - revision => int
 */

require_once __DIR__ . "/db.php";

const CONV_DIR = __DIR__ . "/../../conversations";

const REV_FIRST = 1;
const REV_DELETE_EVENTS = 2;
const REV_LAST = REV_DELETE_EVENTS;

function _path(string $id): string {
    return CONV_DIR . "/$id.json";
}

function id(int $u1, int $u2): string {
    $small = min($u1, $u2);
    $big = max($u1, $u2);
    return $small . '_' . $big;
}

/**
 * Crée une nouvelle boîte de messagerie entre les utilisateurs `$u1` et `$u2`.
 * Si la messagerie existe déjà, aucune action n'est effectuée.
 * Renvoie l'id de la messagerie.
 */
function create(int $u1, int $u2): string {
    if (!file_exists(CONV_DIR)) {
        mkdir(CONV_DIR, 0700, true);
    }

    $id = id($u1, $u2);
    $path = _path($id);

    if (!file_exists($path)) {
        $res = \DB\create($path, [
            "id" => $id,
            "userId1" => $u1,
            "userId2" => $u2,
            "messages" => [],
            "deleteEvents" => [],
            "msgIdSeq" => 1,
            "revision" => REV_LAST
        ]);

        if ($res === \DB\CREATE_ERROR) {
            throw new \RuntimeException("Failed to create conversation file!");
        }
    }

    return id($u1, $u2);
}

function find(string $id): ?array {
    $conv = null;
    $handle = null;

    if (_read(_path($id), $handle, $conv, true)) {
        \DB\close($handle);
    }

    return $conv;
}

function existingId(int $u1, int $u2): ?string {
    $id = id($u1, $u2);
    if (file_exists(_path($id))) {
        return $id;
    } else {
        return null;
    }
}

/**
 * Adds a new message to an existing conversation.
 *
 * @param string $id the id of the conversation
 * @param int $author the user id of the author
 * @param string $content the message content
 * @param array|null $conv set to the new conversation if the operation succeeded
 * @return false|int the id of the new message if the operation succeeded, false otherwise
 */
function addMessage(string $id, int $author, string $content, array &$conv = null) {
    $handle = null;
    if (_read(_path($id), $handle, $conv)) {
        $id = $conv["msgIdSeq"];
        $conv["msgIdSeq"]++;

        $conv["messages"][] = [
            "id" => $id,
            "author" => $author,
            "content" => $content,
        ];

        if (\DB\close($handle, $conv)) {
            return $id;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Deletes a message from a conversation. Does nothing if the message doesn't exist.
 *
 * @param string $convId the id of the conversation
 * @param int $msgId the id of the message to delete
 * @param array|null $conv set to the new conversation if the conversation exists
 * @return bool true if a message has been deleted, false otherwise (conversation or message not found)
 */
function deleteMessage(string $convId, int $msgId, array &$conv = null): bool {
    $handle = null;
    if (_read(_path($convId), $handle, $conv)) {
        $list = &$conv["messages"];

        // Do a linear search to find the message to delete.
        $del = false;
        $lastMsgId = null;
        for ($i = 0; $i < count($list); $i++) {
            if ($list[$i]["id"] == $msgId) {
                // Extract the last message id at this point in time.
                // This will be used by the server to send what messages have been deleted,
                // to avoid sending old delete events that the client doesn't care about.
                $lastMsgId = $list[count($list) - 1]["id"] ?? null;
                // Remove the message from the list.
                array_splice($list, $i, 1);
                $del = true;
                break;
            }
        }

        if ($del) {
            $conv["deleteEvents"][] = [
                "deletedId" => $msgId,
                "lastMsgId" => $lastMsgId
            ];
            return \DB\close($handle, $conv);
        } else {
            return \DB\close($handle);
        }
    } else {
        return false;
    }
}

function upgradeAll() {
    $ok = true;
    foreach (\DB\listFiles(CONV_DIR) as $file) {
        $fp = rtrim(CONV_DIR, "/\\") . "/$file";
        if (is_file($fp) && pathinfo($fp, PATHINFO_EXTENSION) == "json") {
            $ok &= _read($fp, $handle, $conv);
            $ok &= \DB\close($handle);

            if (!$ok) {
                trigger_error("Upgrade failed for file $file!", E_USER_ERROR);
            }
        }
    }
}

function _upgrade(array &$conv): bool {
    $rev = $conv["revision"] ?? null;
    if ($rev === null) {
        throw new \RuntimeException("Revision not found in conversation, file is probably corrupted!");
    }
    if ($rev === REV_LAST) {
        return false;
    }

    while ($rev < REV_LAST) {
        $rev++;
        switch ($rev) {
            case REV_DELETE_EVENTS:
                $conv["deleteEvents"] = [];
                break;
        }
    }

    $conv["revision"] = REV_LAST;

    return true;
}

function _read(string $path, &$handle, ?array &$conv = null, bool $readOnly = false): bool {
    return \DB\read($path, "ConversationDB\_upgrade", $handle, $conv, null, $readOnly);
}