<?php

require_once "modules/userDB.php";
require_once "./member-area/_profileCard.php";
require "./modules/url.php";

$q = $_REQUEST["q"];
$first = true;
$q = strtolower($q);
$len=strlen($q);

// Charger la base de données en lecture seule pour éviter de verrouiller le fichier pour rien
UserDB\load(true);
foreach(UserDB\query() as $u){
    if (stristr($q, substr($u["firstName"], 0, $len))) {
        if ($first) {
            echo '<div class="search-results">';
            $first = false;
        }

        profileCard($u);
    }
}


if ($first) {
    echo "<div class=\"search-results -empty\">Aucun résultat</div>";
} else {
    echo "</div>";
}


?>