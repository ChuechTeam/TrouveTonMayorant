<?php
require "_common.php";

Templates\member("Votre profil");

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

function uploadImg($userid){
    $target_dir = "../user-image-db/";
    @mkdir($target_dir, 0755, false);

    $fName = $_FILES["pfp"]["name"] ?? null;
    if (empty($fName)) {
        return null;
    }

    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($fName,PATHINFO_EXTENSION));
    $target_file = $target_dir . $userid . "." . $imageFileType;

    // Check si c'est une image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["pfp"]["tmp_name"]);
        if($check !== false) {
            $uploadOk = 1;
        } else {
            $uploadOk = 0;
        }
    }

    // Check taille fichier
    if ($_FILES["pfp"]["size"] > 1000000) {
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
        if (move_uploaded_file($_FILES["pfp"]["tmp_name"], $target_file)) {
            $public_url = '/user-image-db' . '/' . $userid . "." . $imageFileType;
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
        $pfp = uploadImg($u["id"]) ?? $u["pfp"];
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
        <form method="post" style="font-weight:800;" enctype="multipart/form-data">
            <h2 class="-title">Compte<hr></h2>

            <div id="pfp">
                <img src="<?=(empty($u['pfp'])) ? User\DEFAULT_PFP : $u['pfp']?>" id="img-preview">
                <div class="pfp-inside">
                    <label for="file-upload">Changer la photo</label>
                    <input type="file" class="d-none" accept="image/*" id="file-upload" name="pfp" onchange="loadFile()">
                </div>
            </div>

            <div class="-grid-container">
                <div class="-grid-item">Email</div>
                <div class="-grid-item"><input type="email" value="<?= htmlspecialchars($u['email']) ?>" name="mail" id="" required></div>   
                
                <div class="-grid-item">Mot de Passe</div>
                <div class="-grid-item"><input type="password" name="password" id="pass-input"></div>   
                
                <div class="-grid-item" >Date d'inscription</div>
                <div class="-grid-item" style="font-weight:400;"><?= DateTime::createFromFormat('Y-m-d', $u['rdate'])->format('d/m/Y'); ?></div>
            </div>

            <?php if (!$notMe): ?>
                <input class="-delete" type="submit" name="delete" value="Supprimer le compte" id="delete-account">
            <?php endif; ?>
            <br><br>

            <h2 class="-title">Informations personnelles<hr></h2>
            <div class="-grid-container">
                <div class="-grid-item">Nom</div>
                <div class="-grid-item"><input type="text" value="<?= htmlspecialchars($u['lastName']) ?>" name="name" id="" required></div>   
                
                <div class="-grid-item">Prénom</div>
                <div class="-grid-item"><input type="text" value="<?= htmlspecialchars($u['firstName']) ?>" name="fname" id="" required></div>   
                
                <div class="-grid-item">Date de naissance</div>
                <div class="-grid-item"><input type="date" value="<?= htmlspecialchars($u['bdate']) ?>" name="bdate" id="" required></div>

                <div class="-grid-item" >Genre</div>
                <div class="-grid-item">
                    <select id="gender" name="gender" required>
                        <option value="m" <?= ($u['gender']=="m") ? "selected" : "" ?>  >Homme</option>
                        <option value="f" <?= ($u['gender']=="f") ? "selected" : "" ?> >Femme</option>
                        <option value="nb" <?= ($u['gender']=="nb") ? "selected" : "" ?> >Non-binaire</option>
                    </select>
                </div>

                <div class="-grid-item">Orientation</div>
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

                <div class="-grid-item">Profession</div>
                <div class="-grid-item"><input type="text" name="job" id="job" value="<?= htmlspecialchars($u['job']) ?>"></div>
                
                <div class="-grid-item">Lieu de résidence</div>
                <div class="-grid-item">
                    <select id="departmentSelect" name="dep">
                        <option disabled selected value> -- Département -- </option>
                    </select>
                    <br>
                    <select id="citySelect" class="d-none" name="city">
                        <option disabled selected value> -- Ville -- </option>
                    </select>
                    <input type="hidden" name="depName" id="depNameInput" value="<?= htmlspecialchars($u["depName"]) ?>">
                    <input type="hidden" name="cityName" id="cityNameInput" value="<?= htmlspecialchars($u["cityName"]) ?>">
                </div>

                <div class="-grid-item">Situation</div>
                <div class="-grid-item">
                    <select id="situation" name="situation">
                        <option disabled selected value></option>
                        <option value="single" <?= ($u['situation']=="single") ? "selected" : "" ?> >Célibataire</option>
                        <option value="open" <?= ($u['situation']=="open") ? "selected" : "" ?> >En couple libre</option>
                    </select>
                </div>

                <div class="-grid-item">Description physique</div>
                <div class="-grid-item"><textarea name="desc" class="-desc-input" placeholder="brun, grand, yeux bruns..." maxlength="200"><?php echo htmlspecialchars($u['desc']) ?></textarea></div>

                <div class="-grid-item">Bio</div>
                <div class="-grid-item"><textarea name="bio" class="-bio-input" maxlength="1000" placeholder="Décrivez vos passions, quel genre de personne vous êtes... Cette description sera la première à apparaître sous votre profil quand d'autres utilisateurs vous trouverons. Faites bonne impression :)"><?php echo htmlspecialchars($u['bio']) ?></textarea></div>
                
                <div class="-grid-item">Domaine préféré des maths</div>
                <div class="-grid-item"><input type="text" name="mathField" id="mathField" value="<?= htmlspecialchars($u['mathField']) ?>"></div>

                <div class="-grid-item">Valeurs propres</div>
                <div class="-grid-item"><textarea name="eigenVal" class="-desc-input" placeholder="Des valeurs qui vous sont propres... Par exemple, entraide, empathie..." maxlength="200"><?php echo htmlspecialchars($u['eigenVal']) ?></textarea></div>

                <div class="-grid-item">Fumeur(se) ?</div>
                <div class="-grid-item">
                    <select id="user_smoke" name="user_smoke">
                        <option disabled selected value></option>
                        <option value="yes" <?= ($u['user_smoke']=="yes") ? "selected" : "" ?> >Oui</option>
                        <option value="no" <?= ($u['user_smoke']=="no") ? "selected" : "" ?> >Non</option>
                    </select>
                </div>

                <div class="-grid-item">Mon problème de maths favori</div>
                <div class="-grid-item">
                    <textarea name="equation" class="-bio-input" maxlength="1000" placeholder="Écrire une équation en notation TeX. Exemple : \int_{-\infty}^{\infty} e^{-x^2} \, dx = \sqrt{\pi}" id="eq-input"><?php echo htmlspecialchars($u['equation']) ?></textarea>
                    <div id="eq" class="has-math">$$ <?php echo htmlspecialchars($u['equation']) ?> $$</div>
                </div>
            </div>
            <br>

            <h2 class="-title">Je recherche<hr></h2>
            <div class="-grid-container">
                <div class="-grid-item">Genre</div>
                <div class="-grid-item" style="font-weight:400;">
                    <ul>
                        <li><input type="checkbox" name="gender_search[]" id="m" value="m" <?= (in_array("h", $u['gender_search'])) ? "checked" : "" ?> ><label for="m">Homme</label></li>
                        <li><input type="checkbox" name="gender_search[]" id="f" value="f" <?= (in_array("f", $u['gender_search'])) ? "checked" : "" ?> ><label for="f">Femme</label></li>
                        <li><input type="checkbox" name="gender_search[]" id="nb" value="nb" <?= (in_array("nb", $u['gender_search'])) ? "checked" : "" ?> ><label for="nb">Non-binaire</label></li>
                        <li><input type="checkbox" name="gender_search[]" id="a" value="a" <?= (in_array("a", $u['gender_search'])) ? "checked" : "" ?> ><label for="a">Autre</label></li>
                    </ul>
                </div>

                <div class="-grid-item">Type de relation</div>
                <div class="-grid-item" style="font-weight:400;">
                    <ul>
                        <li><input type="checkbox" name="rel_search[]" id="ro" value="ro" <?= (in_array("ro", $u['rel_search'])) ? "checked" : "" ?> ><label for="ro">Rencontres occasionnelles</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="rs" value="rs" <?= (in_array("rs", $u['rel_search'])) ? "checked" : "" ?> ><label for="rs">Relation sérieuse</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="rl" value="rl" <?= (in_array("rl", $u['rel_search'])) ? "checked" : "" ?> ><label for="rl">Relation sans lendemain</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="ad" value="ad" <?= (in_array("ad", $u['rel_search'])) ? "checked" : "" ?> ><label for="ad">À découvrir au fil des échanges</label></li>
                        <li><input type="checkbox" name="rel_search[]" id="rne" value="rne"  <?= (in_array("rne", $u['rel_search'])) ? "checked" : "" ?> ><label for="rne">Relation non exclusive</label></li>
                    </ul>
                </div>

                <div class="-grid-item">Fumeur(se) ?</div>
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
        color: white;
        background: linear-gradient(90deg, rgba(2,0,36,1) 0%, rgba(37,173,44,1) 0%, rgba(54,207,9,1) 50%, rgba(37,173,44,1) 100%);        
        border : 1px solid darkgreen;
        border-radius : 7px;
        position: fixed;
        bottom: 0;
        font-size: 15px;
        padding : 1px 2px 1px 2px ;
        font-weight: bolder;
    }
</style>


<script type="module">
    import {typeset} from "/scripts/math.js";

    document.addEventListener("DOMContentLoaded", function() {
        const departmentSelect = document.getElementById('departmentSelect');
        const citySelect = document.getElementById('citySelect');
        let previousDep = null;
        let userDep = "<?= $u['dep'] ?>";
        let userCity = "<?= $u['city'] ?>";

        // Récupère les données du fichier JSON
        fetch('departements-region.json')
        .then(response => response.json())
        .then(data => {
            
            
            // Ajoute des options pour le menu déroulant des départements avec les départements du fichier JSON
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item.num_dep;
                option.textContent = item.dep_name;
                departmentSelect.appendChild(option);
                if(userDep == option.value){
                    option.selected = true;
                    previousDep = userDep;
                    filterCities(userDep);
                    citySelect.classList.remove('d-none');
                }
            });
        })
        .catch(error => console.error('Error fetching JSON:', error));
        
        // Filtre les villes selon le département sélectionné
        function filterCities(selectedDep){
            citySelect.innerHTML = '<option disabled selected value> -- Ville -- </option>';

            // Récupère les données du fichier JSON
            fetch('cities.json')
            .then(response => response.json())
            .then(data => {
                let dataCities = Array.from(new Set(data.cities.map(JSON.stringify))).map(JSON.parse); //nécessaire pour retirer les dupliqués (parce que le json officiel du gouvernement il est nul)
                dataCities.forEach(item => {
                    if(item.department_number === selectedDep){
                        const option = document.createElement('option');
                        option.value = item.insee_code;
                        var cityName = item.city_code.split(" ");
                        for (let i = 0; i < cityName.length; i++) {
                            cityName[i] = cityName[i][0].toUpperCase() + cityName[i].substr(1);
                        }

                        cityName = cityName.join(" ");
                        option.textContent = cityName;
                        citySelect.appendChild(option);
                        if(userCity == option.value){
                            option.selected = true;
                        }
                    }
                });
            })
            .catch(error => console.error('Error fetching JSON:', error));
        }
        
        // Affiche le menu déroulant des villes lorsqu'une option de département est choisie
        departmentSelect.addEventListener('change', function(){
            const selectedDep = this.value;
            if(selectedDep !== previousDep){
                filterCities(selectedDep);
                previousDep = selectedDep;
            }
            if(selectedDep){
                citySelect.classList.remove('d-none');
            }
        });
    });

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

    //fonction pour prévisualiser la photo de profil chargée
    function loadFile(){
        var preview = document.getElementById('img-preview');
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
            input.value = opt.text;
        })
    }
    regNameUpdate(document.getElementById("departmentSelect"), document.getElementById("depNameInput"));
    regNameUpdate(document.getElementById("citySelect"), document.getElementById("cityNameInput"));
</script>
