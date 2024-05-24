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
$g = $_GET["gender"] ?? []; // If unset --> [], so no users will be filtered by gender
$f = $_GET["smoker"] ?? null;
$rel = $_GET["rel_search"] ?? []; // If unset --> [], so no users will be filtered by the type of relationship they search for
$a_min = intval($_GET["a_min"]);
$a_max = intval($_GET["a_max"]);
$dep = $_GET["dep"] ?? null;

function any_in_array(array $values, array $search) {
    foreach($values as $val){
        if (in_array($val,$search)){
            return 1;
        }
    }
    return 0;
}

foreach(UserDB\query() as $u){
    $a = (new DateTime($u["bdate"]))->diff(new DateTime())->y;
    if ((empty($g) || in_array($u["gender"],$g)) && 
        ($f == null || $u["user_smoke"]==$f) &&
        ($a <= $a_max && $a >=$a_min) && // age check
        (User\blockStatus(userSession\loggedUserId(), $u["id"]) != 1 ) &&
        ($dep === null || $u["dep"] == $dep) &&
        (empty($rel) || any_in_array($rel, $u["rel_search"]))
        ) {
        if ($first) {
            echo '<div class="search-results">';
            $first = false;
        }   

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



?>