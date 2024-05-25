<?php

require "_common.php";
Templates\member("Boutique");
Templates\addStylesheet("/assets/style/shop-page.css");
Templates\appendParam("head", '<script src="/scripts/shop.js" type="module" defer></script>');

$offers = [
    [
        "duration" => new DateInterval("P1M"),
        "durationStr" => "1 mois",
        "price" => "10€",
    ],
    [
        "duration" => new DateInterval("P3M"),
        "durationStr" => "3 mois",
        "price" => "25€",
    ],
    [
        "duration" => new DateInterval("P6M"),
        "durationStr" => "6 mois",
        "price" => "45€",
    ],
    [
        "duration" => new DateInterval("P1Y"),
        "durationStr" => "1 an",
        "price" => "80€",
    ]
];

$errorStr = null;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $idx = $_POST["offer"] ?? "-1";
    if (is_numeric($idx) && (($idx = intval($idx)) >= 0 && $idx < count($offers))) {
        $offer = $offers[$idx];

        $ok = User\subscribe($user["id"], $offer["duration"], $user);
        if ($ok === 0) {
            $_SESSION["purchased"] = true;
            header("Location: /member-area/shop.php");
            exit();
        } else {
            $errorStr = User\errToString($ok);
        }
    } else {
        $errorStr = "Offre invalide";
    }
}

$level = \UserSession\level();
$expDate = $user["supExpire"] === null ? null : DateTime::createFromFormat(\User\SUP_DATE_FMT, $user["supExpire"]);
if ($expDate === false) {
    trigger_error("Invalid supExpire date format: " . $user["supExpire"], E_USER_WARNING);
    $expDate = null;
}
$supBought = $user["supBought"] === null ? null : DateTime::createFromFormat(\User\SUP_DATE_FMT, $user["supBought"]);
if ($supBought === false) {
    trigger_error("Invalid supBought date format: " . $user["supBought"], E_USER_WARNING);
    $supBought = null;
}
$subscribed = $level >= User\LEVEL_SUBSCRIBER;
$subCardClass = $subscribed ? "-subscribed" : "-unsubscribed";

if ($subscribed) {
    $randNum = "";
    srand($user["id"]);
    for ($i = 0; $i < 16; $i++) {
        if ($i !== 0 && ($i % 4) == 0) {
            $randNum .= "-";
        }
        $randNum .= rand(1, 9);
    }
}

$purchased = isset($_SESSION["purchased"]);
unset($_SESSION["purchased"]);
?>

<h1 class="title">Boutique</h1>
<?php if ($errorStr !== null): ?> <p id="err"><?= htmlspecialchars($errorStr) ?></p> <?php endif; ?>
<h2>Statut actuel</h2>
<div id="page-sub" class="subscription-card <?= $subCardClass ?>">
    <?php if ($subscribed): ?>
        <div class="-main">
            <img class="-sup-logo" src="/assets/sup.svg">
            <?php if ($expDate !== null): ?>
                <div class="-status">Abonné jusqu'au <?= $expDate->format("d/m/Y") ?></div>
            <?php else: ?>
                <div class="-status">Abonné pour toujours</div>
            <?php endif; ?>
        </div>
        <div class="-details">
            <h3>Abonnement décerné à</h3>
            <div class="-subscriber"><?= htmlspecialchars($user["firstName"] . ' ' . $user["lastName"]) ?></div>
            <?php if ($supBought !== null): ?>
                <h3>Membre depuis le</h3>
                <div class="-bought"><?= $supBought->format("d/m/Y") ?></div>
            <?php endif; ?>
            <div class="-random-numbers"><?= $randNum ?></div>
        </div>
    <?php else: ?>
        <div class="-side icon">close</div>
        <div class="-main">
            <h3>Non abonné</h3>
            <p>Abonnez-vous pour accéder à toutes les fonctionnalités de TTM !</p>
        </div>
    <?php endif; ?>
</div>

<h2>Offres</h2>
<div id="offers">
    <?php foreach ($offers as $i => $o): ?>
        <div class="shop-offer">
            <img class="-sup-logo" src="/assets/sup.svg">
            <div class="-duration"><?= $o["durationStr"] ?></div>
            <div class="-price"><?= $o["price"] ?></div>
            <form method="post" class="offer-form"
                  data-price="<?= htmlspecialchars($o["price"]) ?>"
                  data-duration="<?= htmlspecialchars($o["durationStr"]) ?>">
                <input type="hidden" name="offer" value="<?= $i ?>">
                <button class="-buy">Acheter</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($purchased): ?>
    <div id="purchase-complete">
        <div>
            Achat effectué ! Vous êtes maintenant abonné à TTM <img src="/assets/sup.svg" class="-sup"> !
        </div>
    </div>
<?php endif; ?>