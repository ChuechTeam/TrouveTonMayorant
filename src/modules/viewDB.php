<?php

namespace ViewDB;

require_once __DIR__ . "/db.php";

const VIEW_DIR = __DIR__ . "/../../views";
const DATE_FORMAT = \DateTimeInterface::ATOM;

const REV_FIRST = 1;
const REV_LAST = REV_FIRST;

/*
 * Structure :
 * "userId" => int
 * "viewCount" => int
 * "views" => tableau assoc de [
 *   who => [
 *      "who" => int
 *      "date" => string
 *      "count" => int
 *   ]
 * ]
 * "revision" => int
 */

function _path(int $uid): string {
    return VIEW_DIR . "/$uid.json";
}

// Crée le fichier s'il n'existe pas
function read(int $uid): array {
    _read($uid, $handle, $view);
    \DB\close($handle);
    return $view;
}

// $minInterval est l'intervalle de temps minimal avant de pouvoir ajouter une autre vue
function registerView(int $uid, int $who, \DateInterval $minInterval = null, array &$view = null) {
    _read($uid, $handle, $view);

    if (!isset($view["views"][$who])) {
        // Première vue
        $view["views"][$who] = [
            "who" => $who,
            "date" => (new \DateTime("now"))->format(DATE_FORMAT),
            "count" => 1
        ];
        $view["viewCount"]++;
    }
    else {
        // Vue supplémentaire
        $v = &$view["views"][$who];

        // Vérifier si on peut ajouter une vue
        if ($minInterval !== null) {
            $viewTime = \DateTimeImmutable::createFromFormat(DATE_FORMAT, $v["date"]);
            $nextViewTime = $viewTime->add($minInterval);
            $now = new \DateTime();
            $dist = $nextViewTime->diff($now); //  = $now - $nextViewTime
            // jsp pourquoi c'est inversé mais je trouve ça idiot

            // $now - $nextViewTime < 0
            // ==> $now < $nextViewTime
            // Alors c'est trop tôt...
            if ($dist->invert) {
                \DB\close($handle);
                return;
            }
        }

        // Confirmer la vue
        $view["viewCount"]++;
        $v["date"] = (new \DateTime("now"))->format(DATE_FORMAT);
        $v["count"]++;
    }

    if (!\DB\save($handle, $view)) {
        throw new \RuntimeException("Failed to write view data for user $uid.");
    }
}

function upgradeAll() {
    foreach (\DB\listFiles(VIEW_DIR) as $f) {
        \DB\read($f, 'ViewDB\_upgrade', $handle, $view);
        \DB\close($handle);
    }
}

function _read(int $uid, &$handle, array &$view = null) {
    $ok = \DB\read(_path($uid), 'ViewDB\_upgrade', $handle, $view, function() use ($uid) {
        return [
            "userId" => $uid,
            "viewCount" => 0,
            "views" => [],
            "revision" => REV_LAST
        ];
    });

    if (!$ok) {
        throw new \RuntimeException("Failed to read view data for user $uid.");
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

    // étapes de migration à mettre ici

    $conv["revision"] = REV_LAST;

    return true;
}