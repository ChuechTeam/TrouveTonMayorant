/*
 * Step 1: add a cookie with "romu" inside the value or name
 * Step 2: refresh
 * Step 3: ROMUUUUU
 */
let chaosInterval = null;

function romu(definitive = true) {
    const e = document.createElement("div");
    e.innerText = "ROMU!!!!";
    e.style.fontSize = "5.2em";
    e.style.fontWeight = "bold";
    e.style.textAlign = "center";
    document.body.appendChild(e);

    document.documentElement.style.background = "linear-gradient(180deg, rgba(171, 171, 255, 1), white 33%, rgba(255, 172, 172, 1))";
    document.documentElement.style.backgroundAttachment = "fixed";

    if (definitive) {
        localStorage.setItem("romu", "true");
    }

    if (!chaosInterval) {
        chaosInterval = setInterval(() => {
            const rnd1 = Math.random() * 20 - 10;
            const rnd2 = Math.random() * 20 - 10;
            e.style.transform = `translate(${rnd1}px, ${rnd2}px)`;
        }, 10);
    }
}

function unromu() {
    localStorage.removeItem("romu");
    if (document.documentElement.style.background) {
        document.documentElement.style.background = "";
        document.body.removeChild(document.body.lastChild);
    }
    clearInterval(chaosInterval);
    chaosInterval = null;
}

if (document.cookie.toLocaleLowerCase().includes("romu")
    || localStorage.getItem("romu") != null) {
    romu(false);
}

