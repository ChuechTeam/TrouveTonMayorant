<?php

require_once __DIR__ . "/../modules/user.php";

/**
 * Print l'HTML du profil d'un utilisateur `$u`. Si `$full` est `true`, alors le profil
 * complet sera affiché, sinon, une version abrégée sera utilisée.
 * @param array $u l'utilisateur
 * @param bool $full si on doit afficher le profil complet
 * @return void
 */
function profileCard(array $u, bool $full = false) {
    // Âge de l'utilisateur (> 18 ans normalement sauf si qqun fait des choses illégales)
    $age = (new DateTime($u["bdate"]))->diff(new DateTime())->y;
    switch ($u["gender"]) {
        case \User\GENDER_MAN:
            $gender = "Homme";
            break;
        case \User\GENDER_WOMAN:
            $gender = "Femme";
            break;
        case \User\GENDER_NON_BINARY:
            $gender = "Non-binaire";
            break;
        default:
            $gender = "Autre";
            break;
    }

    // Biographie tronquée si trop grande
    $bio = $u["bio"];
    if (!$full && strlen($bio) > 80) {
        // abrège
        $bio = substr($bio, 0, 80) . "...";
    }

    // profil complet : tableau des choix de relation
    // profil abrégé : version raccourcie dans un string
    $rls = null;
    if ($full) {
        $rls = [];
        foreach ($u["rel_search"] as $rel) {
            switch ($rel) {
                case \User\REL_OCCASIONAL:
                    $rls[] = "des rencontres occasionnelles";
                    break;
                case \User\REL_SERIOUS:
                    $rls[] = "une relation sérieuse";
                    break;
                case \User\REL_NO_TOMORROW:
                    $rls[] = "une relation sans lendemain";
                    break;
                case \User\REL_TALK_AND_SEE:
                    $rls[] = "...discutez avec moi, vous verrez bien";
                    break;
                case \User\REL_NON_EXCLUSIVE:
                    $rls[] = "une relation non exclusive";
                    break;
            }
        }
    } else {
        
        if (count($u["rel_search"]) == 5) {
            $rls = "Tout type de relation";
        } else {
            for ($i = 0; $i < count($u["rel_search"]); $i++) {
                switch ($u["rel_search"][$i]) {
                    case \User\REL_OCCASIONAL:
                        $rls .= "Occasionnel";
                        break;
                    case \User\REL_SERIOUS:
                        $rls .= "Sérieux";
                        break;
                    case \User\REL_NO_TOMORROW:
                        $rls .= "Sans lendemain";
                        break;
                    case \User\REL_TALK_AND_SEE:
                        $rls .= "À découvrir";
                        break;
                    case \User\REL_NON_EXCLUSIVE:
                        $rls .= "Non-exclusif";
                        break;
                }
                if ($i != count($u["rel_search"]) - 1) {
                    $rls .= "/";
                }
            }
        }
    }

    // Situation de couple
    $sit = null;
    switch ($u["situation"]) {
        case User\SITUATION_SINGLE:
            $sit = "Célibataire";
            break;
        case User\SITUATION_OPEN:
            $sit = "Couple libre";
            break;
    }

    // Profil complet uniquement
    if ($full) {
        // Label pour l'orientation sexuelle (s'adapte selon le genre)
        $colloquialOrient = null;
        switch ($u["orientation"]) {
            case User\ORIENTATION_HETERO:
                $colloquialOrient = $u["gender"] == \User\GENDER_MAN ? "Hétérosexuel" :
                    ($u["gender"] == \User\GENDER_WOMAN ? "Hétérosexuelle" : "Hétérosexuel(le)");
                break;
            case User\ORIENTATION_HOMO:
                $colloquialOrient =
                    $u["gender"] == \User\GENDER_MAN ? "Gay" :
                        ($u["gender"] == \User\GENDER_WOMAN ? "Lesbienne" : "Homosexuel(le)");
                break;
            case User\ORIENTATION_ASEXUAL:
                $colloquialOrient = $u["gender"] == \User\GENDER_MAN ? "Asexuel" :
                    ($u["gender"] == \User\GENDER_WOMAN ? "Asexuelle" : "Asexuel(le)");
                break;
            case User\ORIENTATION_BI:
                $colloquialOrient = $u["gender"] == \User\GENDER_MAN ? "Bisexuel" :
                    ($u["gender"] == \User\GENDER_WOMAN ? "Bisexuelle" : "Bisexuel(le)");
                break;
            case User\ORIENTATION_PAN:
                $colloquialOrient = $u["gender"] == \User\GENDER_MAN ? "Pansexuel" :
                    ($u["gender"] == \User\GENDER_WOMAN ? "Pansexuelle" : "Pansexuel(le)");
                break;
            case User\ORIENTATION_OTHER:
                $colloquialOrient = "Autre";
                break;
        }

        // Préférences de genre
        $genderPref = [];
        if (count($u["gender_search"]) == 3) {
            $genderPref[] = "une personne de n'importe quel genre";
        } else {
            foreach ($u["gender_search"] as $g) {
                switch ($g) {
                    case User\GENDER_MAN:
                        $genderPref[] = "un homme";
                        break;
                    case User\GENDER_WOMAN:
                        $genderPref[] = "une femme";
                        break;
                    case User\GENDER_NON_BINARY:
                    case User\GENDER_OTHER: // une personne d'autre genre ???
                        $genderPref[] = "une personne non-binaire";
                        break;
                }
            }
        }

        $convUrl = "/member-area/chat.php?startNew=" . $u["id"];
    }

    // Label fumeur ou non fumeur
    $smokeLabel = null;
    if (!empty($u["user_smoke"])) {
        $smokeLabel = "";
        
        // On met le préfixe Non- si on ne fume pas.
        if ($u["user_smoke"] === "no") {
            $smokeLabel = "Non-";
        }
        
        // Ajouter le "fumeur" ou "fumeuse" ou "fumeu·r·se"
        $smokeLabel .= $u["gender"] == \User\GENDER_MAN ? "Fumeur"
            : ($u["gender"] == \User\GENDER_WOMAN ? "Fumeuse" : "Fumeu·r·se");
    }
    
    // Description physique
    $phys = !empty($u["desc"]) ? $u["desc"] : null;

    // Si l'utilisateur a l'abonnement TTM Sup
    $sup = \User\level($u["id"]) >= \User\LEVEL_SUBSCRIBER;
    if ($full) {
        $supClass = $sup ? " -sup" : "";
    }

    // Nom complet
    $fn = $u["firstName"] . " " . $u["lastName"];

    // A des préférences de relations
    $hasRelPref = !empty($rls);
    // A des préférences de genre
    $hasGenderPref = !empty($genderPref);
    // A des préférences de fumeur/non-fumeur
    $hasSmokePref = $u["search_smoke"] === "yes" || $u["search_smoke"] === "no";

    // A des préférences en général
    $hasPrefs = $hasRelPref || $hasGenderPref || $hasSmokePref;
    ?>

    <?php if ($full): ?>
        <article class="full-profile<?= $supClass ?>">
            <aside class="-primary-infos">
                <h1 class="-name"><?= htmlspecialchars($fn) ?></h1>
                <span class="-gender-age"><?= $gender ?> | <?= $age ?> ans</span>
                <?php if ($sup): ?> <img src="/assets/sup.svg" class="sup-icon" alt="Logo TTM Sup"> <?php endif ?>
            </aside>
            <aside class="-secondary-infos">
                <?php if ($sit !== null): ?>
                    <div class="pill -situation">
                        <span class="-label">Statut</span>
                        <span class="-value"><?= $sit ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($colloquialOrient !== null): ?>
                    <div class="pill -orientation">
                        <span class="-label">Orientation</span>
                        <span class="-value"><?= $colloquialOrient ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($u["job"])): ?>
                    <div class="pill -job">
                        <span class="-label">Profession</span>
                        <span class="-value"><?= htmlspecialchars($u["job"]) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($smokeLabel)): ?>
                    <div class="pill -smoke -label-only"><?= $smokeLabel ?></div>
                <?php endif; ?>

                <?php if ($hasPrefs): ?>
                    <hr>
                    <?php if ($hasRelPref): ?>
                        <h3>Je recherche</h3>
                        <ul class="-pref-list">
                            <?php foreach ($rls as $r) echo "<li>$r</li>"; ?>
                        </ul>
                    <?php endif; ?>

                    <?php if ($hasGenderPref || $hasSmokePref): ?>
                        <h3>Avec...</h3>
                        <ul class="-pref-list">
                            <?php foreach ($genderPref as $g) echo "<li>$g</li>"; ?>
                            <?php if ($hasSmokePref):
                                $smokeClass = $u["search_smoke"] === "no" ? "-not" : ""; ?>
                                <li class="<?= $smokeClass ?> ">une personne qui fume</li>
                            <?php endif; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
            <section class="-main">
                <h2>À propos de moi</h2>
                <p class="-bio"><?= htmlspecialchars($bio) ?> </p>
                <?php if ($phys !== null): ?>
                    <h2>Ma description physique</h2>
                    <p class="-phys"><?= $phys ?></p>
                <?php endif; ?>
                <h2>Bientôt</h2>
                <p>Les valeurs propres, les problèmes en tête d'affiche...</p>
                <button onclick="window.location.href = '<?= $convUrl ?>';">Démarrer une conversation</button>
            </section>
        </article>
    <?php else: ?>
        <article class="profile-card" data-id="<?= $u["id"] ?>">
            <div class="-name"><?= htmlspecialchars($fn) ?></div>
            <div class="-bio"><?= htmlspecialchars($bio) ?> </div>
            <div class="-details">
                <div class="-gender"><?= $gender ?></div>
                <div class="-age"><?= $age ?> ans</div>

                <?php if ($sit !== null): ?>
                    <div class="-situation"><?= $sit ?></div>
                <?php endif; ?>

                <?php if ($rls !== null): ?>
                    <div class="-rel-search"><?= $rls ?></div>
                <?php endif; ?>

                <?php if (!empty($smokeLabel)): ?>
                    <div class="-smoke"><?= $smokeLabel ?></div>
                <?php endif; ?>
            </div>
        </article>
    <?php endif;
} ?>
