<?php

require_once "modules/userDB.php";
require_once "modules/user.php";
require_once "modules/userSession.php";
require_once "./member-area/_profileCard.php";
require "./modules/url.php";

UserSession\start();

// Load the database in read-only mode to avoid performance issues.
UserDB\load(true);
$first = true;
$gender = $_GET["gender"] ?? []; // If unset --> [], so no users will be filtered by gender
$smoke = $_GET["smoker"] ?? null; // Smoker preference
$rel = $_GET["rel_search"] ?? []; // If unset --> [], so no users will be filtered by the type of relationship they search for
$a_min = intval($_GET["a_min"]); // Minimum age
$a_max = intval($_GET["a_max"]); // Maximum age
$dep = !empty($_GET["dep"]) ? $_GET["dep"] : null; // Department might be specified but empty, so check it
$city = !empty($_GET["city"]) ? $_GET["city"] : null; // City might be specified but empty, so check it

function any_in_array(array $values, array $search) {
    foreach ($values as $val) {
        if (in_array($val, $search)) {
            return 1;
        }
    }
    return 0;
}

foreach (UserDB\query() as $u) {
    // Calculate the age of the user
    $age = User\age($u);

    if ((empty($gender) || in_array($u["gender"], $gender)) && // Apply gender preferences
        ($smoke === null || $u["user_smoke"] === $smoke) && // Apply smoking preferences
        ($age >= $a_min && $age <= $a_max) && // Apply age preferences
        ($dep === null || $u["dep"] == $dep) && // Apply department preferences
        ($city === null || $u["city"] == $city) && // Apply city preferences
        (empty($rel) || any_in_array($rel, $u["rel_search"]) && // Apply relationship preferences
        (User\blockStatus(userSession\loggedUserId(), $u["id"]) !== User\BS_THEM)) // Don't show users that blocked me
    ) {
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
    echo "<div class=\"search-results -empty\">Aucun résultat</div>";
} else {
    echo "</div>";
}
