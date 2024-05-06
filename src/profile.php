<?php ob_start(); 
require "modules/url.php";
require "modules/user.php";
require "modules/userSession.php";
$u = \UserSession\loggedUser();
$errStr = null;
if (isset($_COOKIE["erreur"])) {
    $errStr = \User\errToString($_COOKIE["erreur"]);
}
setcookie("erreur", "", -1);
?>

<h1>Profil</h1>
<p><?php print("Ici le profil!!") ?></p>

<?php if($u !== null) :?>
    <p>eeeh tu peux modifier, monsieur ou madame <?= $u["firstName"] ?> <?= $u["lastName"] ?></p>
    <a href="<?= "$root/index.php" ?>"><button>Accueil</button></a>
    <a href="<?= "$root/redirect.php" ?>"><button>Déconnexion</button></a>
    </br></br>
    <form action="/testProfile.php" method="post" id="">
            <table border="1" cellpading="20" cellspacing="0">
                <tr>
                    <td>Email</td>
                    <td><input type="email" value="<?= htmlspecialchars($u['email']) ?>" name="mail" id="" required></td>
                </tr>
                <tr>
                    <td>Mot de Passe</td>
                    <td><input type="password" name="password" id=""></td>
                </tr>
                <tr>
                    <td>Nom</td>
                    <td><input type="text" value="<?= htmlspecialchars($u['lastName']) ?>" name="name" id="" required></td>
                </tr>
                <tr>
                    <td>Prénom</td>
                    <td><input type="text" value="<?= htmlspecialchars($u['firstName']) ?>" name="fname" id="" required></td>
                </tr>
                <tr>
                    <td>Age</td>
                    <td><input type="number" value="<?= htmlspecialchars($u['age']) ?>" name="age" id="" required min="18" max="122"></td>
                </tr>
                <tr>
                    <td>mettre d'autres trucs</td>
                    <td>là</td>
                </tr>

                <tr>
                    <td colspan="2" style="text-align: center;"><button type="submit" id="sub">Enregistrer</button>
                        <br>
                    </td>
                </tr>

            </table>
    </form>
    <?php if($errStr !== null): ?>
        <p id="err"><?= htmlspecialchars($errStr) ?></p>
    <?php endif; ?>

<?php else: ?>
    <p>eeehh tu n'es pas loggé</p><br>
    <a href="<?= "$root/connexion.php" ?>"><button>Se connecter/S'inscrire</button></a>
<?php endif ?>

<style>
    #err {
        color: red;
    }
</style>

<?php $tmplContent = ob_get_clean(); include "templates/base.php"; ?>

