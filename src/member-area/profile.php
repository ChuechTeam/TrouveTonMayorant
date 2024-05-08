<?php
require "_common.php";

UserSession\requireLevel(User\LEVEL_MEMBER);
$u = UserSession\loggedUser();

// -1 : formulaire non envoyé
// 0  : profil mis à jour avec succès
// >0 : échec de la màj (code d'erreur User)
$submitCode = -1;

// Si l'utilisateur a envoyé le formulaire en cliquant sur "Enregistrer"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!empty(($_POST['mail'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['age']))) { //Si les champs ne sont pas vides
        $ok = User\updateProfile($u["id"], array(
            "firstName" => $_POST['fname'],
            "lastName" => $_POST['name'],
            "email" => $_POST['mail'],
            "age" => $_POST['age']
        ), $u);

        if (!empty($_POST["password"]) && $ok === 0) {
            $ok = User\updatePassword($u["id"], $_POST["password"], $u);
        }

        $submitCode = $ok;
    } else {
        $submitCode = User\ERR_FIELD_MISSING;
    }
}

$errStr = null;
if ($submitCode > 0) {
    $errStr = User\errToString($submitCode);
}

Templates\member("Votre profil");
?>

<h1>Profil</h1>
<p><?php print("Ici le profil!!") ?></p>

<p>eeeh tu peux modifier, monsieur ou madame <?= $u["firstName"] ?> <?= $u["lastName"] ?></p>
<a href="<?= "$root/member-area" ?>">
    <button>Accueil</button>
</a>
<a href="<?= "$root/redirect.php" ?>">
    <button>Déconnexion</button>
</a>
<form action="profile.php" method="post" id="">
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
            <td><input type="number" value="<?= htmlspecialchars($u['age']) ?>" name="age" id="" required min="18"
                       max="122"></td>
        </tr>
        <tr>
            <td>mettre d'autres trucs</td>
            <td>là</td>
        </tr>

        <tr>
            <td colspan="2" style="text-align: center;">
                <button type="submit" id="sub">Enregistrer</button>
                <br>
            </td>
        </tr>

    </table>
</form>
<?php if ($errStr !== null): ?>
    <p id="err"><?= htmlspecialchars($errStr) ?></p>
<?php elseif ($submitCode == 0): ?>
    <p id="all-good">Données enregistrées avec succès !</p>
<?php endif ?>

<style>
    #err {
        color: red;
    }
    #all-good {
        color: green;
    }
</style>
