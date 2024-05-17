let promise = Promise.resolve();  // Used to hold chain of typesetting calls

// Prend une fonction qui retourne une liste d'éléments
export function typeset(code) {
    promise = promise.then(() => MathJax.typesetPromise(code()))
        .catch((err) => console.log('Typeset failed: ' + err.message));
    return promise;
}

window.math = { typeset };