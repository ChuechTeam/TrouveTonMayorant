<?php

require_once "modules/userDB.php";
$q = $_REQUEST["q"];
$res = "";
$q = strtolower($q);
$len=strlen($q);

// Charger la base de données en lecture seule pour éviter de verrouiller le fichier pour rien
UserDB\load(true);
foreach(UserDB\query() as $u){
    if (stristr($q, substr($u["firstName"], 0, $len))) {
        if ($res === "") {
            $res = htmlspecialchars($u["firstName"]);
        }
        else {
            $res .= "<br>".htmlspecialchars($u['firstName']);
        }
    }
}


echo $res === "" ? "no suggestion" : $res;


?>