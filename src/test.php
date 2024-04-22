<?php require "modules/user.php";
userLoad();
userPut([
    "id" => 1,
    "email" => "salut@coucou.fr",
    "pass" => "1234",
    "firstName" => "moi",
    "lastName" => "pasmoi"
]);
userSave();