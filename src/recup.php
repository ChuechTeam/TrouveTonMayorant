<?php

require_once "modules/userDB.php";
$q = $_REQUEST["q"];
$res = "";
$q = strtolower($q);
$len=strlen($q);

$DB = \UserDB\load();
foreach($DB["users"] as $u => $v){
    if ($u !== "_dict") {
        if (stristr($q, substr($v["firstName"], 0, $len))) {
            if ($res === "") {
                $res = htmlspecialchars($v["firstName"]);
            }
            else {
                $res .= "<br>".htmlspecialchars($v['firstName']);
            }
        }
        
    }
}


echo $res === "" ? "no suggestion" : $res;


?>