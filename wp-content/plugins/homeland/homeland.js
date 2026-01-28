document.addEventListener('DOMContentLoaded', function () {
    // --- DATA & STATE ---
    if (typeof homeland_carousel_data === 'undefined' || !homeland_carousel_data.slides || homeland_carousel_data.slides.length === 0) {
        return;
    }

    const products = homeland_carousel_data.slides;

    const G = {
        showcase: document.getElementById('productShowcase'),
        navArrows: document.querySelectorAll('.nav-arrow'),

        // Carousel State
        currentIndex: 0,

        // Animation Configuration
        ANIM_DURATION: 0.5,
        EASE: 'power2.out',

        // Layout Parameters
        CARD_WIDTH: 290,
        GAP: 16,
        SCALES: { large: 1.0, medium: 0.8, small: 0.577 },
        Z_INDICES: { large: 3, medium: 2, small: 1, exit: 0 },
        POSITIONS: []
    };

    // --- LOGIC ---

    function calculatePositions() {
        const centerIndex = 2;
        G.POSITIONS = [];
        G.POSITIONS[centerIndex] = 0;

        let currentPos = 0;
        let lastWidth = G.CARD_WIDTH * G.SCALES.large;

        // Center to Right
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.medium / 2);
        G.POSITIONS[centerIndex + 1] = currentPos;

        lastWidth = G.CARD_WIDTH * G.SCALES.medium;
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.small / 2);
        G.POSITIONS[centerIndex + 2] = currentPos;

        // Mirror to Left
        G.POSITIONS[centerIndex - 1] = -G.POSITIONS[centerIndex + 1];
        G.POSITIONS[centerIndex - 2] = -G.POSITIONS[centerIndex + 2];
    }

    function getWrappedIndex(index) {
        const len = products.length;
        return ((index % len) + len) % len;
    }

    const arrowSVG = `<svg class="arrow-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 6H10M10 6L6 2M10 6L6 10" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

    function createCardElement(productIndex, slotIndex, startPos = null) {
        const product = products[productIndex];
        const hasLink = product.link && product.link !== '#';
        const cardTag = hasLink ? 'a' : 'div';

        const wrapper = document.createElement(cardTag);
        if (hasLink) wrapper.href = product.link;
        wrapper.className = 'card-wrapper';
        wrapper.dataset.index = productIndex;

        wrapper.innerHTML = `
            <div class="card">
                <img src="${product.image}" alt="${product.name}" class="product-image">
                <div class="blue-gradient-circle"></div>
                <div class="card-text-wrapper">
                    <span>${product.name}</span>
                    <button class="arrow-button">${arrowSVG}</button>
                </div>
            </div>
        `;

        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];
        const size = sizePattern[slotIndex];

        gsap.set(wrapper, {
            position: 'absolute',
            left: '50%',
            x: startPos !== null ? startPos : G.POSITIONS[slotIndex],
            xPercent: -50,
            scale: G.SCALES[size],
            zIndex: G.Z_INDICES[size],
            opacity: startPos !== null ? 0 : 1,
            transformOrigin: 'bottom center'
        });

        return wrapper;
    }

    /**
     * The core "Continuous" update function.
     * Instead of clearing the DOM, it reconciles the existing cards with the new state.
     */
    function syncCarousel(direction = 0) {
        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];
        const targetIndices = [];

        // Calculate the indices of the 5 products that SHOULD be visible
        for (let i = -2; i <= 2; i++) {
            targetIndices.push(getWrappedIndex(G.currentIndex + i));
        }

        const currentCardWrappers = Array.from(G.showcase.children);
        const keptCards = new Set();

        // 1. Update/Move existing cards
        targetIndices.forEach((productIdx, slotIdx) => {
            let card = currentCardWrappers.find(c => parseInt(c.dataset.index) === productIdx && !keptCards.has(c));

            if (card) {
                // Card exists, animate to its new slot
                const size = sizePattern[slotIdx];
                gsap.to(card, {
                    x: G.POSITIONS[slotIdx],
                    scale: G.SCALES[size],
                    zIndex: G.Z_INDICES[size],
                    opacity: 1,
                    duration: G.ANIM_DURATION,
                    ease: G.EASE,
                    overwrite: true
                });
                keptCards.add(card);
            } else {
                // Card doesn't exist, create it
                // If we know the direction, we can spawn it outside the viewport
                let startPos = null;
                if (direction !== 0) {
                    const entranceSlot = direction === 1 ? 4 : 0;
                    startPos = G.POSITIONS[entranceSlot] + (direction * (G.CARD_WIDTH * 0.5 + G.GAP));
                }

                const newCard = createCardElement(productIdx, slotIdx, startPos);
                G.showcase.appendChild(newCard);

                const size = sizePattern[slotIdx];
                gsap.to(newCard, {
                    x: G.POSITIONS[slotIdx],
                    opacity: 1,
                    duration: G.ANIM_DURATION,
                    ease: G.EASE
                });
                keptCards.add(newCard);
            }
        });

        // 2. Remove old cards
        currentCardWrappers.forEach(card => {
            if (!keptCards.has(card)) {
                const isExitingLeft = parseInt(card.style.left) < 50; // Simple heuristic
                const exitX = gsap.getProperty(card, "x") - (direction * (G.CARD_WIDTH * 0.5 + G.GAP));

                gsap.to(card, {
                    x: exitX,
                    opacity: 0,
                    scale: 0.4,
                    duration: G.ANIM_DURATION,
                    ease: G.EASE,
                    onComplete: () => card.remove()
                });
            }
        });
    }

    // --- INITIALIZATION ---

    function init() {
        calculatePositions();
        syncCarousel(); // Initial render

        G.navArrows.forEach(arrow => {
            arrow.addEventListener('click', function () {
                const isLeft = this.classList.contains('nav-arrow-left');
                // Direction Logic: Left arrow moves center right (dir 1), Right arrow moves center left (dir -1)
                const direction = isLeft ? 1 : -1;

                G.currentIndex = getWrappedIndex(G.currentIndex + direction);
                syncCarousel(direction);
            });

            // Prevent focus state from persisting after click
            arrow.addEventListener('mouseup', function () {
                this.blur();
            });
            arrow.addEventListener('mouseleave', function () {
                this.blur();
            });
        });

        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(() => {
                calculatePositions();
                syncCarousel();
            }, 100);
        });
    }

    init();
});