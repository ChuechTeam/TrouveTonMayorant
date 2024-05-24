<?php

require_once "_common.php";

Templates\member("Bannissements");

if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($_POST["email"])) {
    ModerationDB\unbanEmail($_POST["email"]);
}

$emails = ModerationDB\queryBannedEmails();
?>

<h1>Liste des bannissements</h1>

<?php if (!empty($emails)): ?>
    <ul id="emails">
        <?php foreach ($emails as $e): ?>
            <li><?= htmlspecialchars($e) ?> 
            <form method="post">
                <input type="hidden" name="email" value="<?= htmlspecialchars($e) ?>">
                <button>Débannir</button>
            </form></li>
        <?php endforeach; ?>
    </ul>
<?php else: ?>
    <p>Aucun e-mail n'est banni pour l'instant.</p>
<?php endif; ?>