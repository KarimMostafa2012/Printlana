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

    const G = {
        // DOM Elements
        showcase: wrapper.querySelector('#productShowcase'),
        dotsContainer: wrapper.querySelector('#dotsContainer'),
        navArrows: wrapper.querySelectorAll('.nav-arrow'),

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
        POSITIONS: []
    };

    // --- CALCULATE POSITIONS ---
    function calculatePositions() {
        const centerIndex = 2;
        const centerPosition = 0;
        G.POSITIONS = [];
        let currentPos = centerPosition;
        let lastWidth = G.CARD_WIDTH * G.SCALES.large;
        G.POSITIONS[centerIndex] = centerPosition;
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.medium / 2);
        G.POSITIONS[centerIndex + 1] = currentPos;
        lastWidth = G.CARD_WIDTH * G.SCALES.medium;
        currentPos += (lastWidth / 2) + G.GAP + (G.CARD_WIDTH * G.SCALES.small / 2);
        G.POSITIONS[centerIndex + 2] = currentPos;
        G.POSITIONS[centerIndex - 1] = -G.POSITIONS[centerIndex + 1];
        G.POSITIONS[centerIndex - 2] = -G.POSITIONS[centerIndex + 2];
    }

    // --- DOM & RENDERING ---
    const arrowSVG = `<svg class="arrow-icon" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M2 6H10M10 6L6 2M10 6L6 10" stroke="#141B34" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>`;

    function createProductCard(product, index, position, scale, zIndex) {
        const card = document.createElement('div');
        card.className = 'card';
        card.innerHTML = `
            <img src="${product.image}" alt="${product.name}" class="product-image">
            <div class="blue-gradient-circle"></div>
            <div class="card-text-wrapper">
                <span>${product.name}</span>
                <button class="arrow-button">${arrowSVG}</button>
            </div>
        `;

        const elementToAnimate = (product.link && product.link !== '#') ? document.createElement('a') : card;

        if (elementToAnimate.tagName === 'A') {
            elementToAnimate.href = product.link;
            elementToAnimate.appendChild(card);
        }
        
        elementToAnimate.dataset.index = index;

        gsap.set(elementToAnimate, {
            position: 'absolute',
            left: '50%',
            x: position,
            xPercent: -50,
            scale: scale,
            zIndex: zIndex,
            transformOrigin: 'bottom center'
        });

        return elementToAnimate;
    }

    function getWrappedIndex(index) {
        const len = products.length;
        return ((index % len) + len) % len;
    }

    function renderCards(isInitial = false) {
        if (isInitial) G.showcase.innerHTML = '';
        const fragment = document.createDocumentFragment();
        const startOffset = -2;
        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];
        for (let i = 0; i < 5; i++) {
            const productIndex = getWrappedIndex(G.currentIndex + startOffset + i);
            const product = products[productIndex];
            if (product) {
                const cardElement = createProductCard(product, productIndex, G.POSITIONS[i], G.SCALES[sizePattern[i]], G.Z_INDICES[sizePattern[i]]);
                fragment.appendChild(cardElement);
            }
        }
        G.showcase.appendChild(fragment);
    }

    function animateCards(direction) {
        if (G.isAnimating) return;
        G.isAnimating = true;
        G.navArrows.forEach(arrow => arrow.disabled = true);
        G.currentIndex = getWrappedIndex(G.currentIndex + direction);

        const timeline = gsap.timeline({
            duration: G.ANIM_DURATION,
            ease: G.EASE_TYPE,
            onComplete: () => {
                G.showcase.innerHTML = '';
                renderCards();
                G.isAnimating = false;
                G.navArrows.forEach(arrow => arrow.disabled = false);
            }
        });

        const cards = Array.from(G.showcase.children);
        const sizePattern = ['small', 'medium', 'large', 'medium', 'small'];
        const newCardIndex = getWrappedIndex(G.currentIndex + (direction === 1 ? 2 : -2));
        const newCardProduct = products[newCardIndex];
        const entranceSlotIndex = direction === 1 ? 4 : 0;
        
        if (!newCardProduct) return;

        const entranceX = G.POSITIONS[entranceSlotIndex] + (direction * (G.CARD_WIDTH * G.SCALES.small + G.GAP));
        const newCard = createProductCard(newCardProduct, newCardIndex, entranceX, G.SCALES.small, G.Z_INDICES.small);
        gsap.set(newCard, { opacity: 0 });
        G.showcase.appendChild(newCard);

        cards.forEach((card, i) => {
            const targetSlot = i - direction;
            if (targetSlot < 0 || targetSlot >= 5) {
                timeline.to(card, {
                    x: G.POSITIONS[i] - (direction * (G.CARD_WIDTH * G.SCALES.small + G.GAP)),
                    opacity: 0,
                    scale: G.SCALES.small * 0.9,
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
            x: G.POSITIONS[entranceSlotIndex],
            opacity: 1
        }, 0);
        
        updateDots();
    }

    function createDots() {
        if (!G.dotsContainer) return;
        G.dotsContainer.innerHTML = '';
        for (let i = 0; i < products.length; i++) {
            const dot = document.createElement('div');
            dot.classList.add('dot');
            dot.addEventListener('click', () => goToSlide(i));
            G.dotsContainer.appendChild(dot);
        }
        updateDots();
    }

    function updateDots() {
        if (!G.dotsContainer) return;
        const dots = G.dotsContainer.children;
        for (let i = 0; i < dots.length; i++) {
            dots[i].classList.toggle('active', i === G.currentIndex);
        }
    }

    function slide(direction) {
        animateCards(direction);
    }

    function goToSlide(index) {
        if (index === G.currentIndex || G.isAnimating) return;
        G.currentIndex = index;
        G.showcase.innerHTML = '';
        renderCards();
        updateDots();
    }

    function init() {
        if (!G.showcase) return;
        calculatePositions();
        renderCards(true);
        createDots();

        G.navArrows.forEach(arrow => {
            const direction = arrow.classList.contains('nav-arrow-left') ? -1 : 1;
            arrow.addEventListener('click', () => slide(direction));
        });

        window.addEventListener('resize', () => {
            clearTimeout(window.resizeTimer);
            window.resizeTimer = setTimeout(() => {
                calculatePositions();
                G.showcase.innerHTML = '';
                renderCards();
            }, 100);
        });
    }

    init();
});
