/* =================================== SCRIPT SEPARATOR-1 =================================== */
// Cut words in our favorite cards to avoid long text
document.addEventListener("DOMContentLoaded", function () {
    const productLinks = document.querySelectorAll(
        ".alg-wc-wl-view-table-container .alg-wc-wl-view-table tbody tr .product-name a"
    );

    productLinks.forEach(link => {
        const fullText = link.textContent.trim();
        const words = fullText.split(/\s+/);
        if (words.length > 5) {
            const shortText = words.slice(0, 5).join(" ") + "…";
            link.textContent = shortText;
        }
    });
});

/* =================================== SCRIPT SEPARATOR-2 =================================== */
// Add image tag and delete default Add to cart
function replaceAddToCartTextWithImage(link) {
    // Check if img set
    if (link.querySelector("img")) return;

    const img = document.createElement("img");
        img.src = my_favorite_icon.cart_icon;
        img.alt = "Add to cart";
        img.style.width = "24px";
        img.style.height = "24px";

    link.textContent = "";
    link.appendChild(img);
}

// Wait while DOM loaded before change html
document.addEventListener("DOMContentLoaded", function () {
    const selector = ".add_to_cart_button";

    // Create CSS selector for buttons
    document.querySelectorAll(selector).forEach(replaceAddToCartTextWithImage);

    // See DOM changes
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // ELEMENT_NODE
                    if (node.matches(selector)) {
                        replaceAddToCartTextWithImage(node);
                    } else {
                        node.querySelectorAll?.(selector).forEach(replaceAddToCartTextWithImage);
                    }
                }
            });
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true,
    });
});

/* =================================== SCRIPT SEPARATOR-3 =================================== */
// function that checks for link in the See your wishlist popup
function waitForWishlistPopupLink() {
	let attempts = 0;
	const maxAttempts = 30;

	const interval = setInterval(() => {
		const link = document.querySelector('a.alg-wc-wl-notification-link');

		if (link) {
			link.href = '/my-account/my-wish-list/';
			console.log("Link changed to /my-account/my-wish-list/");
			clearInterval(interval);
		}

		attempts++;
		if (attempts > maxAttempts) {
			clearInterval(interval);
			console.warn("Link not found");
		}
	}, 300); // check every 300ms
}

// get click on heart(Add to wishlist)
document.addEventListener("click", function (e) {
	if (e.target.closest(".alg-wc-wl-btn")) {
		setTimeout(waitForWishlistPopupLink, 500); // wait popup
	}
});