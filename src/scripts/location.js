const departmentSelect = document.getElementById("departmentSelect");
const citySelect = document.getElementById("citySelect");
let previousDep = null;

// <select data-dep="machin" >
const userDep = departmentSelect.dataset.dep;
const userCity = citySelect.dataset.city;

// Récupère les données du fichier JSON
fetch("/member-area/departements-region.json")
  .then((response) => response.json())
  .then((data) => {
    // Ajoute des options pour le menu déroulant des départements avec les départements du fichier JSON
    data.forEach((item) => {
      const option = document.createElement("option");
      option.value = item.num_dep;
      option.textContent = option.value +" : "+item.dep_name;
      option.dataset.publicVal = item.dep_name;
      departmentSelect.appendChild(option);
      if (userDep == option.value) {
        option.selected = true;
        previousDep = userDep;
        filterCities(userDep);
        citySelect.classList.remove("d-none");
      }
    });
  })
  .catch((error) => console.error("Error fetching JSON:", error));

// Filtre les villes selon le département sélectionné
function filterCities(selectedDep) {
  citySelect.innerHTML = "<option disabled selected value> -- Ville -- </option>";

  // Récupère les données du fichier JSON
  fetch("/member-area/cities.json")
    .then((response) => response.json())
    .then((data) => {
      let dataCities = Array.from(new Set(data.cities.map(JSON.stringify)))
                            .map(JSON.parse)
                            .sort((a, b) => a.city_code.localeCompare(b.city_code)); //nécessaire pour retirer les dupliqués (parce que le json officiel du gouvernement il est nul)
      dataCities.forEach((item) => {
        if (item.department_number === selectedDep) {
          const option = document.createElement("option");
          option.value = item.insee_code;
          var cityName = item.city_code.split(" ");
          for (let i = 0; i < cityName.length; i++) {
            cityName[i] = cityName[i][0].toUpperCase() + cityName[i].substr(1);
          }

          cityName = cityName.join(" ");
          option.dataset.publicVal = cityName;
          option.textContent = cityName;
          citySelect.appendChild(option);
          if (userCity == option.value) {
            option.selected = true;
          }
        }
      });
    })
    .catch((error) => console.error("Error fetching JSON:", error));
}

// Affiche le menu déroulant des villes lorsqu'une option de département est choisie
departmentSelect.addEventListener("change", function () {
  const selectedDep = this.value;
  if (selectedDep !== previousDep) {
    filterCities(selectedDep);
    previousDep = selectedDep;
  }
  if (selectedDep) {
    citySelect.classList.remove("d-none");
  }
});
