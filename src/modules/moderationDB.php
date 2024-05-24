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

require_once __DIR__ . "/db.php";

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
        // Our _read function also creates the database if it doesn't exist.
        if (!_read(PATH, $modHandle, $modData, $ro)) {
            throw new \RuntimeException("Failed to load moderation database.");
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

    if (!\DB\save($modHandle, $modData)) {
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

    \DB\close($modHandle);

    $modData = null;
    $modHandle = null;
    $modDirty = false;
    $modReadOnly = false;
}

function queryReports(): array {
    $ud = &load();
    return $ud["reports"];
}

/**
 * Returns an array containing all banned emails.
 * @return array all banned emails
 */
function queryBannedEmails(): array {
    $ud = &load();
    return array_keys($ud["bannedEmails"]);
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

function _default(): array {
    return [
        "reports" => [],
        "bannedEmails" => [],
        "reportIdSeq" => 1,
        "revision" => REV_LAST
    ];
}

function _read(string $path, &$handle, ?array &$db = null, bool $readOnly = false): bool {
    return \DB\read($path, "ModerationDB\_upgrade", $handle, $db, "ModerationDB\_default", $readOnly);
}