<?php

    if(!empty(($_POST['mail'])) && !empty(($_POST['password']))) {
        $user_m = $_POST['mail'];
        $user_p = $_POST['password'];
        $user_n = $_POST['name'];
        $user_fn = $_POST['fname'];
        $user_a = $_POST['age'];

        header('Location: http://10.40.49.79:8080/index.php');
        exit();
    }
    else{
        header('Location: http://10.40.49.79:8080/connexion.php');
        exit();
    }
?>