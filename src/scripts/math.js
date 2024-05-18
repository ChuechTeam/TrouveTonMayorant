let readyResolve
const mjReadyResolve = new Promise((resolve) => { readyResolve = resolve; });
let promise = mjReadyResolve;  // Used to hold chain of typesetting calls

// Prend une fonction qui retourne une liste d'éléments OU une liste directement
export function typeset(code) {
    promise = promise.then(() => MathJax.typesetPromise(typeof code === 'function' ? code() : code))
        .catch((err) => console.log('Typeset failed: ' + err.message));
    return promise;
}

// S'assurer que MathJax est prêt, ou réagir lorsqu'il est chargé
if (window.MathJax != null && "typesetPromise" in window.MathJax) {
    readyResolve();
}
else {
    const mj = window.MathJax ?? (window.MathJax = {});
    const startup = MathJax.startup ?? (MathJax.startup = {});
    startup.ready = function () {
        MathJax.startup.defaultReady();
        MathJax.startup.promise.then(readyResolve);
    };
}

window.math = { typeset };