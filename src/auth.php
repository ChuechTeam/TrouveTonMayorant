<?php
require_once "templates/functions.php";
require_once "modules/userSession.php";

// Get the ?register URL parameter to show the right page
$register = isset($_GET["register"]);
$page = $register ? "register" : "signIn";

// -1 : form not sent
// >0 : Registration/login failed (see User error codes)
$errCode = -1;

// If the user has sent the form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($page === "signIn") {
        // Log in & check fields
        if (!empty(($_POST['mail'])) && !empty(($_POST['password']))) {
            $user_m = $_POST['mail'];
            $user_p = $_POST['password'];

            $u = UserDB\findByEmailPassword($user_m, $user_p);
            if ($u !== null) {
                \UserSession\signIn($u["id"]);
                header("Location: /index.php");
                exit();
            } else {
                $errCode = User\ERR_INVALID_CREDENTIALS;
            }
        } else {
            $errCode = User\ERR_FIELD_MISSING;
        }
    } else {
        // Register & check fields
        if (!empty(($_POST['mail'])) && !empty(($_POST['password'])) && !empty(($_POST['name']))
            && !empty(($_POST['fname'])) && !empty(($_POST['bdate'])) && !empty(($_POST['gender']))) {
            // Gather all important infos
            $user_m = $_POST['mail'];

            $user_p = $_POST['password'];
            $user_n = $_POST['name'];
            $user_fn = $_POST['fname'];
            $user_bd = $_POST['bdate'];
            $user_gender = $_POST['gender'];

            $id = 0;
            $errCode = User\register($user_fn, $user_n, $user_m, $user_p, $user_bd, $user_gender, $id);
            if ($errCode === 0) {
                // Start a user session with our newly created user, and redirect them to the member homepage
                \UserSession\signIn($id);
                header("Location: /index.php");
                exit();
            }
        } else {
            $errCode = User\ERR_FIELD_MISSING;
        }
    }
}

$error = $errCode !== -1 ? User\errToString($errCode) : null;

Templates\base($page === "signIn" ? "Connexion" : "Inscription");
Templates\addStylesheet("/assets/style/auth-page.css");
?>

<h1 class="title">Bonyour, bienvenue sur TTM !</h1>

<div class="login-form-container">
    <div class="login-form">
        <form action="auth.php" method="post" id="signInForm">
            <h3>Se connecter</h3>
            <div class="-grid-container">
                <label class="-grid-item" for="signMail">Email</label>
                <input class="-grid-item" type="email" name="mail" id="signMail" required>
                <label class="-grid-item" for="signPass">Mot de Passe</label>
                <input class="-grid-item" type="password" name="password" id="signPass" required>
            </div>
            <button class="sub" type="submit">Se connecter</button>
            <p class="form-switcher">
                Pas encore de compte ?
                <input type="button" value="S'inscrire" onclick="switchForm('register')">
            </p>
        </form>

        <form action="auth.php?register" method="post" id="registerForm">
            <h3>S'inscrire</h3>
            <div class="-grid-container">
                <label class="-grid-item" for="regMail">Email</label>
                <input type="email" name="mail" id="regMail" class="-grid-item" required>
                <label class="-grid-item" for="regPass">Mot de Passe</label>
                <input type="password" name="password" id="regPass" class="-grid-item" required>
                <label class="-grid-item" for="regLName">Nom</label>
                <input type="text" name="name" id="regLName" class="-grid-item" required>
                <label class="-grid-item" for="regFName">Prénom</label>
                <input type="text" name="fname" id="regFName" class="-grid-item" required>
                <label class="-grid-item" for="regBDate">Date de naissance</label>
                <input type="date" name="bdate" id="regBDate" class="-grid-item" required>
                <label class="-grid-item" for="regGender">Genre</label>
                <select class="-grid-item" id="regGender" name="gender" required>
                    <option value="m">Homme</option>
                    <option value="f">Femme</option>
                    <option value="nb">Non-binaire</option>
                </select>
            </div>
            <button type="submit" class="sub">S'inscrire</button>

            <p class="form-switcher">Déjà un compte ? <input type="button" value="Connexion" onclick="switchForm('signIn')"></p>

        </form>
        <p id="error"><?= $error ?></p>
    </div>
</div>
<?php /* This script isn't in a separate file to avoid flickering, we want to make sure it runs synchronously */ ?>
<script>
    const signInForm = document.getElementById("signInForm");
    const registerForm = document.getElementById("registerForm");
    const errorEl = document.getElementById("error");

    function switchForm(page, clearErr = true) {
        if (page === "register") {
            signInForm.style.display = "none";
            registerForm.style.display = "block";
        } else if (page === "signIn") {
            registerForm.style.display = "none";
            signInForm.style.display = "block";
        } else {
            console.error("Unknown page: " + page);
        }

        if (clearErr) {
            errorEl.innerHTML = '';
        }
    }

    switchForm('<?= $page ?>', false);
</script>