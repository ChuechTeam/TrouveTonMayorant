<?php
require_once "modules/userSession.php";

\UserSession\signOut();

header("Location: /index.php");