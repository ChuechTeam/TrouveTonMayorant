<?php
require_once "templates/functions.php";
require_once "modules/userSession.php";
require_once "modules/url.php";

$register = isset($_GET["register"]);
$page = $register ? "register" : "signIn";

// -1 : formulaire non envoyé
// >0 : échec de l'inscription/connexion (code d'erreur User)
$errCode = -1;

// si on a envoyé le formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($page === "signIn") {
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
        // Inscription
        if (!empty(($_POST['mail'])) && !empty(($_POST['password'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['bdate']))) { //Si les champs ne sont pas vides
            //On recupere les infos importantes
            $user_m = $_POST['mail'];

            $user_p = $_POST['password'];
            $user_n = $_POST['name'];
            $user_fn = $_POST['fname'];
            $user_bd = $_POST['bdate'];

            $id = 0;
            $errCode = User\register($user_fn, $user_n, $user_m, $user_p, $user_bd, $id);
            if ($errCode === 0) {
                \UserSession\signIn($id);
                header("Location: $root/index.php");
                exit();
            }
        } else {
            $errCode = User\ERR_FIELD_MISSING;
        }
    }
}

$erreur = $errCode !== -1 ? User\errToString($errCode) : null;

Templates\base($page === "signIn" ? "Connexion" : "Inscription");
?>

<h1 class="title">Bonyour, bienvenue sur TTM !</h1>

<div class="login-form-container">
    <div class="login-form">
        <form action="auth.php" method="post" id="Co">
            <table border="1" cellpading="20" cellspacing="0">
                <tr>
                    <td colspan="2" style="text-align: center;">Se connecter</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><input type="email" name="mail" id="" required></td>
                </tr>
                <tr>
                    <td>Mot de Passe</td>
                    <td><input type="password" name="password" id="" required></td>
                </tr>
                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button class="sub" type="submit">Se connecter</button>
                        <br>

                    </td>
                </tr>
            </table>
            <p style="font-size: 11px">Pas encore de compte ? <input type="button" value="S'inscrire"
                                                                     onclick="HideShow('register')"></p>
        </form>
        <form action="auth.php?register" method="post" id="Ins">
            <table border="1" cellpading="20" cellspacing="0">
                <tr>
                    <td colspan="2" style="text-align: center;">S'inscrire</td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td><input type="email" name="mail" id="" required></td>
                </tr>
                <tr>
                    <td>Mot de Passe</td>
                    <td><input type="password" name="password" id="" required></td>
                </tr>
                <tr>
                    <td>Nom</td>
                    <td><input type="text" name="name" id="" required></td>
                </tr>
                <tr>
                    <td>Prénom</td>
                    <td><input type="text" name="fname" id="" required></td>
                </tr>
                <tr>
                    <td>Date de naissance</td>
                    <td><input type="date" name="bdate" id="" required></td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: center;">
                        <button type="submit" class="sub">S'inscrire</button>
                        <br>

                    </td>
                </tr>
            </table>
            <p style="font-size: 11px">Déjà un compte ? <input type="button" value="Connexion"
                                                               onclick="HideShow('signIn')">
            </p>

        </form>
        <p id="error">
            <?= $erreur ?>
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