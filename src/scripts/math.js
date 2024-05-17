let promise = Promise.resolve();  // Used to hold chain of typesetting calls

// Prend une fonction qui retourne une liste d'éléments OU une liste directement
export function typeset(code) {
    // S'assurer que MathJax existe (pas d'oubli de script)
    if (!("MathJax" in window)) {
        throw new Error("MathJax is not loaded!");
    }
    // Lancer le rendu après l'initialisation de MathJax s'il n'est pas encore complètement chargé
    if (!("typesetPromise" in MathJax)) {
        promise = MathJax.startup.promise;
    }
    promise = promise.then(() => MathJax.typesetPromise(typeof code === 'function' ? code() : code))
        .catch((err) => console.log('Typeset failed: ' + err.message));
    return promise;
}

window.math = { typeset };