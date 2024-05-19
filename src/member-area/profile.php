<?php
require "_common.php";

Templates\member("Votre profil");
Templates\addStylesheet("/assets/style/profile-edit-page.css");
Templates\appendParam("head", '<script src="/scripts/location.js" type="module" defer></script>');

// Permettre de modifier un utilisateur de son choix si l'on est admin.
$notMe = false;
if (isset($_GET["id"]) && User\level(UserSession\loggedUserId()) >= User\LEVEL_ADMIN) {
    $u = UserDB\findById($_GET["id"]);
    $notMe = true;
    if ($u === null) {
        echo '<div class="not-found">Utilisateur introuvable !</div>';
        http_response_code(404);
        exit();
    }
} else {
    $u = UserSession\loggedUser();
}
// -1 : formulaire non envoyé
// 0  : profil mis à jour avec succès
// >0 : échec de la màj (code d'erreur User)
$submitCode = -1;

function fileExistsInAnyExtension($fName, $dir){
    $extensions = ['png', 'jpg', 'gif', 'jpeg'];

    foreach($extensions as $ex){
        if(file_exists($dir . $fName . '.' . $ex)){
            return $dir . $fName . '.' . $ex;
        }
    }
    return null;
}

function uploadImg($field, $userid){
    $target_dir = "../user-image-db/" . $userid . "/";
    @mkdir($target_dir, 0755, true);

    $fName = $_FILES[$field]["name"] ?? null;
    if (empty($fName)) {
        return null;
    }

    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($fName,PATHINFO_EXTENSION));
    $target_file = $target_dir . $userid . $field . "." . $imageFileType;

    // Check si c'est une image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES[$field]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $uploadOk = 0;
        }
    }

    // Check taille fichier
    if ($_FILES[$field]["size"] > 1000000) {
        echo '<script>alert("Le fichier est trop gros.")</script>';
        $uploadOk = 0;
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        echo '<script>alert("Seuls les formats JPG,JPEG,GIF,PNG sont acceptés.")</script>';
        $uploadOk = 0;
    }

    if ($uploadOk == 1) {
        $previousPfp = fileExistsInAnyExtension($userid, $target_dir);
        if($previousPfp !== null){
            unlink($previousPfp);
        }
        if (move_uploaded_file($_FILES[$field]["tmp_name"], $target_file)) {
            $public_url = '/user-image-db' . '/' . $userid . '/' . $userid . $field . "." . $imageFileType;
            return $public_url;
        } 
        else {
            echo '<script>alert("Une erreur est survenue.")</script>';
        }
    }
    return null;
}


// Si l'utilisateur a envoyé le formulaire en cliquant sur "Enregistrer"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // cas spécial pour la suppression de compte
    if (isset($_POST["delete"])) {
        if (isset($_POST["password"])) {
            $submitCode = User\deleteAccount($u["id"], $_POST["password"]);
        } else {
            $submitCode = User\ERR_INVALID_CREDENTIALS;
        }
        // bye...
        if ($submitCode === 0) {
            UserSession\signOut();
            header("Location: /");
            exit();
        }
    }
    else if (!empty(($_POST['mail'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['bdate'] && !empty($_POST['gender'])))) { //Si les champs ne sont pas vides

        $pfp = uploadImg("pfp", $u["id"]) ?? $u["pfp"];
        $pic1 = uploadImg("pic1", $u["id"]) ?? $u["pic1"];
        $pic2 = uploadImg("pic2", $u["id"]) ?? $u["pic2"];
        $pic3 = uploadImg("pic3", $u["id"]) ?? $u["pic3"];

        $ok = User\updateProfile($u["id"], array(
            "firstName" => $_POST['fname'],
            "lastName" => $_POST['name'],
            "email" => $_POST['mail'],
            "bdate" => $_POST['bdate'],
            "gender" => $_POST['gender'],
            ), array(
                "pfp" => ($pfp!== null) ? $pfp : "",
                "orientation" => (isset($_POST['orientation'])) ? $_POST['orientation'] : "",
                "job" => (isset($_POST['job'])) ? $_POST['job'] : "",
                "situation" => (isset($_POST['situation'])) ? $_POST['situation'] : "",
                "dep" => (isset($_POST['dep'])) ? $_POST['dep'] : "",
                "depName" => (isset($_POST['depName'])) ? $_POST['depName'] : "",
                "city" => (isset($_POST['city'])) ? $_POST['city'] : "",
                "cityName" => (isset($_POST['cityName'])) ? $_POST['cityName'] : "",
                "desc" => (isset($_POST['desc'])) ? $_POST['desc'] : "",
                "bio" => (isset($_POST['bio'])) ? $_POST['bio'] : "",
                "mathField" => (isset($_POST['mathField'])) ? $_POST['mathField'] : "",
                "eigenVal" => (isset($_POST['eigenVal'])) ? $_POST['eigenVal'] : "",
                "equation" => (isset($_POST['equation'])) ? $_POST['equation'] : "",
                "user_smoke" => (isset($_POST['user_smoke'])) ? $_POST['user_smoke'] : "",
                "search_smoke" => (isset($_POST['search_smoke'])) ? $_POST['search_smoke'] : "",
                "gender_search" => (isset($_POST['gender_search'])) ? $_POST['gender_search'] : [],
                "rel_search" => (isset($_POST['rel_search'])) ? $_POST['rel_search'] : [],
                "pic1" => ($pic1!== null) ? $pic1 : "",
                "pic2" => ($pic2!== null) ? $pic2 : "",
                "pic3" => ($pic3!== null) ? $pic3 : "",
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

$depFilePath = __DIR__ . "/../../data/departements-region.json"; // Emplacement du fichier JSON
?>

<h1 class="title">Profil <?= $notMe ? "de {$u["firstName"]} {$u["lastName"]}" : "" ?></h1>

<div class="profile-form-container">
    <div class="profile-form">
        <form method="post" enctype="multipart/form-data">
            <h2 class="-title">Compte<hr></h2>

            <div id="pfp">
                <img src="<?=(empty($u['pfp'])) ? User\DEFAULT_PFP : $u['pfp']?>" id="img-preview">
                <label for="pfp-upload" class="pfp-inside">
                    Changer la photo
                    <input type="file" class="d-none" accept="image/*" id="pfp-upload" name="pfp" onchange="loadFile('img-preview')">
                </label>
            </div>

            <div class="-grid-container">
                <label class="-grid-item" for="mail">Email</label>
                <div class="-grid-item"><input type="email" value="<?= htmlspecialchars($u['email']) ?>" name="mail" id="mail" required></div>
                
                <label class="-grid-item" for="pass-input">Mot de Passe</label>
                <div class="-grid-item"><input type="password" name="password" id="pass-input"></div>   
                
                <label class="-grid-item">Date d'inscription</label>
                <div class="-grid-item"><?= DateTime::createFromFormat('Y-m-d', $u['rdate'])->format('d/m/Y'); ?></div>
            </div>

            <?php if (!$notMe): ?>
                <input class="-form-btn -delete" type="submit" name="delete" value="Supprimer le compte" id="delete-account">
                <a class="-form-btn -public" href="/member-area/userProfile.php?id=<?= $u["id"] ?>">Voir mon profil public</a>
                <a class="-form-btn -visits" href="/member-area/profileVisits.php">Voir qui a visité mon profil</a>
            <?php endif; ?>
            <br><br>

            <h2 class="-title">Informations personnelles<hr></h2>
            <div class="-grid-container">
                <label class="-grid-item" for="name">Nom</label>
                <div class="-grid-item"><input type="text" value="<?= htmlspecialchars($u['lastName']) ?>" name="name" id="name" required></div>
                
                <label class="-grid-item" for="fname">Prénom</label>
                <div class="-grid-item"><input type="text" value="<?= htmlspecialchars($u['firstName']) ?>" name="fname" id="fname" required></div>
                
                <label class="-grid-item" for="bdate">Date de naissance</label>
                <div class="-grid-item"><input type="date" value="<?= htmlspecialchars($u['bdate']) ?>" name="bdate" id="bdate" required></div>

                <label class="-grid-item" for="gender">Genre</label>
                <div class="-grid-item">
                    <select id="gender" name="gender" required>
                        <option value="m" <?= ($u['gender']=="m") ? "selected" : "" ?>  >Homme</option>
                        <option value="f" <?= ($u['gender']=="f") ? "selected" : "" ?> >Femme</option>
                        <option value="nb" <?= ($u['gender']=="nb") ? "selected" : "" ?> >Non-binaire</option>
                    </select>
                </div>

                <label class="-grid-item" for="orientation">Orientation</label>
                <div class="-grid-item">
                    <select id="orientation" name="orientation">
                        <option disabled selected value></option>
                        <option value="het" <?= ($u['orientation']=="het") ? "selected" : "" ?> >Hétérosexuel(le)</option>
                        <option value="ho" <?= ($u['orientation']=="ho") ? "selected" : "" ?> >Homosexuel(le)</option>
                        <option value="bi" <?= ($u['orientation']=="bi") ? "selected" : "" ?> >Bisexuel(le)</option>
                        <option value="pan" <?= ($u['orientation']=="pan") ? "selected" : "" ?> >Pansexuel(le)</option>
                        <option value="as" <?= ($u['orientation']=="as") ? "selected" : "" ?> >Asexuel(le)</option>
                        <option value="a" <?= ($u['orientation']=="a") ? "selected" : "" ?> >Autre</option>
                    </select>
                </div>

                <label class="-grid-item" for="job">Profession</label>
                <div class="-grid-item"><input type="text" name="job" id="job" value="<?= htmlspecialchars($u['job']) ?>"></div>
                
                <label class="-grid-item" for="departmentSelect">Lieu de résidence</label>
                <div class="-grid-item">
                    <select id="departmentSelect" data-dep="<?= $u["dep"] ?>" name="dep">
                        <option disabled selected value> -- Département -- </option>
                    </select>
                    <br>
                    <select id="citySelect" class="d-none" data-city="<?= $u["city"] ?>" name="city">
                        <option disabled selected value> -- Ville -- </option>
                    </select>
                    <input type="hidden" name="depName" id="depNameInput" value="<?= htmlspecialchars($u["depName"]) ?>">
                    <input type="hidden" name="cityName" id="cityNameInput" value="<?= htmlspecialchars($u["cityName"]) ?>">
                </div>

                <label class="-grid-item" for="situation">Situation</label>
                <div class="-grid-item">
                    <select id="situation" name="situation">
                        <option disabled selected value></option>
                        <option value="single" <?= ($u['situation']=="single") ? "selected" : "" ?> >Célibataire</option>
                        <option value="open" <?= ($u['situation']=="open") ? "selected" : "" ?> >En couple libre</option>
                    </select>
                </div>

                <label class="-grid-item" for="desc">Description physique</label>
                <div class="-grid-item"><textarea name="desc" id="desc" class="-desc-input" placeholder="brun, grand, yeux bruns..." maxlength="200"><?php echo htmlspecialchars($u['desc']) ?></textarea></div>

                <label class="-grid-item" for="bio">Bio</label>
                <div class="-grid-item"><textarea name="bio" id="bio" class="-bio-input" maxlength="1000" placeholder="Décrivez vos passions, quel genre de personne vous êtes... Cette description sera la première à apparaître sous votre profil quand d'autres utilisateurs vous trouverons. Faites bonne impression :)"><?php echo htmlspecialchars($u['bio']) ?></textarea></div>
                
                <label class="-grid-item" id="mathField">Domaine préféré des maths</label>
                <div class="-grid-item"><input type="text" name="mathField" id="mathField" value="<?= htmlspecialchars($u['mathField']) ?>"></div>

                <label class="-grid-item" id="eigenVal">Valeurs propres</label>
                <div class="-grid-item"><textarea name="eigenVal" id="eigenVal" class="-desc-input" placeholder="Des valeurs qui vous sont propres... Par exemple, entraide, empathie..." maxlength="200"><?php echo htmlspecialchars($u['eigenVal']) ?></textarea></div>

                <label class="-grid-item" for="user_smoke">Fumeur(se) ?</label>
                <div class="-grid-item">
                    <select id="user_smoke" name="user_smoke">
                        <option disabled selected value></option>
                        <option value="yes" <?= ($u['user_smoke']=="yes") ? "selected" : "" ?> >Oui</option>
                        <option value="no" <?= ($u['user_smoke']=="no") ? "selected" : "" ?> >Non</option>
                    </select>
                </div>

                <label class="-grid-item" for="eq-input">Mon problème de maths favori</label>
                <div class="-grid-item">
                    <textarea name="equation" class="-bio-input" maxlength="1000" placeholder="Écrire une équation en notation TeX. Exemple : \int_{-\infty}^{\infty} e^{-x^2} \, dx = \sqrt{\pi}" id="eq-input"><?php echo htmlspecialchars($u['equation']) ?></textarea>
                    <div id="eq" class="has-math">$$ <?php echo htmlspecialchars($u['equation']) ?> $$</div>
                </div>
            </div>
            <br>

            <h2 class="-title">Je recherche<hr></h2>
            <div class="-grid-container">
                <label class="-grid-item">Genre</label>
                <div class="-grid-item" style="font-weight:bold;">
                    <ul>
                        <li><input type="checkbox" name="gender_search[]" id="m" value="m" <?= (in_array(User\GENDER_MAN, $u['gender_search'])) ? "checked" : "" ?> ><label for="m">Homme</label></li>
                        <li><input type="checkbox" name="gender_search[]" id="f" value="f" <?= (in_array(User\GENDER_WOMAN, $u['gender_search'])) ? "checked" : "" ?> ><label for="f">Femme</label></li>
                        <li><input type="checkbox" name="gender_search[]" id="nb" value="nb" <?= (in_array(User\GENDER_NON_BINARY, $u['gender_search'])) ? "checked" : "" ?> ><label for="nb">Non-binaire</label></li>
                    </ul>
                </div>

                <label class="-grid-item">Type de relation</label>
                <div class="-grid-item" style="font-weight:bold;">
                    <ul>
                        <li><input type="checkbox" name="rel_search[]" id="ro" value="ro" <?= (in_array("ro", $u['rel_search'])) ? "checked" : "" ?> ><label for="ro">Rencontres occasionnelles</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="rs" value="rs" <?= (in_array("rs", $u['rel_search'])) ? "checked" : "" ?> ><label for="rs">Relation sérieuse</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="rl" value="rl" <?= (in_array("rl", $u['rel_search'])) ? "checked" : "" ?> ><label for="rl">Relation sans lendemain</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="ad" value="ad" <?= (in_array("ad", $u['rel_search'])) ? "checked" : "" ?> ><label for="ad">À découvrir au fil des échanges</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="rne" value="rne"  <?= (in_array("rne", $u['rel_search'])) ? "checked" : "" ?> ><label for="rne">Relation non exclusive</label></li>
                    </ul>
                </div>

                <label class="-grid-item" for="search_smoke">Fumeur(se) ?</label>
                <div class="-grid-item">
                    <select id="search_smoke" name="search_smoke">
                        <option disabled selected value></option>
                        <option value="yes" <?= ($u['search_smoke']=="yes") ? "selected" : "" ?> >Oui</option>
                        <option value="no" <?= ($u['search_smoke']=="no") ? "selected" : "" ?> >Non</option>
                        <option value="w" <?= ($u['search_smoke']=="w") ? "selected" : "" ?> >Peu importe</option>
                    </select>
                </div>

            </div>
            <br>
            <h2 class="-title">Ma galerie<hr></h2>
            <div class="-grid-container">
                <div class="-grid-item" style="font-weight:bold;">Image 1</div>
                <div class="-grid-item">
                    <img src="<?=(empty($u['pic1'])) ? '' : $u['pic1']?>" id="img1-preview">
                    <br><label for="pic1-upload" class="upload-label">Importer une photo</label>
                    <input type="file" class="d-none" accept="image/*" id="pic1-upload" name="pic1" onchange="loadFile('img1-preview')">
                </div>

                <div class="-grid-item" style="font-weight:bold;">Image 2</div>
                <div class="-grid-item">
                    <img src="<?=(empty($u['pic2'])) ? '' : $u['pic2']?>" id="img2-preview">
                    <br><label for="pic2-upload" class="upload-label">Importer une photo</label>
                    <input type="file" class="d-none" accept="image/*" id="pic2-upload" name="pic2" onchange="loadFile('img2-preview')">
                </div>

                <div class="-grid-item" style="font-weight:bold;">Image 3</div>
                <div class="-grid-item">
                    <img src="<?=(empty($u['pic3'])) ? '' : $u['pic3']?>" id="img3-preview">
                    <br><label for="pic3-upload" class="upload-label">Importer une photo</label>
                    <input type="file" class="d-none" accept="image/*" id="pic3-upload" name="pic3" onchange="loadFile('img3-preview')">
                </div>
            </div>

            <br>
            <button type="submit" class="sub">Enregistrer</button>
        </form>
    </div>
</div>


<?php if ($errStr !== null): ?>
    <p id="err"><?= htmlspecialchars($errStr) ?></p>
<?php elseif ($submitCode == 0): ?>
    <p id="all-good">Données enregistrées avec succès !</p>
<?php endif ?>

<script type="module">
    import {typeset} from "/scripts/math.js";

    //bouton suppression du compte
    document.getElementById("delete-account")?.addEventListener("click", function(e) {
        if (document.getElementById("pass-input").value == "") {
            e.preventDefault();
            alert("Veuillez entrer votre mot de passe pour supprimer votre compte.");
            document.getElementById("pass-input").focus(); // user friendly ??
        } else {
            if (!confirm("Voulez vous vraiment supprimer votre compte ?")) {
                e.preventDefault();
            }
        }
    });

    //fonction pour prévisualiser la photo chargée
    window.loadFile = function loadFile(id){
        var preview = document.getElementById(id);
        preview.src = URL.createObjectURL(event.target.files[0]);
        preview.onload = function() {
            URL.revokeObjectURL(preview.src) // free memory
        }
    }

    //affichage de l'équation mathjax
    const eq = document.getElementById("eq");
    document.getElementById("eq-input").addEventListener("input", e => {
        typeset(() => {
            eq.innerHTML = "$$"+ e.target.value +"$$";
            return [eq];
        })
    })

    function regNameUpdate(dropdown, input) {
        dropdown.addEventListener("change", e => {
            const opt = dropdown.options[dropdown.selectedIndex];
            input.value = opt.dataset.publicVal;
        })
    }
    regNameUpdate(document.getElementById("departmentSelect"), document.getElementById("depNameInput"));
    regNameUpdate(document.getElementById("citySelect"), document.getElementById("cityNameInput"));
</script>
