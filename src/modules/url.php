<?php

/**
 * To use :
 * $myUrl = "$root/auth.php"
 *
 * Can be included using require instead of require_once
 */
$root = (!empty($_SERVER["HTTPS"]) ? "https://" : "http://") . $_SERVER["HTTP_HOST"];