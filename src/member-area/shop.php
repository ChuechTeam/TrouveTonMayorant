<?php

require "_common.php";
Templates\member("Boutique");
Templates\addStylesheet("/assets/style/shop-page.css");

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

<script>
    const ps = document.getElementById("page-sub");

    function crossProduct(x1, y1, z1, x2, y2, z2) {
        return {
            x: y1 * z2 - z1 * y2,
            y: z1 * x2 - x1 * z2,
            z: x1 * y2 - y1 * x2
        };
    }

    // Norme 3
    function funkyNorm(x, y, w, h) {
        return Math.max(Math.abs(x / w), Math.abs(y / h));
    }

    // c'est vraiment trop excessif, donc c'est bien
    if (ps.classList.contains("-subscribed")) {
        ps.style.transform = "rotate3d(0, 0, 1, 0.01deg)";
        document.addEventListener("pointermove", e => {
            const mx = e.clientX
            const my = e.clientY

            const {x, y, width, height} = ps.getBoundingClientRect()

            const px = x + width / 2;
            const py = y + height / 2;

            // élément --> souris
            const nx = mx - px
            const ny = my - py

            // (0, 0, 1) est toujours perpendiculaire
            const cp = crossProduct(0, 0, 1, nx, ny, 0)

            const amplitudeMin = 0;
            const amplitudeMax = 20;

            function lerp(a, b, t) {
                return Math.min(b, Math.max(a, a + (b - a) * t));
            }

            const margin = 0.4;

            const distToCenter = funkyNorm(nx, ny, width / (2 - margin), height / (2 - margin));
            if (distToCenter > 1) {
                ps.style.transform = "rotate3d(0, 0, 1, 0.01deg)";
                ps.style.setProperty("--shadow-x", "0");
                ps.style.setProperty("--shadow-y", "0");
                return;
            }

            const smoothDist = Math.min(1, Math.pow(1 - distToCenter, 0.7) * 2);
            const amplitude = lerp(amplitudeMin, amplitudeMax, smoothDist);
            ps.style.transform = `rotate3d(${-cp.x}, ${-cp.y}, ${cp.z}, ${amplitude}deg)`;

            const normalizedNX = nx / width;
            const normalizedNY = ny / height;

            const shadowAmplitude = 80;
            const shadowX = -smoothDist * shadowAmplitude * normalizedNX;
            const shadowY = -smoothDist * shadowAmplitude * normalizedNY;
            ps.style.setProperty("--shadow-x", shadowX + "px");
            ps.style.setProperty("--shadow-y", shadowY + "px");
        })

        document.addEventListener("touchend", e => {
            ps.style.transform = "rotate3d(0, 0, 1, 0.01deg)";
            ps.style.setProperty("--shadow-x", "0");
            ps.style.setProperty("--shadow-y", "0");
        })
    }

    document.addEventListener("submit", e => {
        if (e.target.classList.contains("offer-form")) {
            const form = e.target;
            const price = form.dataset.price;
            const duration = form.dataset.duration;
            if (!confirm(`Acheter l'offre TTM sup™ ${duration} pour ${price} ?
Votre carte bancaire sera débitée par magie.`)) {
                e.preventDefault();
            }
        }
    })
</script>