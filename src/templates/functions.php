<?php

namespace Templates;

require_once __DIR__ . "/../modules/userSession.php";

$params = [];

function base(?string $title = null) {
    ob_start();

    if (isset($title)) {
        setParam("title", $title);
    }

    register_shutdown_function(function() {
        $tmplArgs = _prepareArgs();
        require __DIR__ . "/base.php";
    });
}

function member(?string $title = null) {
    ob_start();

    $user = \UserSession\loggedUser();
    if ($user == null) {
        die("The member template requires a logged user!");
    }

    setParam("user", $user);

    register_shutdown_function(function($title) {
        $tmplArgs = _prepareArgs();
        base($title);
        require __DIR__ . "/member.php";
    }, $title);
}

function setParam(string $name, $val) {
    global $params;
    $params[$name] = $val;
}

function appendParam(string $name, string $val) {
    global $params;
    $params[$name] = ($params[$name] ?? "") . $val;
}

function paramStart(string $name) {
    ob_start(function($str) use ($name) { setParam($name, $str); });
}

function paramStartAppend(string $name) {
    ob_start(function($str) use ($name) { appendParam($name, $str); });
}

function paramEnd() {
    ob_end_clean();
}

function _prepareArgs(): array {
    global $params;
    $copy = $params;
    $copy["content"] = ob_get_clean();
    return $copy;
}