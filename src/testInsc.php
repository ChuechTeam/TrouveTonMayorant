<?php require "modules/user.php";
require "modules/userSession.php";
require "modules/url.php";
    if(!empty(($_POST['mail'])) && !empty(($_POST['password'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['age']))) { //Si les champs ne sont pas vides
        //On recupere les infos importantes
        $user_m = $_POST['mail'];
        $dom = $_SERVER["HTTP_HOST"];

        //Check si le mail est deja utilise
        // if(UserDB\findByEmail($user_m) != null){
        //     setcookie("erreur", 1);
        //     header("Location: http://$dom/connexion.php");
        //     exit();
        // }

        $user_p = $_POST['password'];
        $user_n = $_POST['name'];
        $user_fn = $_POST['fname'];
        $user_a = $_POST['age'];

        $id = 0;
        $ok = User\register($user_fn, $user_n, $user_m, $user_p, $user_a, $id);
        if ($ok !== 0) {
            setcookie("erreur", $ok);
            header("Location: $root/connexion.php");
            exit();
        }

        // UserDB\put(array("email" =>$user_m, 
        // "pass"=>password_hash($user_p, PASSWORD_DEFAULT), 
        // "firstName"=>$user_fn, 
        // "lastName"=>$user_n));

        \UserSession\signIn($id);
        header("Location: http://$dom/index.php");
        exit();
    }
    else{
        $dom = $_SERVER["HTTP_HOST"];
        setcookie("erreur", 3);
        header("Location: http://$dom/connexion.php");
        exit();
    }
?>