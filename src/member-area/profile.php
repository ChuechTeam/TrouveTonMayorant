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
    if (!empty(($_POST['mail'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['bdate']))) { //Si les champs ne sont pas vides

        $ok = User\updateProfile($u["id"], array(
            "firstName" => $_POST['fname'],
            "lastName" => $_POST['name'],
            "email" => $_POST['mail'],
            "bdate" => $_POST['bdate']
            ), array(
                "gender" => (isset($_POST['gender'])) ? $_POST['gender'] : "",
                "orientation" => (isset($_POST['orientation'])) ? $_POST['orientation'] : "",
                "job" => (isset($_POST['job'])) ? $_POST['job'] : "",
                "situation" => (isset($_POST['situation'])) ? $_POST['situation'] : "",
                "desc" => (isset($_POST['desc'])) ? $_POST['desc'] : "",
                "bio" => (isset($_POST['bio'])) ? $_POST['bio'] : "",
                "user_smoke" => (isset($_POST['user_smoke'])) ? $_POST['user_smoke'] : "",
                "search_smoke" => (isset($_POST['search_smoke'])) ? $_POST['search_smoke'] : "",
            ),$u);

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

<h1 class="title">Profil</h1>

<div class="profile-form-container">
    <div class="profile-form">
        <form action="profile.php" method="post" id="">

            <h2 class="-title">Compte<hr></h2>
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
                    <td>Date d'inscription</td>
                    <td>aaaa</td>
                </tr>
            </table>
            <button class="-delete">Supprimer le compte</button>
            <br>
            <h2 class="-title">Informations personnelles<hr></h2>
            <table border="1" cellpading="20" cellspacing="0">
                <tr>
                    <td>Nom</td>
                    <td><input type="text" value="<?= htmlspecialchars($u['lastName']) ?>" name="name" id="" required></td>
                </tr>
                <tr>
                    <td>Prénom</td>
                    <td><input type="text" value="<?= htmlspecialchars($u['firstName']) ?>" name="fname" id="" required></td>
                </tr>
                <tr>
                    <td>Date de naissance</td>
                    <td><input type="date" value="<?= htmlspecialchars($u['bdate']) ?>" name="bdate" id="" required></td>
                </tr>
                <tr>
                    <td>Genre</td>
                    <td><select id="gender" name="gender">
                        <option disabled selected value></option>
                        <option value="m" <?= ($u['gender']=="m") ? "selected" : "" ?> >Homme</option>
                        <option value="f" <?= ($u['gender']=="f") ? "selected" : "" ?> >Femme</option>
                        <option value="nb" <?= ($u['gender']=="nb") ? "selected" : "" ?> >Non-binaire</option>
                        <option value="a" <?= ($u['gender']=="a") ? "selected" : "" ?> >Autre</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Orientation</td>
                    <td><select id="orientation" name="orientation">
                        <option disabled selected value></option>
                        <option value="het" <?= ($u['orientation']=="het") ? "selected" : "" ?> >Hétérosexuel(le)</option>
                        <option value="ho" <?= ($u['orientation']=="ho") ? "selected" : "" ?> >Homosexuel(le)</option>
                        <option value="bi" <?= ($u['orientation']=="bi") ? "selected" : "" ?> >Bisexuel(le)</option>
                        <option value="pan" <?= ($u['orientation']=="pan") ? "selected" : "" ?> >Pansexuel(le)</option>
                        <option value="as" <?= ($u['orientation']=="as") ? "selected" : "" ?> >Asexuel(le)</option>
                        <option value="a" <?= ($u['orientation']=="a") ? "selected" : "" ?> >Autre</option>
                        </select>
                    </td>
                </tr>

                <tr>
                    <td>Profession</td>
                    <td><input type="text" name="job" id="job" value="<?= htmlspecialchars($u['job']) ?>"></td>
                </tr>
                <tr>
                    <td>Lieu de résidence</td>
                    <td>selecteur de ville svp</td>
                </tr>
                <tr>
                    <td>Situation</td>
                    <td><select id="situation" name="situation">
                        <option disabled selected value></option>
                        <option value="single" <?= ($u['situation']=="single") ? "selected" : "" ?> >Célibataire</option>
                        <option value="open" <?= ($u['situation']=="open") ? "selected" : "" ?> >En couple libre</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Description physique</td>
                    <td>
                        <textarea name="desc" placeholder="brun, grand, yeux bruns..." cols="30" rows="2" maxlength="200" value="<?= htmlspecialchars($u['desc']) ?>"></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Bio</td>
                    <td>
                        <textarea name="bio" cols="64" rows="9" maxlength="1000" value="<?= htmlspecialchars($u['bio']) ?>" placeholder="Décrivez vos passions, quel genre de personne vous êtes... Cette description sera la première à apparaître sous votre profil quand d'autres utilisateurs vous trouverons. Faites bonne impression :)"></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Fumeur(se) ?</td>
                    <td><select id="user_smoke" name="user_smoke">
                        <option disabled selected value></option>
                        <option value="yes" <?= ($u['user_smoke']=="yes") ? "selected" : "" ?> >Oui</option>
                        <option value="no" <?= ($u['user_smoke']=="no") ? "selected" : "" ?> >Non</option>
                        </select>
                    </td>
                </tr>
            </table>

            <h2>Je recherche</h2>
            <table border="1" cellpading="20" cellspacing="0">
                <tr>
                    <td>Je recherche</td>
                    <td>
                        <ul>
                            <li><input type="checkbox" name="gender_search[]" id="h" value="h"><label for="h">Homme</label></li>
                            <li><input type="checkbox" name="gender_search[]" id="f" value="f"><label for="f">Femme</label></li>
                            <li><input type="checkbox" name="gender_search[]" id="nb" value="nb"><label for="nb">Non-binaire</label></li>
                            <li><input type="checkbox" name="gender_search[]" id="a" value="a"><label for="a">Autre</label></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>Type de relation</td>
                    <td>
                        <ul>
                            <li><input type="checkbox" name="rel_search[]" id="ro" value="ro"><label for="ro">Rencontres occasionnelles</label></li>
                            <li><input type="checkbox" name="rel_search[]" id="rs" value="rs"><label for="rs">Relation sérieuse</label></li>
                            <li><input type="checkbox" name="rel_search[]" id="rl" value="rl"><label for="rl">Relation sans lendemain</label></li>
                            <li><input type="checkbox" name="rel_search[]" id="ad" value="ad"><label for="ad">À découvrir au fil des échanges</label></li>
                            <li><input type="checkbox" name="rel_search[]" id="rne" value="rne"><label for="rne">Relation non exclusive</label></li>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <td>Fumeur(se) ?</td>
                    <td><select id="search_smoke" name="search_smoke">
                        <option disabled selected value></option>
                        <option value="yes" <?= ($u['search_smoke']=="yes") ? "selected" : "" ?> >Oui</option>
                        <option value="no" <?= ($u['search_smoke']=="no") ? "selected" : "" ?> >Non</option>
                        <option value="w" <?= ($u['search_smoke']=="w") ? "selected" : "" ?> >Peu importe</option>
                        </select>
                    </td>
                </tr>

            </table>
            <button type="submit" class="sub">Enregistrer</button>
        </form>
    </div>
</div>


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
