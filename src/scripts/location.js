const departmentSelect = document.getElementById("departmentSelect");
const citySelect = document.getElementById("citySelect");
let previousDep = null;

// <select data-dep="machin" >
const userDep = departmentSelect.dataset.dep;
const userCity = citySelect.dataset.city;

const allowEmptyCity = citySelect.dataset.allowEmpty !== undefined;
const emptyCityLabel = citySelect.dataset.allowEmpty ? citySelect.dataset.allowEmpty : "[Aucune ville]";

const allowEmptyDep = departmentSelect.dataset.allowEmpty !== undefined;

// Fetch all departments from the JSON file
fetch("/assets/departments.json")
    .then((response) => response.json())
    .then((data) => {
        // Add options for the department dropdown menu with the departments from the JSON file
        data.forEach((item) => {
            const option = document.createElement("option");
            option.value = item.num_dep;
            option.textContent = option.value + " : " + item.dep_name;
            option.dataset.publicVal = item.dep_name;
            departmentSelect.appendChild(option);

            // Select the already selected option if the data-dep attribute is set
            if (userDep == option.value) {
                option.selected = true;
                previousDep = userDep;
                filterCities(userDep);
                citySelect.classList.remove("d-none");
            }
        });
    })
    .catch((error) => console.error("Error fetching JSON:", error));

let cityList = null;

/**
 * Fetches the list of cities from the JSON file.
 * The list is already sorted, with duplicates removed.
 * If the list has already been loaded before, it is returned directly.
 * @return {Promise<[string, string, string][]>} a promise that resolves to the list of cities with
 * the following format:
 * [
 *    ["postal_code", "department_number", "city_name"]...
 * ]
 */
async function getCityList() {
    if (cityList === null) {
        const response = await fetch("/assets/cities-light.json");
        cityList = await response.json();
    }
    return cityList;
}

// Filter cities by selected department
function filterCities(selectedDep) {
    if (allowEmptyCity) {
        const opt = document.createElement("option");
        opt.selected = true;
        opt.value = "";
        opt.textContent = emptyCityLabel;
        citySelect.replaceChildren(opt);
    } else {
        citySelect.innerHTML = "<option disabled selected value> -- Ville -- </option>";
    }

    // Empty department selected, hide the city dropdown menu
    if (!selectedDep) {
        citySelect.classList.add("d-none");
        return;
    }

    // Fetch the cities JSON file
    // Its structure is simply an array of arrays:
    // [
    //    ["postal_code", "department_number", "city_name"]...
    // ]
    getCityList()
        .then((data) => {
            // Populate the city dropdown menu with the cities from the JSON file
            for (const item of data) {
                let [cityPostalCode, cityDepartment, cityName] = item;
                if (cityDepartment === selectedDep) {
                    const option = document.createElement("option");
                    option.value = cityPostalCode;

                    // Capitalize the first letter of each word in the city name,
                    // because the original database is all lowercase
                    cityName = cityName.split(" ");
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
            }
        })
        .catch((error) => console.error("Error fetching JSON:", error));
}

// Displays the city dropdown menu when a department option is chosen
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
