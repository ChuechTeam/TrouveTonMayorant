<?php ob_start();
session_start();
$pageBit = 1 << 9;
// Page bit: 1 << 9
// 1 --> connexion
// 0 --> inscription
$erreur = null;
$nerreur = !empty($_COOKIE["erreur"]) ? intval($_COOKIE["erreur"]) : null;
if ($nerreur !== null) {
    $erreur = \User\errToString($nerreur & ~$pageBit);
}
setcookie("erreur", "", -1);
?>

<h1 style="text-align: center;">Bonyour, bienvenue sur TTM !</h1>


<div class="login-form-container">
    <div class="login-form">
        <form action="testCo.php" method="post" id="Co">
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
                    <td colspan="2" style="text-align: center;"><button id="sub" type="submit">Se connecter</button>
                        <br>

                    </td>
                </tr>
            </table>
            <p style="font-size: 11px">Pas encore de compte ? <input type="button" value="S'inscrire"
                    onclick="HideShow('insc')"></p>
        </form>
        <form action="testInsc.php" method="post" id="Ins">
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
                    <td>Age</td>
                    <td><input type="number" name="age" id="" required min="18" max="122"></td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: center;"><button type="submit" id="sub">S'inscrire</button>
                        <br>

                    </td>
                </tr>
            </table>
            <p style="font-size: 11px">Déjà un compte ? <input type="button" value="Connexion" onclick="HideShow('conn')">
            </p>

        </form>
        <p id="error">
            <?= $erreur ?>
        </p>
    </div>
</div>

<script>
    function HideShow(page, clear = true) {
        if (page === "insc") {
            var x = document.getElementById("Co");
            x.style.display = "none";
            var y = document.getElementById("Ins");
            y.style.display = "block";
            var p = document.getElementById('error');
            if (clear && p && p.innerHTML.trim() !== '') {
                p.innerHTML = '';
            }
        }
        else {
            var x = document.getElementById("Ins");
            x.style.display = "none";
            var y = document.getElementById("Co");
            y.style.display = "block";
            var p = document.getElementById('error');
            if (clear && p && p.innerHTML.trim() !== '') {
                p.innerHTML = '';
            }
        }

    }
    <?php if ($nerreur !== null): ?>
        
        HideShow(<?= ($nerreur & $pageBit) === 0 ? 1 : 0 ?> ? 'insc' : 'conn', false);
    <?php endif ?>
</script>




<?php $tmplContent = ob_get_clean();
include "templates/base.php"; ?>