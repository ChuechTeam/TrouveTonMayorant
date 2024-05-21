<?php

namespace ViewDB;

require_once __DIR__ . "/db.php";

const VIEW_DIR = __DIR__ . "/../../views";
const DATE_FORMAT = \DateTimeInterface::ATOM; // date format for visits

const REV_FIRST = 1;
const REV_LAST = REV_FIRST;

/*
 * Array structure of a view stats file:
 * "userId" => int
 * "viewCount" => int
 * "views" => associative array of [
 *   who => [
 *      "who" => int (user id)
 *      "date" => string (last visit)
 *      "count" => int (how many times)
 *   ]
 * ]
 * "revision" => int
 */

function _path(int $uid): string {
    return VIEW_DIR . "/$uid.json";
}

/**
 * Reads view stats for a given user from the database.
 *
 * @param int $uid the user id
 * @return array the view stats
 */
function read(int $uid): array {
    _read($uid, $handle, $view);
    \DB\close($handle);
    return $view;
}

/**
 * Registers a profile view by a user to another user. Adding new views can be restricted
 * using a minimum interval between views: if the last view happened too early, it won't be registered.
 *
 * @param int $uid the user whose profile is being visited
 * @param int $who the person visiting the profile
 * @param \DateInterval|null $minInterval the minimum interval between views, null has no restriction
 * @param array|null $view the view stats, if provided, it will be updated
 * @return void
 */
function registerView(int $uid, int $who, \DateInterval $minInterval = null, array &$view = null) {
    _read($uid, $handle, $view);

    if (!isset($view["views"][$who])) {
        // Then this is out first view, no need to apply intervals
        $view["views"][$who] = [
            "who" => $who,
            "date" => (new \DateTime("now"))->format(DATE_FORMAT),
            "count" => 1
        ];
        $view["viewCount"]++;
    }
    else {
        // This user has already visited this profile once, check if we can add a new view
        $v = &$view["views"][$who];
        if ($minInterval !== null) {
            // Find the last time at which the visitor saw the profile
            $viewTime = \DateTimeImmutable::createFromFormat(DATE_FORMAT, $v["date"]);
            // Calculate the next minimum date at which a view can be registered
            $nextViewTime = $viewTime->add($minInterval);
            $now = new \DateTime();
            $dist = $nextViewTime->diff($now); // = $now - $nextViewTime

            // Exit early if $now < $nextViewTime :
            // $now - $nextViewTime < 0
            // <==> $now < $nextViewTime
            // <==> $dist->inverse = 1
            // If the difference is negative, then
            if ($dist->invert === 1) {
                \DB\close($handle);
                return;
            }
        }

        // We can now register the view
        $view["viewCount"]++;
        $v["date"] = (new \DateTime("now"))->format(DATE_FORMAT);
        $v["count"]++;
    }

    if (!\DB\save($handle, $view)) {
        throw new \RuntimeException("Failed to write view data for user $uid.");
    }
}

/**
 * Upgrades all view stats files in the default directory to the latest revision.
 * @return void
 */
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

    // add some migration steps if necessary

    $conv["revision"] = REV_LAST;

    return true;
}