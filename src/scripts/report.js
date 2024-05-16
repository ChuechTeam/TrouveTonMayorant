import {api} from "./api.js";
import { fetchAndOpenConv } from "./chat.js";

document.addEventListener("click", e => {
    if (e.target.classList.contains("-close-report")) {
        const report = e.target.closest(".report");
        const reportId = report?.dataset.id;
        if (!reportId) {
            return;
        }

        if (!confirm('Voulez-vous vraiment clore ce signalement ?')) {
            return;
        }

        api.deleteReport(reportId)
            .then(() => {
                report.remove();
            })
            .catch(err => {
                console.error(err);
                alert('Ça n\'a pas marché...');
            });
    } else if (e.target.classList.contains("-see-conv")) {
        const convId = e.target.closest(".report")?.dataset.convId;
        if (!convId) {
            return;
        }

        fetchAndOpenConv(convId)
            .catch(err => {
                console.error(err);
                alert('Ça n\'a pas marché...');
            });
    }
})