<?php
require_once "_common.php";
Templates\member("Accueil");
Templates\addStylesheet("/assets/style/search-page.css");
Templates\appendParam("head", '<script src="/scripts/location.js" type="module" defer></script>
<script src="/scripts/search.js" type="module" defer></script>');

require_once "../modules/viewDB.php";

// Find out the default values for the age slider.
$myAge = User\age($user);
$defAgeMin = max($myAge - 5, 18);
$defAgeMax = min($myAge + 5, 100);

// Some functions to pre-check checkboxes according to the user's preferences.
function checkedIfRel(string $rel) {
    global $user;
    if (in_array($rel, $user["rel_search"])) {
        echo "checked";
    }
}

function checkedIfGender(string $gender) {
    global $user;
    if (in_array($gender, $user["gender_search"])) {
        echo "checked";
    }
}

function checkedIfSmoke(string $smoke) {
    global $user;
    if ($user["search_smoke"] === $smoke) {
        echo "checked";
    }
}
?>

<h1 class="title">Bonyour, <?= htmlspecialchars($user["firstName"] . " " . $user["lastName"]) ?> !</h1>
<search id="search">
    <h2 style="text-align: center;">Recherchez des profils, trouvez votre prochain mayorant.</h2>
    <div id="fields">
        <form id="search-form">
            <div class="-group">
                <header class="field-header -disable-on0checks">
                    <span class="-name">Genre</span>
                    <button class="-reset icon" type="button" data-reset="gender[]">ink_eraser</button>
                </header>
                <ul class="field multi-select">
                    <li>
                        <input type="checkbox" name="gender[]" value="f"
                               id="searchWoman"
                            <?php checkedIfGender(User\GENDER_WOMAN); ?>>
                        <label for="searchWoman">Femme</label></li>
                    <li>
                        <input type="checkbox" name="gender[]" value="m" id="searchMan"
                            <?php checkedIfGender(User\GENDER_MAN); ?>>
                        <label for="searchMan">Homme</label>
                    </li>
                    <li><input type="checkbox" name="gender[]" value="nb" id="searchNB"
                            <?php checkedIfGender(User\GENDER_MAN); ?>>
                        <label for="searchNB">Non-binaire</label>
                    </li>
                </ul>
            </div>
            <div class="-group">
                <header class="field-header -disable-on0checks">
                    <span class="-name">Fumeur</span>
                    <button class="-reset icon" type="button" data-reset="smoker">ink_eraser</button>
                </header>
                <ul class="field multi-select">
                    <li>
                        <input type="checkbox" name="smoker" value="yes"
                               id="searchSmokeYes" <?php checkedIfSmoke(User\PREF_YES); ?>>
                        <label for="searchSmokeYes">Oui</label>
                    </li>
                    <li>
                        <input type="checkbox" name="smoker" value="no"
                               id="searchSmokeNo" <?php checkedIfSmoke(User\PREF_NO); ?>>
                        <label for="searchSmokeNo">Non</label>
                    </li>
                </ul>
            </div>
            <div class="-group">
                <header class="field-header -disable-on-def-dep">
                    <span class="-name">Emplacement</span>
                    <button class="-reset icon" type="button" data-reset-dep>ink_eraser</button>
                </header>
                <ul class="field location">
                    <select id="departmentSelect" name="dep" data-allow-empty
                            onchange="this.dataset.chosen = this.value;"
                            <?= $user["dep"] !== null ? "data-dep={$user["dep"]}" : "" ?>
                            data-chosen="<?= $user["dep"] ?? "" ?>">
                        <option selected value>[Tous les départements]</option>
                    </select>
                    <select id="citySelect" class="d-none" name="city" data-allow-empty="[Toutes les villes]">
                    </select>
                </ul>
            </div>
            <div class="-group">
                <header class="field-header">
                    <span class="-name">Âge</span>
                </header>
                <div class="field">
                    <div class="wrapper">
                        <div class="container">
                            <div class="slider-track"></div>
                            <input type="range" min="18" max="100" value="<?= $defAgeMin ?>" id="slider-1">
                            <input type="range" min="18" max="100" value="<?= $defAgeMax ?>" id="slider-2">
                        </div>

                        <div class="values">
                            <span id="range1">20</span>
                            <span>&dash;</span>
                            <span id="range2">30</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="-group -right">
                <header class="field-header -disable-on0checks">
                    <span class="-name">Statut</span>
                    <button class="-reset icon" type="button" data-reset="situation[]">ink_eraser</button>
                </header>
                <ul class="field multi-select">
                    <li>
                        <input type="checkbox" name="situation[]" id="sitSingle" value="<?= \User\SITUATION_SINGLE ?>">
                        <label for="sitSingle">Célibataire</label>
                    </li>
                    <li>
                        <input type="checkbox" name="situation[]" id="sitOpen" value="<?= \User\SITUATION_OPEN ?>">
                        <label for="sitOpen">Couple libre</label>
                    </li>
                </ul>
            </div>
            <div class="-group -right-large">
                <header class="field-header -disable-on0checks">
                    <span class="-name">Relation voulue</span>
                    <button class="-reset icon" type="button" data-reset="rel_search[]">ink_eraser</button>
                </header>
                <ul class="field relationship multi-select -col">
                    <li>
                        <input type="checkbox" name="rel_search[]" id="ro" value="ro"
                            <?php checkedIfRel(User\REL_OCCASIONAL); ?>>
                        <label for="ro">Rencontre occasionnelle</label>
                    </li>
                    <li>
                        <input type="checkbox" name="rel_search[]" id="rs" value="rs"
                            <?php checkedIfRel(User\REL_SERIOUS); ?>>
                        <label for="rs">Relation sérieuse</label>
                    </li>
                    <li>
                        <input type="checkbox" name="rel_search[]" id="rl" value="rl"
                            <?php checkedIfRel(User\REL_NO_TOMORROW); ?>>
                        <label for="rl">Relation sans lendemain</label>
                    </li>
                    <li>
                        <input type="checkbox" name="rel_search[]" id="ad" value="ad"
                            <?php checkedIfRel(User\REL_TALK_AND_SEE); ?>>
                        <label for="ad">À découvrir au fil des échanges</label>
                    </li>
                    <li>
                        <input type="checkbox" name="rel_search[]" id="rne" value="rne"
                            <?php checkedIfRel(User\REL_NON_EXCLUSIVE); ?>>
                        <label for="rne">Relation non exclusive</label>
                    </li>
                </ul>
            </div>
        </form>
        <button class="sub" id="search-button">Recherche</button>
    </div>

    <output id="results"></output>
</search>
