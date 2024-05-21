<?php

namespace ModerationDB;

/*
 * Structure of the database :
 * reports => associative array [id] => [
 *     id => int
 *     convId => string
 *     msgId => int
 *     userId => int
 *     reason => string
 * ]
 * bannedEmails => associative array [
 *     [email] => 1
 * ]
 * reportIdSeq => int
 * revision => int
 */

const PATH = __DIR__ . "/../../moderation.json";

const REV_FIRST = 1;
const REV_LAST = REV_FIRST;

$modHandle = null;
$modData = null;
$modReadOnly = false;
$modDirty = false;
$modShutdownRegistered = false;

function &load(bool $ro = false): array {
    global $modHandle;
    global $modData;
    global $modReadOnly;
    global $modShutdownRegistered;
    
    if ($modData === null) {
        if (!_read(PATH, $modHandle, $modData, $ro)) {
            // We create the file once to read it afterward
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

            return load($modReadOnly);
        }
        
        $modReadOnly = $ro;

        if (!$modShutdownRegistered) {
            register_shutdown_function(function () {
                unload();
            });
            $modShutdownRegistered = true;
        }
    }

    return $modData;
}

function save() {
    global $modHandle;
    global $modData;
    global $modDirty;
    global $modReadOnly;

    if (!$modDirty || !$modData || !$modHandle || $modReadOnly) {
        return;
    }

    if (!_save($modHandle, $modData)) {
        throw new \RuntimeException("Failed to save moderation database.");
    }

    $modDirty = false;
}

function unload() {
    global $modHandle;
    global $modData;
    global $modDirty;
    global $modReadOnly;
    
    if (!$modHandle) {
        return;
    }
    
    if ($modDirty) {
        save();
    }

    _close($modHandle);

    $modData = null;
    $modHandle = null;
    $modDirty = false;
    $modReadOnly = false;
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
    global $modDirty;
    global $modReadOnly;

    if ($modReadOnly) {
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
    $ud["reports"][$id] = $rep;

    $modDirty = true;
}

function deleteReport(int $reportId): bool {
    global $modDirty;
    global $modReadOnly;

    if ($modReadOnly) {
        throw new \RuntimeException("Cannot delete reports in read-only mode.");
    }

    $ud = &load();
    $reps = &$ud["reports"];
    
    if (isset($reps[$reportId])) {
        unset($reps[$reportId]);
        $modDirty = true;
        return true;
    } else {
        return false;
    }
}

function banEmail(string $email) {
    global $modDirty;
    global $modReadOnly;

    if ($modReadOnly) {
        throw new \RuntimeException("Cannot ban emails in read-only mode.");
    }

    $ud = &load();
    if (!isset($ud["bannedEmails"][$email])) {
        $ud["bannedEmails"][$email] = 1;
        $modDirty = true;
    }
}

function unbanEmail(string $email) {
    global $modDirty;
    global $modReadOnly;

    if ($modReadOnly) {
        throw new \RuntimeException("Cannot ban emails in read-only mode.");
    }

    $ud = &load();
    if (isset($ud["bannedEmails"][$email])) {
        unset($ud["bannedEmails"][$email]);
        $modDirty = true;
    }
}

function emailBanned(string $email): bool {
    $ud = &load();
    return isset($ud["bannedEmails"][$email]);
}

/*
 * TODO: use db.php
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

    $modData = fread($handle, _fSize($handle));
    if ($modData === false) {
        _close($handle);
        return false;
    }

    $db = json_decode($modData, true);
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