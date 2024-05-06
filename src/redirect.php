<?php
require "modules/url.php";
require "modules/userSession.php";

\UserSession\signOut();

header("Location: $root/index.php");