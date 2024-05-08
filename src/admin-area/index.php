<?php
require_once "../modules/userSession.php";
\UserSession\requireLevel(User\LEVEL_ADMIN);
echo "ehhh c'est vide :(";