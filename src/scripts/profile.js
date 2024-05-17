document.addEventListener("click", function(e) {
    const card = e.target.closest(".profile-card");
    if (card !== null) {
        e.preventDefault();
        const id = card.dataset.id;
        window.location.href = new URL("member-area/userProfile.php?id=" + id, window.location.origin);
    }
});

document.getElementById("block-btn")?.addEventListener("click", function(e) {
    if (!confirm("Voulez-vous vraiment bloquer cet utilisateur ?")) {
        e.preventDefault();
    }
});