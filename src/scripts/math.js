let promise = window.mathJaxReady;  // Used to hold chain of typesetting calls
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

window.math = { typeset };