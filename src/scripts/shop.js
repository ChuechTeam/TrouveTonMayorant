/*
 * This section of the code animates the shop card when -subscribed, to have
 * a TOTALLY OVERKILL effect when hovering it.
 */

const card = document.getElementById("page-sub");

// Calculates the cross product of two vectors
function crossProduct(x1, y1, z1, x2, y2, z2) {
    return {
        x: y1 * z2 - z1 * y2,
        y: z1 * x2 - x1 * z2,
        z: x1 * y2 - y1 * x2
    };
}

// Rectangular norm. Gives us a "percentage" of the furthest point from the center
function funkyNorm(x, y, w, h) {
    return Math.max(Math.abs(x / w), Math.abs(y / h));
}

// Only apply the effect if the card is for a subscribed user.
if (card.classList.contains("-subscribed")) {
    card.style.transform = "rotate3d(0, 0, 1, 0.01deg)";
    document.addEventListener("pointermove", e => {
        // Get the viewport-space mouse coordinates
        const mx = e.clientX
        const my = e.clientY

        // Get the viewport-space bounding box of the card
        const {x, y, width, height} = card.getBoundingClientRect()

        // Center of the card
        const cx = x + width / 2;
        const cy = y + height / 2;

        // Vector from the card --> mouse
        const nx = mx - cx
        const ny = my - cy

        // To create the right rotation axis for our effect,
        // we need it to be a vector perpendicular to the facing vector.
        // Thankfully, since we're in 2D space with (x, y), (0, 0, 1) is always perpendicular.
        const axis = crossProduct(0, 0, 1, nx, ny, 0)

        // Rotation amplitude in degrees, varying from 0 to 20 depending on the distance to the center.
        const amplitudeMin = 0;
        const amplitudeMax = 20;

        // Linear interpolation function (makes sure that the result is between a and b, even if t ∉ [0, 1])
        function lerp(a, b, t) {
            return Math.min(b, Math.max(a, a + (b - a) * t));
        }

        // Some "negative" margin applied to the hit box,
        // in order to make the effect begin before the mouse reaches the edge of the card.
        const margin = 0.4;

        // Calculate the greatest distance to the center of the card (x or y),
        // while applying the negative margin.
        const distToCenter = funkyNorm(nx, ny, width / (2 - margin), height / (2 - margin));
        if (distToCenter > 1) {
            // We're out of the card, reset everything to zero, BUT keep the card
            // veerrrry slightly rotated so we don't get a weird effect when the rotation becomes zero,
            // as it will render the icon in some sort of pixel-perfect way.
            // (Comment the line below to see what I mean)
            card.style.transform = "rotate3d(0, 0, 1, 0.01deg)";
            card.style.setProperty("--shadow-x", "0");
            card.style.setProperty("--shadow-y", "0");
            return;
        }

        // Calculate the distance to the *edge* of the card (greatest axis between x and y)
        // and make it reach 1 a bit earlier.
        // Apply the rotation to the card, with the rotation axis and amplitude.
        const smoothDist = Math.min(1, Math.pow(1 - distToCenter, 0.7) * 2);
        const amplitude = lerp(amplitudeMin, amplitudeMax, smoothDist);
        card.style.transform = `rotate3d(${-axis.x}, ${-axis.y}, ${axis.z}, ${amplitude}deg)`;

        // Normalize the card-to-mouse vector, so we get a vector of euclidean norm equal to 1.
        const normalizedNX = nx / Math.hypot(nx, ny);
        const normalizedNY = ny / Math.hypot(nx, ny);

        // Apply the shadow in the OPPOSITE direction of the card-to-mouse vector.
        // Vectors are cool so we just have to negate it to get the opposite direction.
        // Also scale the shadow distance according to the distance to the edge.
        const shadowAmplitude = 50;
        const shadowX = -smoothDist * shadowAmplitude * normalizedNX;
        const shadowY = -smoothDist * shadowAmplitude * normalizedNY;
        card.style.setProperty("--shadow-x", shadowX + "px");
        card.style.setProperty("--shadow-y", shadowY + "px");
    })

    document.addEventListener("touchend", e => {
        // Reset the card transforms when the touch has ended.
        card.style.transform = "rotate3d(0, 0, 1, 0.01deg)";
        card.style.setProperty("--shadow-x", "0");
        card.style.setProperty("--shadow-y", "0");
    })
}

document.addEventListener("submit", e => {
    if (e.target.classList.contains("offer-form")) {
        const form = e.target;
        const price = form.dataset.price;
        const duration = form.dataset.duration;
        if (!confirm(`Acheter l'offre TTM sup™ ${duration} pour ${price} ?
Votre carte bancaire sera débitée par magie.`)) {
            e.preventDefault();
        }
    }
})