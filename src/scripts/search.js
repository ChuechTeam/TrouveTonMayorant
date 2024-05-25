const searchForm = document.getElementById("search-form");
const searchButton = document.getElementById("search-button");
const sliderOne = document.getElementById("slider-1");
const sliderTwo = document.getElementById("slider-2");
const displayValOne = document.getElementById("range1");
const displayValTwo = document.getElementById("range2");
const results = document.getElementById("results");
const sliderTrack = document.querySelector(".slider-track");
const sliderMaxValue = document.getElementById("slider-1").max - document.getElementById("slider-1").min;

async function loadResults() {
    // window.location.origin = http://localhost:8080 (for exemple)
    const endpoint = new URL("research.php", window.location.origin);
    // register a_min and a_max fields using the min/max range values
    endpoint.searchParams.append("a_min", displayValOne.innerText);
    endpoint.searchParams.append("a_max", displayValTwo.innerText);
    const sp = new URLSearchParams(new FormData(searchForm));
    for (const [key, value] of sp) {
        endpoint.searchParams.append(key, value);
        // transfer all search parameters from sp to the endpoint : g=gender[]&smoker=0 etc...
    }

    // Query the server and display the results into the "results" element
    await fetch(endpoint)
        .then(response => response.text())
        .then(data => {
            results.innerHTML = data;
        })
        .catch(error => {
            console.error("Error:", error);
        });
}

function slide() {
    // Make sure we display the smallest value on the left, and the biggest on the right
    if (parseInt(sliderTwo.value) > parseInt(sliderOne.value)) {
        displayValOne.textContent = sliderOne.value;
        displayValTwo.textContent = sliderTwo.value;
    } else {
        displayValOne.textContent = sliderTwo.value;
        displayValTwo.textContent = sliderOne.value;
    }
    fillColor();
}

// Updates the slider background to match the selected range
function fillColor() {
    const v1 = Number(sliderOne.value) - 18;
    const v2 = Number(sliderTwo.value) - 18;
    const percent1 = ((v1 >= v2 ? v2 : v1) / sliderMaxValue) * 100;
    const percent2 = ((v1 <= v2 ? v2 : v1) / sliderMaxValue) * 100;
    sliderTrack.style.background = `linear-gradient(to right, #dadae5 ${percent1}% , #3264fe ${percent1}% , #3264fe ${percent2}%, #dadae5 ${percent2}%)`;
}

for (let btn of document.querySelectorAll(".field-header .-reset")) {
    // Reset the fields when clicking on a reset button.
    btn.addEventListener("click", function () {
        const toReset = btn.dataset.reset;
        if (toReset) {
            for (let input of searchForm.querySelectorAll(`input[name="${toReset}"]`)) {
                input.checked = false;
            }
        } else if (btn.dataset.resetDep !== undefined) {
            document.getElementById("departmentSelect").value = "";
            document.getElementById("departmentSelect").dataset.chosen = "";
            document.getElementById("citySelect").value = "";
            document.getElementById("citySelect").classList.add("d-none");
        }
    });
}

searchButton.addEventListener("click", loadResults);
sliderOne.addEventListener("input", slide);
sliderTwo.addEventListener("input", slide);

slide();