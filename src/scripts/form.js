/**
 * When the shown page originates from a POST request,
 * prevents the form from re-submitting when refreshing the page,
 * and corrects the browser history to prevent duplicate entries (get then post)
 */
export function preventPostRefresh() {
    // Taken from
    // https://stackoverflow.com/questions/6320113/how-to-prevent-form-resubmission-when-page-is-refreshed-f5-ctrlr/45656609#45656609
    if (window.history.replaceState) {
        window.history.replaceState( null, null, window.location.href );
    }
}

// When true, prevents post refresh on all pages.
const defaultBehavior = true;
if ((!("disablePostRefresh" in window) && defaultBehavior) ||
    window.disablePostRefresh === true) {
    preventPostRefresh();
}