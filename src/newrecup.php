<?php

require_once "modules/userDB.php";
require_once "modules/user.php";
require_once "modules/userSession.php";
require_once "./member-area/_profileCard.php";
require "./modules/url.php";

// Charger la base de données en lecture seule pour éviter de verrouiller le fichier pour rien
UserDB\load(true);
$first = true;
$g = $_GET["genre"] ?? []; // Si pas set --> []
$f = $_GET["fumeur"] ?? null;
$a_min = intval($_GET["a_min"]);
$a_max = intval($_GET["a_max"]);

foreach(UserDB\query() as $u){
    $a = (new DateTime($u["bdate"]))->diff(new DateTime())->y;
    if ((empty($g) || in_array($u["gender"],$g)) && 
        ($f == null || $u["user_smoke"]==$f) &&
        ($a <= $a_max && $a >=$a_min) &&// age autour de celui du user
        (User\blockStatus(userSession\loggedUserId(), $u["id"]) != 1 )
        
        ) {
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