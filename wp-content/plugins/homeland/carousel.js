document.addEventListener('DOMContentLoaded', function () {
    // --- DATA & STATE ---
    if (typeof homeland_carousel_data === 'undefined' || !homeland_carousel_data.slides || homeland_carousel_data.slides.length === 0) {
        return;
    }

    const products = homeland_carousel_data.slides;

    const G = {
        // DOM Elements
        showcase: document.getElementById('productShowcase'),
        navArrows: document.querySelectorAll('.nav-arrow'),

        // Carousel State
        currentIndex: 0,
        isAnimating: false,
        moveQueue: 0, // Queue for multiple rapid clicks

        // Animation Parameters
        ANIM_DURATION: 0.3, // Even snappier for queue processing
        EASE_TYPE: 'power2.inOut',

        // Card Layout Parameters
        CARD_WIDTH: 290,
        GAP: 16,
        SCALES: {
            large: 1.0,
            medium: 0.8,
            small: 0.577
        },
        Z_INDICES: {
            large: 3,
            medium: 2,
            small: 1,
            exit: 0
        },
        POSITIONS: []
    };

    // --- CORE LOGIC ---

    function calculatePositions() {
        const centerIndex = 2;
        G.POSITIONS = [];
        G.POSITIONS[centerIndex] = 0;

        let currentPos = 0;
        let lastWidth = G.CARD_WIDTH * G.SCALES.large;

        // Center to Medium
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.medium / 2);
        G.POSITIONS[centerIndex + 1] = currentPos;

        // Medium to Small
        lastWidth = G.CARD_WIDTH * G.SCALES.medium;
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.small / 2);
        G.POSITIONS[centerIndex + 2] = currentPos;

        // Mirror to Left side
        G.POSITIONS[centerIndex - 1] = -G.POSITIONS[centerIndex + 1];
        G.POSITIONS[centerIndex - 2] = -G.POSITIONS[centerIndex + 2];
    }

    function getWrappedIndex(index) {
        const len = products.length;
        return ((index % len) + len) % len;
    }

    // --- RENDERING ---

    const arrowSVG = `<svg class="arrow-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 6H10M10 6L6 2M10 6L6 10" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

    function createProductCard(product, index, position, scale, zIndex) {
        const hasLink = product.link && product.link !== '#';
        const cardTag = hasLink ? 'a' : 'div';

        const card = document.createElement(cardTag);
        if (hasLink) card.href = product.link;
        card.className = 'card-wrapper';

        card.innerHTML = `
            <div class="card" data-index="${index}">
                <img src="${product.image}" alt="${product.name}" class="product-image">
                <div class="blue-gradient-circle"></div>
                <div class="card-text-wrapper">
                    <span>${product.name}</span>
                    <button class="arrow-button">${arrowSVG}</button>
                </div>
            </div>
        `;

        gsap.set(card, {
            position: 'absolute',
            left: '50%',
            x: position,
            xPercent: -50,
            scale: scale,
            zIndex: zIndex,
            transformOrigin: 'bottom center'
        });

        return card;
    }

    function renderInitialSet() {
        G.showcase.innerHTML = '';
        const fragment = document.createDocumentFragment();
        const startOffset = -2;
        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];

        for (let i = 0; i < 5; i++) {
            const productIndex = getWrappedIndex(G.currentIndex + startOffset + i);
            const card = createProductCard(
                products[productIndex],
                productIndex,
                G.POSITIONS[i],
                G.SCALES[sizePattern[i]],
                G.Z_INDICES[sizePattern[i]]
            );
            fragment.appendChild(card);
        }
        G.showcase.appendChild(fragment);
    }

    // --- ANIMATION CONTROLS ---

    function processQueue() {
        if (G.isAnimating || G.moveQueue === 0 || products.length < 5) return;

        const direction = Math.sign(G.moveQueue);
        G.moveQueue -= direction;

        executeSlide(direction);
    }

    function executeSlide(direction) {
        G.isAnimating = true;

        G.currentIndex = getWrappedIndex(G.currentIndex + direction);

        const timeline = gsap.timeline({
            duration: G.ANIM_DURATION,
            ease: G.EASE_TYPE,
            onComplete: () => {
                G.showcase.innerHTML = '';
                renderInitialSet();
                G.isAnimating = false;
                processQueue(); // Check for more moves
            }
        });

        const currentCards = Array.from(G.showcase.children);
        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];

        const newCardIndex = getWrappedIndex(G.currentIndex + (direction === 1 ? 2 : -2));
        const entranceSlot = direction === 1 ? 4 : 0;
        const entranceStartPosition = G.POSITIONS[entranceSlot] + (direction * (G.CARD_WIDTH * G.SCALES.small + G.GAP));

        const newCard = createProductCard(
            products[newCardIndex],
            newCardIndex,
            entranceStartPosition,
            G.SCALES.small,
            G.Z_INDICES.small
        );
        gsap.set(newCard, { opacity: 0 });
        G.showcase.appendChild(newCard);

        currentCards.forEach((card, i) => {
            const targetSlot = i - direction;

            if (targetSlot < 0 || targetSlot >= 5) {
                const exitPosition = G.POSITIONS[i] - (direction * (G.CARD_WIDTH * G.SCALES.small + G.GAP));
                timeline.to(card, {
                    x: exitPosition,
                    opacity: 0,
                    scale: G.SCALES.small * 0.8,
                    zIndex: G.Z_INDICES.exit
                }, 0);
            } else {
                timeline.to(card, {
                    x: G.POSITIONS[targetSlot],
                    scale: G.SCALES[sizePattern[targetSlot]],
                    zIndex: G.Z_INDICES[sizePattern[targetSlot]]
                }, 0);
            }
        });

        timeline.to(newCard, {
            x: G.POSITIONS[entranceSlot],
            opacity: 1
        }, 0);
    }

    // --- INITIALIZATION ---

    function init() {
        calculatePositions();
        renderInitialSet();

        G.navArrows.forEach(arrow => {
            arrow.addEventListener('click', function () {
                const isLeft = this.classList.contains('nav-arrow-left');
                // Direction Logic (v1.0.3 verified): 
                // Left arrow -> content moves Left (reveals right) -> direction 1.
                // Right arrow -> content moves Right (reveals left) -> direction -1.
                const direction = isLeft ? 1 : -1;

                G.moveQueue += direction;
                processQueue();
            });
        });

        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(() => {
                calculatePositions();
                renderInitialSet();
            }, 100);
        });
    }

    init();
});