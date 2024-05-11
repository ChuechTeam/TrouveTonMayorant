<?php

namespace ConversationDB;

/*
 * Tableau assoc conversation :
 * - id => string
 * - userId1 => int
 * - userId2 => int
 * - messages => tableau de
 *   [
 *       id => int
 *       user => int
 *       content => string
 *   ]
 * - msgIdSeq => int
 * - revision => int
 */

const CONV_DIR = __DIR__ . "/../../conversations";

const REV_FIRST = 1;
const REV_LAST = REV_FIRST;

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
        $data = [
            "id" => $id,
            "userId1" => $u1,
            "userId2" => $u2,
            "messages" => [],
            "msgIdSeq" => 1,
            "revision" => REV_LAST
        ];
        file_put_contents($path, json_encode($data), LOCK_EX);
    }

    return id($u1, $u2);
}

function find(string $id): ?array {
    $conv = null;
    $handle = null;

    if (_read($id, $handle, $conv, true)) {
        _close($handle);
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

// Renvoie false s'il y a un problème
// Sinon, renvoie l'identifiant du message
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

        if (_close($handle, $conv)) {
            return $id;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

function deleteMessage(int $convId, int $msgId, array &$conv = null): bool {
    $handle = null;
    if (_read(_path($convId), $handle, $conv)) {
        $list = &$conv["messages"];

        $del = false;
        for ($i = 0; $i < count($list); $i++) {
            if ($list[$i]["id"] == $msgId) {
                array_splice($list, $i, 1);
                $del = true;
                break;
            }
        }

        if ($del) {
            return _close($handle, $conv);
        } else {
            return _close($handle);
        }
    } else {
        return false;
    }
}

function upgradeAll() {
    $ok = true;

    foreach (scandir(CONV_DIR) as $file) {
        if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === "json") {
            $ok &= _read($file, $handle, $conv);
            $ok &= _close($handle);

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
        // mettre les actions de migration vers la nouvelle version ici
        $rev++;
    }

    return true;
}

function _read(string $path, &$handle, ?array &$conv = null, bool $readOnly = false): bool {
    $handle = @fopen($path, $readOnly ? "r" : "r+");
    if ($handle === false) {
        return false;
    }

    $lockOk = flock($handle, $readOnly ? LOCK_SH : LOCK_EX);
    if ($lockOk === false) {
        fclose($handle);
        return false;
    }

    $data = fread($handle, _fSize($path));
    if ($data === false) {
        _close($handle);
        return false;
    }

    $conv = json_decode($data, true);
    if (_upgrade($conv)) {
        if ($readOnly) {
            throw new \RuntimeException("Cannot upgrade conversation in read-only mode.");
        }

        return _save($handle, $conv);
    }

    return true;
}

function _save($handle, array $conv): bool {
    $json = json_encode($conv);
    $ok = true;
    $ok &= fseek($handle, 0) === 0;
    $ok &= ftruncate($handle, strlen($json)) !== false;
    $ok &= fwrite($handle, $json) !== false;

    return $ok;
}

function _close($handle, ?array $conv = null): bool {
    $ok = true;
    if ($conv !== null) {
        $ok = _save($handle, $conv);
    }

    $ok &= flock($handle, LOCK_UN);
    $ok &= fclose($handle);

    return $ok;
}

function _fSize($handle) {
    $stat = fstat($handle);
    if ($stat === false) {
        throw new \RuntimeException("Failed to gather the file size!");
    }
    return $stat['size'];
}