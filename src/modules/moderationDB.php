<?php

namespace ModerationDB;

/*
 * Structure :
 * reports => tableau de [
 *     id => int
 *     convId => string
 *     msgId => int
 *     userId => int
 *     reason => string
 * ]
 * bannedEmails => tableau assoc [
 *     [email] => 1
 * ]
 * reportIdSeq => int
 * revision => int
 */

const PATH = __DIR__ . "/../../moderation.json";

const REV_FIRST = 1;
const REV_LAST = REV_FIRST;

$handle = null;
$data = null;
$readOnly = false;
$dirty = false;
$shutdownRegistered = false;

function &load(bool $ro = false): array {
    global $handle;
    global $data;
    global $readOnly;
    global $shutdownRegistered;

    if ($data === null) {
        if (!_read(PATH, $handle, $data, $ro)) {
            // On crée le fichier pour la première fois et on le relit juste après.
            trigger_error("Creating moderation database for the first time.");
            $ok = file_put_contents(PATH, json_encode(
                [
                    "reports" => [],
                    "bannedEmails" => [],
                    "reportIdSeq" => 1,
                    "revision" => REV_LAST
                ]
            ),  LOCK_EX);
            if (!$ok) {
                throw new \RuntimeException("Failed to create the moderation database.");
            }

            return load($readOnly);
        }

        $readOnly = $ro;

        if (!$shutdownRegistered) {
            register_shutdown_function(function () {
                unload();
            });
            $shutdownRegistered = true;
        }
    }

    return $data;
}

function save() {
    global $handle;
    global $data;
    global $dirty;
    global $readOnly;

    if (!$dirty || !$data || !$handle || $readOnly) {
        return;
    }

    if (!_save($handle, $data)) {
        throw new \RuntimeException("Failed to save moderation database.");
    }

    $dirty = false;
}

function unload() {
    global $handle;
    global $data;
    global $dirty;
    global $readOnly;

    if (!$handle) {
        return;
    }

    if ($dirty) {
        save();
    }

    _close($handle);

    $data = null;
    $handle = null;
    $dirty = false;
    $readOnly = false;
}

function queryReports(): array {
    $ud = &load();
    return $ud["reports"];
}

function findReport(int $reportId): ?array {
    $ud = &load();
    return $ud["reports"][$reportId] ?? null;
}

function addReport(string $convId, int $msgId, int $userId, string $reason) {
    global $dirty;
    global $readOnly;

    if ($readOnly) {
        throw new \RuntimeException("Cannot add reports in read-only mode.");
    }

    $ud = &load();
    $id = $ud["reportIdSeq"]++;
    $rep = [
        "id" => $id,
        "convId" => $convId,
        "msgId" => $msgId,
        "userId" => $userId,
        "reason" => $reason
    ];
    $ud["reports"][] = $rep;

    $dirty = true;
}

function deleteReport(int $reportId): bool {
    global $dirty;
    global $readOnly;

    if ($readOnly) {
        throw new \RuntimeException("Cannot delete reports in read-only mode.");
    }

    $ud = &load();
    $reps = &$ud["reports"];
    $found = false;

    for ($i = 0; $i < count($reps); $i++) {
        if ($reps[$i]["id"] == $reportId) {
            array_splice($reps, $i, 1);
            $found = true;
            break;
        }
    }

    if ($found) {
        $dirty = true;
        return true;
    } else {
        return false;
    }
}

function banEmail(string $email) {
    global $dirty;
    global $readOnly;

    if ($readOnly) {
        throw new \RuntimeException("Cannot ban emails in read-only mode.");
    }

    $ud = &load();
    if (!isset($ud["bannedEmails"][$email])) {
        $ud["bannedEmails"][$email] = 1;
        $dirty = true;
    }
}

function unbanEmail(string $email) {
    global $dirty;
    global $readOnly;

    if ($readOnly) {
        throw new \RuntimeException("Cannot ban emails in read-only mode.");
    }

    $ud = &load();
    if (isset($ud["bannedEmails"][$email])) {
        unset($ud["bannedEmails"][$email]);
        $dirty = true;
    }
}

function emailBanned(string $email): bool {
    $ud = &load();
    return isset($ud["bannedEmails"][$email]);
}

/*
 * Copié de ConversationDB pour l'instant
 */

function _upgrade(array &$db): bool {
    $rev = $db["revision"] ?? null;
    if ($rev === null) {
        throw new \RuntimeException("Revision not found in database, file is probably corrupted!");
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

function _read(string $path, &$handle, ?array &$db = null, bool $readOnly = false): bool {
    $handle = @fopen($path, $readOnly ? "r" : "r+");
    if ($handle === false) {
        return false;
    }

    $lockOk = flock($handle, $readOnly ? LOCK_SH : LOCK_EX);
    if ($lockOk === false) {
        fclose($handle);
        return false;
    }

    $data = fread($handle, _fSize($handle));
    if ($data === false) {
        _close($handle);
        return false;
    }

    $db = json_decode($data, true);
    if (_upgrade($db)) {
        if ($readOnly) {
            throw new \RuntimeException("Cannot upgrade database in read-only mode.");
        }

        return _save($handle, $db);
    }

    return true;
}

function _save($handle, array $db): bool {
    $json = json_encode($db);
    $ok = true;
    $ok &= fseek($handle, 0) === 0;
    $ok &= ftruncate($handle, strlen($json)) !== false;
    $ok &= fwrite($handle, $json) !== false;

    return $ok;
}

function _close($handle, ?array $db = null): bool {
    $ok = true;
    if ($db !== null) {
        $ok = _save($handle, $db);
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