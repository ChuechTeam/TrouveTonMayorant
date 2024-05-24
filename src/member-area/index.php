<?php
require_once "_common.php";
Templates\member("Accueil");
Templates\addStylesheet("/assets/style/search-page.css");
Templates\appendParam("head", '<script src="/scripts/location.js" type="module" defer></script>');

require_once "../modules/viewDB.php";
?>

<h1 class="title"><?php echo "Bonyour, ".$user["firstName"]." ".$user["lastName"]." !" ?></h1>

<search id="search" >
    <div id="fields">
        <form id="search-form">
            <ul class="field">
                <li><input type="checkbox" name="gender[]" value="f" ><label>Femme</label></li>
                <li><input type="checkbox" name="gender[]" value="m"><label for="h">Homme</label></li>
                <li><input type="checkbox" name="gender[]" value="nb"><label for="nb">Non-binaire</label></li>
            </ul>
            <ul class="field">
                <li><input type="checkbox" name="smoker" value="yes"><label>Fumeur</label></li>
            </ul>
            <ul class="field location">
              <select id="departmentSelect" name="dep">
                <option disabled selected value> -- Département -- </option>
              </select>
              <select id="citySelect" class="d-none" name="city">
                <option disabled selected value> -- Ville -- </option>
              </select>
              <input type="hidden" name="depName" id="depNameInput" value="">
              <input type="hidden" name="cityName" id="cityNameInput" value="">
            </ul>
            <ul class="field relationship">
                <li><input type="checkbox" name="rel_search[]" id="ro" value="ro"><label for="ro">Rencontres occasionnelles</label></li>
                <li><input type="checkbox" name="rel_search[]" id="rs" value="rs"><label for="rs">Relation sérieuse</label></li>
                <li><input type="checkbox" name="rel_search[]" id="rl" value="rl"><label for="rl">Relation sans lendemain</label></li>
                <li><input type="checkbox" name="rel_search[]" id="ad" value="ad"><label for="ad">À découvrir au fil des échanges</label></li>
                <li><input type="checkbox" name="rel_search[]" id="rne" value="rne"><label for="rne">Relation non exclusive</label></li>
            </ul>

            <div style="display:flex; justify-content:center; align-items:center;">Âge</div>
            <div class="wrapper">

              <div class="container">
                <div class="slider-track"></div>
                <input type="range" min="18" max="100" value="<?php echo (User\age($user["id"]) - 5) > 18 ? User\age($user["id"]) - 5 : 18 ?>" id="slider-1" oninput="slide()">
                <input type="range" min="18" max="100" value="<?php echo (User\age($user["id"]) + 5) < 100 ? User\age($user["id"]) + 5 : 100 ?>" id="slider-2" oninput="slide()">
              </div>
              
              <div class="values">
                <span id="range1">
                  20
                </span>
                <span> &dash; </span>
                <span id="range2">
                  30
                </span>
              </div>

            </div>
        </form>
        <div class="buttons">
          <button form="search-form" class="sub" type="reset" onclick="slider_reset()">Réinitialiser</button>
          <button class="sub" id="search-entry" onclick="loadresults()">Recherche</button>
        </div>
    </div>
    
    <output id="results">

    </output>
</search>

<script>
    function loadresults(){
        var xhttp;
        xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("results").innerHTML = this.responseText ;
            }
        };
        // window.location.origin = http://localhost:8080 (for exemple)
        const endpoint = new URL("research.php", window.location.origin);
        // register a_min and a_max fields using the min/max range values
        endpoint.searchParams.append("a_min",document.getElementById("range1").innerText);
        endpoint.searchParams.append("a_max",document.getElementById("range2").innerText);
        const sp = new URLSearchParams(new FormData(document.getElementById("search-form")));
        for (const [key, value] of sp) {
            endpoint.searchParams.append(key, value); 
            // transfer all search parameters from sp to the endpoint : g=gender[]&smoker=0 etc...
        }
        

        xhttp.open("GET", endpoint, true);
        xhttp.send();
    }
    window.onload = function () {
      slide();
    };

let sliderOne = document.getElementById("slider-1");
let sliderTwo = document.getElementById("slider-2");
let displayValOne = document.getElementById("range1");
let displayValTwo = document.getElementById("range2");
let minGap = 0;
let sliderTrack = document.querySelector(".slider-track");
let sliderMaxValue = document.getElementById("slider-1").max - document.getElementById("slider-1").min ;

function slide() {
  // Make sure we display the smallest value on the left, and the biggest on the right
  if (parseInt(sliderTwo.value) - parseInt(sliderOne.value) <= minGap) {
    displayValOne.textContent = sliderTwo.value;
    displayValTwo.textContent = sliderOne.value;
  }
  else{
    displayValOne.textContent = sliderOne.value;
    displayValTwo.textContent = sliderTwo.value;
  }
  fillColor();
}
function fillColor() {
  const v1 = Number(sliderOne.value) -18;
  const v2 = Number(sliderTwo.value) -18;
  const percent1 = ((v1 >= v2 ? v2 : v1) / sliderMaxValue) * 100;
  const percent2 = ((v1 <= v2 ? v2 : v1) / sliderMaxValue) * 100;
  sliderTrack.style.background = `linear-gradient(to right, #dadae5 ${percent1}% , #3264fe ${percent1}% , #3264fe ${percent2}%, #dadae5 ${percent2}%)`;
}

function slider_reset(){
  const v1 = <?php echo (User\age($user["id"]) - 5) > 18 ? User\age($user["id"]) - 5 : 18 ?>;
  const v2 = <?php echo (User\age($user["id"]) + 5) < 100 ? User\age($user["id"]) + 5 : 100 ?>;
  const percent1 = (v1 -18 / sliderMaxValue) * 100;
  const percent2 = (v1 -18 / sliderMaxValue) * 100;
  document.getElementById("range1").innerHTML = v1;
  document.getElementById("range2").innerHTML = v2;
  sliderTrack.style.background = `linear-gradient(to right, #dadae5 ${percent1}% , #3264fe ${percent1}% , #3264fe ${percent2}%, #dadae5 ${percent2}%)`;
}
</script>


