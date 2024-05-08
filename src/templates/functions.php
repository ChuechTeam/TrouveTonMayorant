<?php

namespace Templates;

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

function paramStart(string $name) {
    ob_start(function($str) use ($name) {
        global $params;
        $params[$name] = $str;
    });
}

function paramEnd() {
    ob_end_clean();
}

function _prepareArgs(): array {
    global $params;

    return [
        "content" => ob_get_clean(),
        ...$params
    ];
}