<?php

/**
 * db.php
 * -----------
 * Contains functions for creating, reading, and writing to JSON files with (relatively) robust file locking.
 */
namespace DB;

// Skeleton of an upgrade function
//
// function _upgrade(array &$conv): bool {
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
//            case REV_X:
//                // do stuff!
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

// 1 : exists
// 0 : created
// -1 : error
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

/**
 * Reads a JSON database file at the given path, applying any upgrades and locking the file by default.
 *
 * The upgrade function, if specified and not null with `$upgradeFunc`, is called when the file is read.
 * It must apply any updates to the database, and return `true` if some updates were made; `false` otherwise.
 *
 * A default value function can optionally be specified using `$defaultFunc`, which must return the default
 * value for creating a new database, in form of an associative array.
 * When specified, this function is called when the file does not exist: it creates a new file,
 * and writes the returned default value in JSON format in it.
 * The function continues as usual with a filled `$handle` value.
 *
 * The parsed JSON database, if found, is stored in the `$entry` parameter.
 *
 * @param string $path the path to the JSON database file
 * @param ?callable(array): bool $upgradeFunc an upgrade function called when loading an existing database, must return
 *                                   true if the database has been updated, false otherwise
 * @param resource $handle set to the handle to the loaded file, if the load was successful
 * @param array|null $entry set to the parsed JSON database, if the load was successful
 * @param ?callable(): array $defaultFunc a function returning the default value for a new database, which enables
 *                                   the creation of a file with the returned value if the file doesn't exist
 * @param bool $readOnly whether the file should be opened in read-only mode, meaning that locking is made
 *                       using `LOCK_SH` instead of `LOCK_EX`, and that the file is opened in read-only mode
 * @return bool true if the file was successfully read OR created using `$defaultFunc`, false otherwise
 */
function read(string    $path,
              ?callable $upgradeFunc, // (&array) => bool
                        &$handle,
              ?array    &$entry = null,
              ?callable $defaultFunc = null, // () => array
              bool      $readOnly = false): bool {
    $handle = @fopen($path, $readOnly ? "r" : ($defaultFunc === null ? "r+" : "c+"));
    if ($handle === false) {
        if ($defaultFunc === null || $readOnly) {
            // We don't want to create files, give up.
            return false;
        }
        else {
            // That didn't work? Then it's likely that the file is just not there.
            // Let's create it.
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

    // Lock the file for reading or writing
    $lockOk = flock($handle, $readOnly ? LOCK_SH : LOCK_EX);
    if ($lockOk === false) {
        fclose($handle);
        return false;
    }

    $size = fSize($handle);

    // If the file is empty, and we have specified a default value function, treat the file as a new entry,
    // and initialize it.
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
    if ($entry === false) {
        return false;
    }

    if ($upgradeFunc !== null && $upgradeFunc($entry)) {
        if ($readOnly) {
            throw new \RuntimeException("Cannot upgrade database entry in read-only mode.");
        }

        return save($handle, $entry);
    }

    return true;
}

/**
 * Saves a JSON database to the file system.
 *
 * @param resource $handle the handle
 * @param array $entry the entire database data
 * @return bool true if the save was successful, false otherwise
 */
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