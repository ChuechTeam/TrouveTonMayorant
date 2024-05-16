<?php

require_once "modules/userDB.php";
require_once "./member-area/_profileCard.php";
require "./modules/url.php";

// Charger la base de données en lecture seule pour éviter de verrouiller le fichier pour rien
UserDB\load(true);
$first = true;
$g = $_GET["genre"] ?? []; // Si pas set --> []
$f = $_GET["fumeur"] ?? null;

    
foreach(UserDB\query() as $u){
    if ((empty($g) || in_array($u["gender"],$g)) && 
        ($f == null || $u["user_smoke"]==$f)) {
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