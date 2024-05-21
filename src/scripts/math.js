let readyResolve
const mjReadyResolve = new Promise((resolve) => { readyResolve = resolve; });
let promise = mjReadyResolve;  // Used to hold chain of typesetting calls

/**
 * Schedules the given elements, or function returning an element array, to be typeset with MathJax.
 * @param {HTMLElement[]|function} code
 * @return {Promise<any>} a promise completed when the elements are typeset (you can ignore it)
 */
export function typeset(code) {
    promise = promise.then(() => MathJax.typesetPromise(typeof code === 'function' ? code() : code))
        .catch((err) => console.log('Typeset failed: ' + err.message));
    return promise;
}

// Make sure that MathJax is loaded properly, else, add a config entry to load it
if (window.MathJax != null && "typesetPromise" in window.MathJax) {
    readyResolve();
}
else {
    const mj = window.MathJax ?? (window.MathJax = {});
    const startup = mj.startup ?? (mj.startup = {});
    startup.ready = function () {
        MathJax.startup.defaultReady();
        MathJax.startup.promise.then(readyResolve);
    };
}

window.math = { typeset };