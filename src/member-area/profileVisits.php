<?php

require "_common.php";
require_once "../modules/viewDB.php";
require_once "_profileCard.php";

Templates\member("Vos visiteurs");
Templates\addStylesheet("/assets/style/profile-visits-page.css");

echo '<h1 class="title">Statistiques de votre profil</h1>';

$hasRights = \UserSession\level() >= \User\LEVEL_SUBSCRIBER;
if (!$hasRights):
    srand($user["id"]);
    ?>
    <div id="stats">
        <!-- BTW those aren't the real stats -->
        <div class="-stat -not-sup">
            <h2 class="-title">Visites</h2>
            <div class="-value"><span><?= rand(0, 500) ?></span></div>
        </div>
        <div class="-stat -not-sup">
            <h2 class="-title">Pourcentage d'hommes</h2>
            <div class="-value"><span><?= rand(0, 100) ?>%</span></div>
        </div>
        <div class="-stat -not-sup">
            <h2 class="-title">Pourcentage de femmes</h2>
            <div class="-value"><span><?= rand(0, 100) ?>%</span></div>
        </div>
        <div class="-stat -not-sup">
            <h2 class="-title">Âge moyen</h2>
            <div class="-value"><span><?= rand(18, 100) ?> ans</span></div>
        </div>
    </div>
    <p id="not-subscribed"><span class="icon">lock</span> Découvrez qui a visité votre profil en achetant un abonnement
        TTM <img class="sup-icon -white" src="/assets/sup.svg"> !</p>
    <?php
    exit;
endif;

$view = \ViewDB\read($user["id"]);

$visitors = [];
$n = 0;
$m = 0;
$f = 0;
$ageSum = 0;
foreach ($view["views"] as $v) {
    $u = \UserDB\findById($v["who"]);
    if ($u !== null) {
        $n++;
        if ($u["gender"] === User\GENDER_MAN) {
            $m++;
        } else if ($u["gender"] === User\GENDER_WOMAN) {
            $f++;
        }
        $ageSum += \User\age($u["id"]);

        // Only display users not blocking the user, and not blocked by the user.
        if (\User\blockStatus($user["id"], $u["id"]) === \User\BS_NO_BLOCK) {
            $date = DateTimeImmutable::createFromFormat(\ViewDB\DATE_FORMAT, $v["date"]);
            $visitors[] = [$u, $v, $date];
        }
    }
}

$mProp = $n === 0 ? 0 : (int)round(((float)$m / $n) * 100);
$fProp = $n === 0 ? 0 : (int)round(((float)$f / $n) * 100);
$avgAge = $n === 0 ? 0 : (int)round($ageSum / $n);

usort($visitors, function ($a, $b) {
    // value < 0 --> a < b --> first in the array,
    // value > 0 --> a > b --> last in the array,
    // We want the highest date to be first in the table.
    // So, when a > b, we need to return a NEGATIVE value (counterintuitive, I know).

    // a - b
    $diff = $b[2]->diff($a[2]);

    // $diff->invert == 0 <==> a - b > 0 <==> a > b
    return $diff->invert === 0 ? -1 : 1;
});
?>

<div id="stats">
    <div class="-stat">
        <div class="-bg-equation has-math -wait-mathjax">$$ \mathrm{card}(V) $$</div>
        <h2 class="-title">Visites</h2>
        <div class="-value"><?= $view["viewCount"] ?></div>
    </div>
    <div class="-stat">
        <div class="-bg-equation has-math -wait-mathjax">$$ \frac{100}{n} \sum_{i=1}^n \delta_{g_i}^{\mathrm{homme}}
            $$
        </div>
        <h2 class="-title">Pourcentage d'hommes</h2>
        <div class="-value"><?= $mProp ?>%</div>
    </div>
    <div class="-stat">
        <div class="-bg-equation has-math -wait-mathjax">$$ \frac{100}{n} \sum_{i=1}^n \delta_{g_i}^{\mathrm{femme}}
            $$
        </div>
        <h2 class="-title">Pourcentage de femmes</h2>
        <div class="-value"><?= $fProp ?>%</div>
    </div>
    <div class="-stat">
        <div class="-bg-equation has-math -wait-mathjax">$$ \frac{1}{n} \sum_{i=1}^n a_i $$</div>
        <h2 class="-title">Âge moyen</h2>
        <div class="-value"><?= $avgAge ?> ans</div>
    </div>
</div>
<h2 style="text-align: center;">Visiteurs</h2>
<div id="people">
    <?php foreach ($visitors as [$u, $v, $d]): ?>
        <div class="-person profile-card-container">
            <?php povProfileCard($u); ?>
            <div class="-details">
                <div class="-views"><span class="icon -inl">visibility</span> <?= $v["count"] ?></div>
                <div class="-last-visit">Dernière visite : <?= $d->format("d/m/Y \à H:i") ?></div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if (empty($visitors)): ?>
    <p id="no-visit">Personne n'a visité votre profil pour l'instant !
        Essayez de compléter votre profil avec plus d'informations afin que les autres membres puissent
        trouver des gens comme vous.
    </p>
<?php endif; ?>
