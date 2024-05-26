<?php

$api = true;
require "../_common.php";
require_once "../_profileCard.php";

/*
 * GET /member-area/api/search.php
 * Searches for users that qualify given filters and preferences,
 * and returns their profile cards in HTML form.
 *
 * Input (URL):
 * ?gender[]: if non-empty, filters the users with a gender present in the array
 * ?smoker: if set, filters the users by their smoking preference
 * ?rel_search[]: if non-empty, filters the users with at least one relationship type present in the array
 * ?a_min: filters users with an age lower than this value (18 by default)
 * ?a_max: filters users with an age higher than this value (200 by default)
 * ?dep: if set, filters users by their department code
 * ?city: if set, filters users by their city
 * ?situation[]: if non-empty, filters the users with a relationship situation present in the array
 *
 * Returns:
 * 200 OK: the HTML of the profile cards, contained in a <div class="search-results"> element;
 *         profile cards are wrapped in <div class="profile-card-container"> elements for better layout.
 *         If there are no results found, the response will be <div class="search-results -empty">Aucun résultat</div>.
 */

// Load the database in read-only mode to avoid performance issues.
UserDB\load(true);
$first = true; // To know if we have to send an "empty results" div or not
$gender = $_GET["gender"] ?? []; // If unset --> [], so no users will be filtered by gender
$smoke = $_GET["smoker"] ?? []; // Smoker preference
$rel = $_GET["rel_search"] ?? []; // If unset --> [], so no users will be filtered by the type of relationship they search for
$a_min = intval($_GET["a_min"] ?? 18); // Minimum age (18 if not specified)
$a_max = intval($_GET["a_max"] ?? 200); // Maximum age (200 if not specified)
$dep = !empty($_GET["dep"]) ? $_GET["dep"] : null; // Department might be specified but empty, so check it
$city = !empty($_GET["city"]) ? $_GET["city"] : null; // City might be specified but empty, so check it
$situation = $_GET["situation"] ?? []; // If unset --> [], so no users will be filtered by relationship situation

// Returns true when at least one element of $search is in $values
function any_in_array(array $values, array $search) {
    foreach ($values as $val) {
        if (in_array($val, $search)) {
            return 1;
        }
    }
    return 0;
}

// Loop through all registered users
foreach (UserDB\query() as $u) {
    // Calculate the age of the user
    $age = User\age($u);

    if (
        (empty($gender) || in_array($u["gender"], $gender)) && // Apply gender preferences
        (empty($smoke) || in_array($u["user_smoke"], $smoke)) && // Apply smoking preferences
        ($age >= $a_min && $age <= $a_max) && // Apply age preferences
        ($dep === null || $u["dep"] == $dep) && // Apply department preferences
        ($city === null || $u["city"] == $city) && // Apply city preferences
        (empty($rel) || any_in_array($rel, $u["rel_search"])) && // Apply relationship preferences
        (empty($situation) || in_array($u["situation"], $situation)) && // Apply relationship status preferences
        (User\blockStatus(userSession\loggedUserId(), $u["id"]) !== User\BS_THEM) && // Don't show users that blocked me
        ($u["id"] !== $user["id"]) // Don't show myself
    ) {
        // If it's our first result, start the search-results div
        if ($first) {
            echo '<div class="search-results">';
            $first = false;
        }

        // Print the profile card in a container to get correct layout for mobile.
        echo "<div class='profile-card-container'>";
        povProfileCard($u);
        echo "</div>";
    }
}

if ($first) {
    // If we don't have any results, then we didn't print out anything, so return a fully-fledged empty div
    echo "<div class=\"search-results -empty\">Aucun résultat</div>";
} else {
    // Close the search-results div
    echo "</div>";
}
