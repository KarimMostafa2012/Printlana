document.addEventListener('DOMContentLoaded', function () {

    // Check if the necessary data and elements exist
    if (typeof homeland_carousel_data === 'undefined' || !homeland_carousel_data.slides || homeland_carousel_data.slides.length === 0) {
        // console.error('Homeland Carousel: No slide data found.');
        return;
    }

    const wrapper = document.querySelector('.homeland-carousel-wrapper');
    if (!wrapper) {
        return;
    }

    // --- CONFIG & STATE ---
    const products = homeland_carousel_data.slides;
    const TOTAL_PRODUCTS = products.length; // New: total number of available products

    const G = {
        // DOM Elements
        showcase: wrapper.querySelector('#productShowcase'),
        navArrows: wrapper.querySelectorAll('.nav-arrow'),
        cardElements: [], // New: Store references to reusable DOM card elements

        // Carousel State
        currentIndex: 0,
        isAnimating: false,

        // Animation Parameters
        ANIM_DURATION: 0.6,
        EASE_TYPE: 'cubic-bezier(0.4, 0.0, 0.2, 1)',

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
        POSITIONS: [], // Calculated below
        cardPoolSize: 7 // New: Number of DOM elements to create and reuse (e.g., 5 visible + 2 for entering/exiting)
    };

    // --- CALCULATE POSITIONS ---
    function calculatePositions() {
        const centerIndex = 2; // The large card is the 3rd element (index 2)
        const centerPosition = 0; // The center of the showcase

        G.POSITIONS = [];

        // Right side
        let currentPos = centerPosition;
        let lastWidth = G.CARD_WIDTH * G.SCALES.large;
        G.POSITIONS[centerIndex] = centerPosition;

        // Center -> Right Med
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.medium / 2);
        G.POSITIONS[centerIndex + 1] = currentPos;
        lastWidth = G.CARD_WIDTH * G.SCALES.medium;

        // Right Med -> Right Small
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.small / 2);
        G.POSITIONS[centerIndex + 2] = currentPos;

        // Left side (mirror the right)
        G.POSITIONS[centerIndex - 1] = -G.POSITIONS[centerIndex + 1];
        G.POSITIONS[centerIndex - 2] = -G.POSITIONS[centerIndex + 2];
    }

    // --- DOM & RENDERING ---
    const arrowSVG = `<svg class="arrow-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 6H10M10 6L6 2M10 6L6 10" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

    // New: Helper to update content of an existing card DOM element
    function updateCardContent(cardElement, product, productIndex) {
        let actualCard = cardElement;
        // If the wrapper is an anchor, get the inner .card div
        if (cardElement.tagName === 'A') {
            cardElement.href = product.link;
            actualCard = cardElement.querySelector('.card');
        } else if (product.link && product.link !== '#') {
            // If it was a div and now needs to be a link, replace it
            const newAnchor = document.createElement('a');
            newAnchor.href = product.link;
            newAnchor.dataset.productIndex = productIndex;
            cardElement.parentNode.replaceChild(newAnchor, cardElement);
            newAnchor.appendChild(cardElement); // Move the old div.card inside the new anchor
            actualCard = cardElement; // Still refers to the inner div
            cardElement = newAnchor; // Update reference for GSAP
        } else if (cardElement.tagName === 'A' && (!product.link || product.link === '#')) {
            // If it was an anchor and now doesn't need to be, unwrap it
            const parentDiv = cardElement.parentNode;
            while (cardElement.firstChild) {
                parentDiv.insertBefore(cardElement.firstChild, cardElement);
            }
            parentDiv.removeChild(cardElement);
            actualCard = parentDiv.querySelector('.card'); // Assuming the .card div is still there
            cardElement = actualCard; // Update reference for GSAP
        }
        
        actualCard.dataset.productIndex = productIndex;
        actualCard.querySelector('.product-image').src = product.image;
        actualCard.querySelector('.product-image').alt = product.name;
        actualCard.querySelector('.card-text-wrapper span').textContent = product.name;

        return cardElement; // Return potentially new cardElement reference
    }

    // New: Helper to create the initial card wrapper (a or div) with inner structure
    function createCardWrapper() {
        const cardInner = document.createElement('div');
        cardInner.className = 'card';
        cardInner.innerHTML = `
            <img class="product-image">
            <div class="blue-gradient-circle"></div>
            <div class="card-text-wrapper">
                <span></span>
                <button class="arrow-button">${arrowSVG}</button>
            </div>
        `;
        return cardInner; // Initially return the div.card
    }


    function getWrappedIndex(index) {
        // Ensure the index always loops correctly within the TOTAL_PRODUCTS
        return ((index % TOTAL_PRODUCTS) + TOTAL_PRODUCTS) % TOTAL_PRODUCTS;
    }

    // New: Initializes the pool of reusable DOM elements
    function initializeCards() {
        G.showcase.innerHTML = ''; // Clear any existing content
        const fragment = document.createDocumentFragment();

        // Create the pool of cards (div.card elements initially)
        for (let i = 0; i < G.cardPoolSize; i++) {
            const cardElement = createCardWrapper();
            G.cardElements.push(cardElement);
            fragment.appendChild(cardElement);
        }
        G.showcase.appendChild(fragment);

        // Now, populate and position them correctly for the initial view
        renderCards();
    }

    // Updated renderCards: now positions and updates existing elements
    function renderCards() {
        const sizePattern = ['small', 'medium', 'large', 'medium', 'small']; // For visible cards
        const offsetToCenter = Math.floor(G.cardPoolSize / 2) - 2; // How many cards to skip from pool start to align with visible slot -2

        G.cardElements.forEach((cardElement, poolIndex) => {
            // Determine the product for this card element based on the current index
            const productIndex = getWrappedIndex(G.currentIndex + (poolIndex - Math.floor(G.cardPoolSize / 2)));
            const product = products[productIndex];

            // Re-create wrapper if type needs to change (div to a or a to div)
            let currentWrapper = cardElement;
            if (product && product.link && product.link !== '#' && cardElement.tagName !== 'A') {
                const newAnchor = document.createElement('a');
                newAnchor.href = product.link;
                newAnchor.dataset.productIndex = productIndex;
                G.showcase.replaceChild(newAnchor, cardElement);
                newAnchor.appendChild(cardElement); // Move the old div.card inside the new anchor
                currentWrapper = newAnchor;
                G.cardElements[poolIndex] = newAnchor; // Update reference in our array
            } else if ((!product || !product.link || product.link === '#') && cardElement.tagName === 'A') {
                const innerCardDiv = cardElement.querySelector('.card');
                G.showcase.replaceChild(innerCardDiv, cardElement);
                currentWrapper = innerCardDiv;
                G.cardElements[poolIndex] = innerCardDiv; // Update reference
            }


            // Determine its conceptual slot relative to the 5 visible ones
            const visiblePatternIndex = poolIndex - offsetToCenter; // Maps poolIndex to 0-4 for visible cards
            
            let slotPosition = 0;
            let slotScale = 0;
            let slotZIndex = G.Z_INDICES.exit; // Default to exit z-index
            let slotOpacity = 0; // Default to hidden


            if (visiblePatternIndex >= 0 && visiblePatternIndex < 5) {
                // This card is within the 5 visible slots
                slotPosition = G.POSITIONS[visiblePatternIndex];
                slotScale = G.SCALES[sizePattern[visiblePatternIndex]];
                slotZIndex = G.Z_INDICES[sizePattern[visiblePatternIndex]];
                slotOpacity = 1;
            } else {
                // Card is outside the 5 visible slots, position it just off-screen
                // This is for the cards that are part of the pool but not currently visible
                if (poolIndex < offsetToCenter) { // Left side off-screen
                    slotPosition = G.POSITIONS[0] - (G.CARD_WIDTH * G.SCALES.small + G.GAP);
                } else { // Right side off-screen
                    slotPosition = G.POSITIONS[4] + (G.CARD_WIDTH * G.SCALES.small + G.GAP);
                }
                slotScale = G.SCALES.small;
                slotOpacity = 0;
            }

            if (product) {
                updateCardContent(currentWrapper, product, productIndex);
            } else {
                // Hide card if no product is available (e.g., less than 5 products total)
                 gsap.set(currentWrapper, { autoAlpha: 0 });
                 return;
            }

            gsap.set(currentWrapper, {
                x: slotPosition,
                scale: slotScale,
                zIndex: slotZIndex,
                autoAlpha: slotOpacity,
                overwrite: true // Ensure no conflicting inline styles from previous animations
            });
        });
    }


    function animateCards(direction) {
        if (G.isAnimating || TOTAL_PRODUCTS < 5) return; // Don't animate if not enough products
        G.isAnimating = true;
        G.navArrows.forEach(arrow => arrow.disabled = true);

        const timeline = gsap.timeline({
            duration: G.ANIM_DURATION,
            ease: G.EASE_TYPE,
            onComplete: () => {
                // After animation, reset the positions of all cards in the pool
                // to match their logical positions without animations.
                // This ensures the next animation starts from a clean state.
                G.currentIndex = getWrappedIndex(G.currentIndex + direction); // Update current index AFTER animations
                renderCards(); // Re-render to set all elements to their new static state
                G.isAnimating = false;
                G.navArrows.forEach(arrow => arrow.disabled = false);
            }
        });

        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];
        const offsetToCenter = Math.floor(G.cardPoolSize / 2) - 2;

        G.cardElements.forEach((cardElement, poolIndex) => {
            const currentProductIndex = getWrappedIndex(G.currentIndex + (poolIndex - Math.floor(G.cardPoolSize / 2)));
            const currentProduct = products[currentProductIndex];

            // Determine its conceptual target slot (index in the 5-card visible pattern)
            let targetVisiblePatternIndex = poolIndex - offsetToCenter - direction;

            // Calculate target properties
            let targetX = 0;
            let targetScale = 0;
            let targetZIndex = G.Z_INDICES.exit;
            let targetOpacity = 0;


            if (targetVisiblePatternIndex >= 0 && targetVisiblePatternIndex < 5) {
                // This card will be within the 5 visible slots
                targetX = G.POSITIONS[targetVisiblePatternIndex];
                targetScale = G.SCALES[sizePattern[targetVisiblePatternIndex]];
                targetZIndex = G.Z_INDICES[sizePattern[targetVisiblePatternIndex]];
                targetOpacity = 1;
            } else {
                // Card will be outside visible slots
                if (poolIndex - direction < offsetToCenter) { // Will be off-screen left
                    targetX = G.POSITIONS[0] - (G.CARD_WIDTH * G.SCALES.small + G.GAP);
                } else { // Will be off-screen right
                    targetX = G.POSITIONS[4] + (G.CARD_WIDTH * G.SCALES.small + G.GAP);
                }
                targetScale = G.SCALES.small; // Smaller for off-screen
                targetOpacity = 0;
            }

            timeline.to(cardElement, {
                x: targetX,
                scale: targetScale,
                zIndex: targetZIndex,
                autoAlpha: targetOpacity,
                duration: G.ANIM_DURATION,
                ease: G.EASE_TYPE
            }, 0); // All animations start at the same time
        });
        
        // At the very end of the animation (onComplete), G.currentIndex will be updated,
        // and renderCards will be called to resynchronize the content and exact positions.
    }

    // Since goToSlide directly jumps, we can remove it for now or implement a multi-step animate.
    // Given the complexity of animateCards, a direct jump to index would also require
    // pre-calculating all positions/scales/opacities for G.cardElements for the target index.
    // For now, goToSlide will be removed. Users should use arrows.
    function slide(direction) {
        animateCards(direction);
    }

    function init() {
        if (!G.showcase) return;
        calculatePositions();
        initializeCards(); // New: Initialize card DOM elements and render initial state

        G.navArrows.forEach(arrow => {
            const direction = arrow.classList.contains('nav-arrow-left') ? -1 : 1;
            arrow.addEventListener('click', () => slide(direction));
        });

        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(() => {
                calculatePositions();
                renderCards(); // Re-calculate positions and re-render existing cards
            }, 100);
        });
    }

    init();
});