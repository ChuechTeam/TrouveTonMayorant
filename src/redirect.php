<?php
require "modules/url.php";

session_start();
$_SESSION['loggedIn']=0;

header("Location: $root/index.php");