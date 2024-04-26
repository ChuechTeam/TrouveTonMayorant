<?php require "modules/userDB.php";
    session_start();

    if (!empty(($_POST['mail'])) && !empty(($_POST['password']))) {
        $user_m = $_POST['mail'];
        $user_p = $_POST['password'];
        $dom = $_SERVER["HTTP_HOST"];
        
        if(UserDB\findByEmailPassword($user_m, $user_p) != null){
            $_SESSION["loggedIn"] = 1;
            header("Location: http://$dom/index.php");
            exit();
        }
        else{
            setcookie("erreur", 2 | (1 << 9));
            header("Location: http://$dom/connexion.php");
            exit();
        }
    }
    else{
        $_SESSION["loggedIn"] = 0;
        $dom = $_SERVER["HTTP_HOST"];
        setcookie("erreur", 3 | (1 << 9));
        header("Location: http://$dom/connexion.php");
        exit();
    }
?>