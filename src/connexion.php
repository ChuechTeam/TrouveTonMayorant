<?php ob_start(); ?>

<h1>Bonyour !</h1>
<div class="login-form">
    <form action="testCo.php" method="post" id="Co">
    <table border="1" cellpading="20" cellspacing="0" >
        <tr>
            <td colspan="2" style="text-align: center;">Se connecter</td>
        </tr>
        <tr>
            <td>Email</td>
            <td><input type="mail" name="mail" id=""></td>
        </tr>
        <tr>
            <td>Password</td>
            <td><input type="password" name="password" id=""></td>
        </tr>
        <tr>
            <td colspan="2" style="text-align: center;"><button type="submit">Se connecter</button>
            <br>
            
            </td>
        </tr>
    </table>
    <p style="font-size: 11px">Pas encore de compte ? <input type="button" value="S'inscrire" onclick="HideShow(0)"></p>
    </form>

    
    <form action="testInsc.php" method="post" id="Ins">
    <table border="1" cellpading="20" cellspacing="0" >
        <tr>
            <td colspan="2" style="text-align: center;">S'inscrire</td>
        </tr>
        <tr>
            <td>Email</td>
            <td><input type="mail" name="mail" id=""></td>
        </tr>
        <tr>
            <td>Password</td>
            <td><input type="password" name="password" id=""></td>
        </tr>
        <tr>
            <td>Nom</td>
            <td><input type="text" name="name" id=""></td>
        </tr>
        <tr>
            <td>Prénom</td>
            <td><input type="text" name="fname" id=""></td>
        </tr>
        <tr>
            <td>Age</td>
            <td><input type="number" name="age" id="" min="18" max="122"></td>
        </tr>

        <tr>
            <td colspan="2" style="text-align: center;"><button type="submit">S'inscrire</button>
            <br>
            
            </td>
        </tr>
    </table>
    <p style="font-size: 11px">Déjà un compte ? <input type="button" value="Connexion" onclick="HideShow(1)"></p>
    </form>
</div>

<script>
    function HideShow(i) {
  if(!i){
    var x = document.getElementById("Co");
    x.style.display = "none";
    var y = document.getElementById("Ins");
    y.style.display = "block";
  }
  else{
    var x = document.getElementById("Ins");
    x.style.display = "none";
    var y = document.getElementById("Co");
    y.style.display = "block";
  }
} 
</script>



<?php $tmplContent = ob_get_clean(); include "templates/base.php"; ?>