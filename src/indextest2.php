<?php ob_start(); ?>

<div class="box" id="theBox">
    <header id="theBoxHeader">Truc truc</header>
    <div class="-contents">
    <ul>
        <li>a</li>
        <li>b</li>
            <li>s</li>
        </ul>
    </div>
    <div class="-bg" id="theBoxBg"></div>
</div>

<div style="background-color: blue;"></div>

<style>
    .box {
        position: relative;
    }
    .box > .-bg {
        position: absolute;
        left: 0;
        right: 0;
        top: 0;
        background-color: green;
        z-index: -1;
        height: 100%;
        
        transition: height 0.3s ease;
    }
    .box .-contents { display: none; }
    .box.-open .-contents { display: block; }
</style>
<script>
    const tb = document.getElementById("theBox");
    const tbh = document.getElementById("theBoxHeader");
    const tbbg = document.getElementById("theBoxBg");

    function toggle() { 
        if (tb.classList.contains("-open")) {
            tb.classList.remove("-open");
        } else {
            tb.classList.add("-open");
        }

        tbbg.style.height = tb.clientHeight + "px";
    }

    tbbg.style.height = tb.clientHeight + "px";
    tbh.addEventListener("click", toggle);
</script>
<?php $tmplContent = ob_get_clean(); include "templates/base.php"; ?>


