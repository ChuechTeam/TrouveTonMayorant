<?php
require_once "templates/functions.php";
require_once "modules/userSession.php";
require_once "modules/url.php";

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
                header("Location: $root/index.php");
                exit();
            } else {
                $errCode = User\ERR_INVALID_CREDENTIALS;
            }
        } else {
            $errCode = User\ERR_FIELD_MISSING;
        }
    } else {
        // Register & check fields
        if (!empty(($_POST['mail'])) && !empty(($_POST['password'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['bdate'])) && !empty(($_POST['gender']))) { //Si les champs ne sont pas vides
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
                // Start a user session with our newly created user
                \UserSession\signIn($id);
                header("Location: $root/index.php");
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
        <form action="auth.php" method="post" id="Co">
            <div class="-grid-container">
                <div class="-grid-item -header"><h3>Se connecter</h3></div>
                <div class="-grid-item">Email</div>
                <div class="-grid-item"><input type="email" name="mail" id="" required></div>
                <div class="-grid-item">Mot de Passe</div>
                <div class="-grid-item"><input type="password" name="password" id="" required></div>
                <div class="-grid-item -footer"><button class="sub" type="submit">Se connecter</button></div>
            </div>

            <p style="font-size: 11px">Pas encore de compte ? <input type="button" value="S'inscrire"
                                                                     onclick="HideShow('register')"></p>
        </form>

        <form action="auth.php?register" method="post" id="Ins">

            <div class="-grid-container2">
                <div class="-grid-item -header"><h3>S'inscrire</h3></div>
                <div class="-grid-item">Email</div>
                <div class="-grid-item"><input type="email" name="mail" id="" required></div>
                <div class="-grid-item">Mot de Passe</div>
                <div class="-grid-item"><input type="password" name="password" id="" required></div>
                <div class="-grid-item">Nom</div>
                <div class="-grid-item"><input type="text" name="name" id="" required></div>
                <div class="-grid-item">Prénom</div>
                <div class="-grid-item"><input type="text" name="fname" id="" required></div>
                <div class="-grid-item">Date de naissance</div>
                <div class="-grid-item"><input type="date" name="bdate" id="" required></div>
                <div class="-grid-item">Genre</div>
                <div class="-grid-item">
                    <select id="gender" name="gender" required>
                        <option value="m">Homme</option>
                        <option value="f">Femme</option>
                        <option value="nb">Non-binaire</option>
                    </select>
                </div>
                <div class="-grid-item -footer"><button type="submit" class="sub">S'inscrire</button></div>
            </div>
            
            <p style="font-size: 11px">Déjà un compte ? <input type="button" value="Connexion"
                                                               onclick="HideShow('signIn')">
            </p>

        </form>
        <p id="error">
            <?= $error ?>
        </p>
    </div>
</div>

<script>
    function HideShow(page, clear = true) {
        if (page === "register") {
            var x = document.getElementById("Co");
            x.style.display = "none";
            var y = document.getElementById("Ins");
            y.style.display = "block";
            var p = document.getElementById('error');
            if (clear && p && p.innerHTML.trim() !== '') {
                p.innerHTML = '';
            }
        } else if (page === "signIn") {
            var x = document.getElementById("Ins");
            x.style.display = "none";
            var y = document.getElementById("Co");
            y.style.display = "block";
            var p = document.getElementById('error');
            if (clear && p && p.innerHTML.trim() !== '') {
                p.innerHTML = '';
            }
        } else {
            console.error("Unknown page: " + page);
        }
    }

    HideShow('<?= $page ?>', false);
</script>