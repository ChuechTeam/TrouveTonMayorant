<?php

/*
 * Outils pour stocker des "bases de donnée" en JSON.
 */

namespace DB;

//function _upgrade(array &$conv): bool {
//    $rev = $conv["revision"] ?? null;
//    if ($rev === null) {
//        throw new \RuntimeException("Revision not found in conversation, file is probably corrupted!");
//    }
//    if ($rev === REV_LAST) {
//        return false;
//    }
//
//    while ($rev < REV_LAST) {
//        $rev++;
//        switch ($rev) {
//            case REV_DELETE_EVENTS:
//                $conv["deleteEvents"] = [];
//                break;
//        }
//    }
//
//    $conv["revision"] = REV_LAST;
//
//    return true;
//}

const CREATE_EXISTS = 1;
const CREATE_OK = 0;
const CREATE_ERROR = -1;

// 1 : existe
// 0 : créé
// -1 : erreur
function create(string $path, array $entry): int {
    $dir = dirname($path);
    if (!file_exists($dir)) {
        if (!mkdir($dir, 0755, true)) {
            return CREATE_ERROR;
        }
    }

    if (!file_exists($path)) {
        return file_put_contents($path, json_encode($entry), LOCK_EX) === false ? CREATE_ERROR : CREATE_OK;
    } else {
        return CREATE_EXISTS;
    }
}

function read(string    $path,
              ?callable $upgradeFunc, // (&array) => bool
                        &$handle,
              ?array    &$entry = null,
              ?callable $defaultFunc = null, // () => array
              bool      $readOnly = false): bool {
    $handle = @fopen($path, $readOnly ? "r" : ($defaultFunc === null ? "r+" : "c+"));
    if ($handle === false) {
        if ($defaultFunc === null || $readOnly) {
            return false;
        }
        else {
            // Réessayons en créant le dossier cette fois ci...
            $dn = dirname($path);
            if (!file_exists($dn)) {
                if (!mkdir($dn, 0755, true)) {
                    return false;
                }
            }

            $handle = fopen($path, "c+");
            if ($handle === false) { return false; }
        }
    }

    $lockOk = flock($handle, $readOnly ? LOCK_SH : LOCK_EX);
    if ($lockOk === false) {
        fclose($handle);
        return false;
    }

    $size = fSize($handle);

    // Si une valeur par défaut est donnée et que le fichier est vide, alors on crée une nouvelle entrée.
    if ($size === 0 && $defaultFunc !== null) {
        $entry = $defaultFunc();
        return save($handle, $entry);
    }

    $data = fread($handle, $size);
    if ($data === false) {
        close($handle);
        return false;
    }

    $entry = json_decode($data, true);
    if ($upgradeFunc !== null && $upgradeFunc($entry)) {
        if ($readOnly) {
            throw new \RuntimeException("Cannot upgrade database entry in read-only mode.");
        }

        return save($handle, $entry);
    }

    return true;
}

function save($handle, array $entry): bool {
    $json = json_encode($entry);
    $ok = true;
    $ok &= fseek($handle, 0) === 0;
    $ok &= ftruncate($handle, strlen($json)) !== false;
    $ok &= fwrite($handle, $json) !== false;

    return $ok;
}

function close($handle, ?array $conv = null): bool {
    $ok = true;
    if ($conv !== null) {
        $ok = save($handle, $conv);
    }

    $ok &= flock($handle, LOCK_UN);
    $ok &= fclose($handle);

    return $ok;
}

function listFiles(string $dir) {
    $dirs = @scandir($dir);
    if ($dirs === false) {
        return;
    }

    foreach ($dirs as $file) {
        $fp = rtrim($dir, "/\\") . "/$file";
        if (is_file($fp) && pathinfo($fp, PATHINFO_EXTENSION) == "json") {
            yield $fp;
        }
    }
}

function fSize($handle) {
    $stat = fstat($handle);
    if ($stat === false) {
        throw new \RuntimeException("Failed to gather the file size!");
    }
    return $stat['size'];
}