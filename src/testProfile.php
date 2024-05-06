<?php 
ob_start(); 
require "modules/user.php";
require "modules/userSession.php";
require "modules/url.php";
$u = \UserSession\loggedUser();
    if(!empty(($_POST['mail'])) && !empty(($_POST['name'])) && !empty(($_POST['fname'])) && !empty(($_POST['age']))) { //Si les champs ne sont pas vides

        $user_m = $_POST['mail'];
        $user_n = $_POST['name'];
        $user_fn = $_POST['fname'];
        $user_a = $_POST['age'];

        $user_p = (empty(($_POST['password']))) ? $u['pass'] : password_hash($_POST['password'], PASSWORD_DEFAULT);

        if ($u !== null){
            $ok = User\validate(array(
                "firstName" => $user_fn, 
                "lastName" => $user_n, 
                "email" => $user_m, 
                "age" => $user_a
            ), $u['id']);

            if($ok !== 0 ){
                setcookie("erreur", $ok);
                header("Location: $root/profile.php");
                exit();
            }
            
            $u["id"] = UserDB\put(array(
                "firstName" => $user_fn, 
                "lastName" => $user_n, 
                "pass" => $user_p,
                "email" => $user_m, 
                "age" => $user_a,
                "id" => $u['id']));
        }
        

        header("Location: $root/profile.php");
        exit();
    }
    else{
        setcookie("erreur", UE_FIELD_MISSING);
        header("Location: $root/profile.php");
        exit();
    }
?>