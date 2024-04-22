<?php ob_start(); ?>

<h1>Bonyour !</h1>
<p><?php print("Ici le php !!") ?></p>

<?php $tmplContent = ob_get_clean(); include "templates/base.php"; ?>
