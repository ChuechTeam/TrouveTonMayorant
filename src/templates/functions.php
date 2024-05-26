<?php

/**
 * templates/functions.php
 * -----------
 *
 * A simple templating system for laying out pages coherently.
 * The role of a template is, essentially, to "wrap" the content of a page with
 * common HTML stuff.
 * Each template is given a "content" variable to insert the page's content, and can
 * access parameters that allow further customization.
 * Template data is accessible inside the $tmplArgs variable.
 */
namespace Templates;

require_once __DIR__ . "/../modules/userSession.php";
require_once __DIR__ . "/../modules/user.php";

$params = [];

/**
 * Uses the base template {@see base.php} to render the page.
 *
 * @param string|null $title the title of the page, inside the <title> tag
 * @return void
 */
function base(?string $title = null) {
    // Start recording the printed content now
    ob_start();

    if (isset($title)) {
        setParam("title", $title);
    }

    // Once the script has ended, use the base template to render the page
    register_shutdown_function(function() {
        $tmplArgs = _prepareArgs();
        require __DIR__ . "/base.php";
    });
}

/**
 * Uses the member template {@see member.php}, which derives from the base template, to render the page.
 * Requires a logged-in user account.
 *
 * @param string|null $title the title of the page, inside the <title> tag
 * @return void
 */
function member(?string $title = null) {
    ob_start();

    $user = \UserSession\loggedUser();
    if ($user == null) {
        die("The member template requires a logged user!");
    }

    // Fill some user-related data to use later inside the template
    setParam("user", $user);
    setParam("userLevel", \User\level($user["id"]));

    // Include MathJax for math rendering
    appendParam("head", <<<HTML
<script>
    window.mathJaxReady = new Promise((resolve) => {
        window.readyResolve = resolve;
    });
    MathJax = {
        tex: {
            inlineMath: [['$', '$']],
            processEscapes: true
        },
        loader: {load: ['ui/safe']},
        startup: { 
            elements: ['.has-math'],
            ready: function() {
                MathJax.startup.defaultReady();
                MathJax.startup.promise.then(window.readyResolve);
            }
        }
    };
</script>
<script async src="/scripts/math.js" type="module"></script>
<script id="MathJax-script" async src="/assets/mathjax/es5/tex-mml-chtml.js"></script>
HTML);

    register_shutdown_function(function($title) {
        $tmplArgs = _prepareArgs();
        // This one is a bit tricky to understand:
        // We run the 'base' function, which will record all the member template output
        // into a string, then later render it inside the base template.
        base($title);
        require __DIR__ . "/member.php";
    }, $title);
}

/**
 * Adds a stylesheet reference to the <head> of the page, for the base template (and derived).
 * The 'head' parameter is appended with: <link rel="stylesheet" href="$href">
 *
 * @param string $href the href of the stylesheet
 * @return void
 */
function addStylesheet(string $href) {
    appendParam("head", "<link rel=\"stylesheet\" href=\"$href\">");
}

/**
 * Sets a template parameter to the given value.
 *
 * @param string $name the name of the parameter
 * @param mixed $val the value
 * @return void
 */
function setParam(string $name, $val) {
    global $params;
    $params[$name] = $val;
}

/**
 * Appends a value to the parameter. If the parameter is not set, initializes it with the value instead.
 *
 * @param string $name the name of the parameter
 * @param string $val the value to append
 * @return void
 */
function appendParam(string $name, string $val) {
    global $params;
    $params[$name] = ($params[$name] ?? "") . $val;
}

/**
 * Prepares all data to be sent to a template file for rendering.
 * Returns an associative array with all the parameters, and the content of the page.
 * @return array
 */
function _prepareArgs(): array {
    global $params;
    $copy = $params;
    $copy["content"] = ob_get_clean();
    return $copy;
}