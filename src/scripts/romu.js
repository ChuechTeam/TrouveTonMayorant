/**
 * Step 1: add a cookie with "romu" inside the value or name
 * Step 2: refresh
 * Step 3: ROMUUUUU
 */
if (document.cookie.toLocaleLowerCase().includes("romu")) {
    const e = document.createElement("div");
    e.innerText = "ROMU!!!!";
    document.body.appendChild(e);
}