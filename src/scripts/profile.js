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

var slide_id = 1;
function plusSlides(n) {
    showSlides(slide_id += n);
}

function currentSlide(n) {
    showSlides(slide_id = n);
}

function showSlides(n) {
    var i;
    var slides = document.querySelectorAll('.custom-slider');
    if (slides.length === 0) {
        return;
    }
    
    if(n > slides.length){
        slide_id = 1;
    }
    if(n < 1){
        slide_id = slides.length;
    }
    for(i = 0; i < slides.length; i++){
        slides[i].style.display = "none";
    }

    slides[slide_id-1].style.display = "block";
}

showSlides(slide_id);

window.plusSlides = plusSlides;
window.currentSlide = currentSlide;
window.showSlides = showSlides;