import {typeset} from "/scripts/math.js";

// Button to delete the account
document.getElementById("delete-account")?.addEventListener("click", function (e) {
    if (document.getElementById("pass-input").value == "") {
        e.preventDefault();
        alert("Veuillez entrer votre mot de passe pour supprimer votre compte.");
        document.getElementById("pass-input").focus(); // user friendly ??
    } else {
        if (!confirm("Voulez vous vraiment supprimer votre compte ?")) {
            e.preventDefault();
        }
    }
});

// Function to get a preview of the uploaded image
window.loadFile = function loadFile(id) {
    var preview = document.getElementById(id);
    preview.src = URL.createObjectURL(event.target.files[0]);
    preview.onload = function () {
        URL.revokeObjectURL(preview.src) // free memory
    }
}

// Update the MathJax equation when editing the preferred equation
const eq = document.getElementById("eq");
document.getElementById("eq-input").addEventListener("input", e => {
    typeset(() => {
        // Escape dollar signs, the server will escape them when submitting
        const escaped = e.target.value.replaceAll(/(?<!\\)\$/g, '\\$');
        eq.textContent = "$$" + escaped + "$$";
        return [eq];
    })
})

const depSelect = document.getElementById("departmentSelect");
const depInput = document.getElementById("depNameInput");
const citySelect = document.getElementById("citySelect");
const cityInput = document.getElementById("cityNameInput");

// Update the depName and cityName hidden inputs when the user selects a department and a city,
// so we don't just put the zip code.
function regNameUpdate(dropdown, input) {
    dropdown.addEventListener("change", e => {
        const opt = dropdown.options[dropdown.selectedIndex];
        const val = opt.dataset.publicVal ?? "";
        input.value = val;

        // Empty the city input if the department is changed
        if (dropdown == depSelect) {
            cityInput.value = "";
        }
    })
}

regNameUpdate(depSelect, depInput);
regNameUpdate(citySelect, cityInput);