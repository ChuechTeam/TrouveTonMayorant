<?php
require "modules/url.php";
require_once "modules/userSession.php";

\UserSession\signOut();

header("Location: $root/index.php");