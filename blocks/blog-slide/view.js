/**
 * Carousel logic for Blog Slide block.
 */
(function () {
    function debounce(fn, wait) {
        let t;
        return function () {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, arguments), wait);
        };
    }

    function perView(maxCards) {
        const w = window.innerWidth;
        if (w >= 1100) return maxCards;
        if (w >= 768) return Math.min(2, maxCards);
        return 1;
    }

    function initCarousel(root) {
        const track = root.querySelector('.blog-slide__track');
        const cards = Array.from(root.querySelectorAll('.blog-slide__card'));
        const prevBtn = root.querySelector('.bs-prev');
        const nextBtn = root.querySelector('.bs-next');
        const dotsContainer = root.querySelector('.blog-slide__dots');

        if (!track || cards.length === 0 || !prevBtn || !nextBtn || !dotsContainer) return;

        const maxCards = parseInt(root.dataset.cardsToShow, 10) || 3;
        let currentIndex = 0;
        let view = perView(maxCards);
        let maxIndex = Math.max(0, cards.length - view);
        let totalPositions = Math.max(1, maxIndex + 1);

        function buildDots() {
            dotsContainer.innerHTML = '';
            for (let i = 0; i < totalPositions; i++) {
                const dot = document.createElement('button');
                dot.className = 'bs-dot' + (i === currentIndex ? ' is-active' : '');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            }
        }

        function updateWidths() {
            view = perView(maxCards);
            maxIndex = Math.max(0, cards.length - view);
            totalPositions = Math.max(1, maxIndex + 1);
            track.style.setProperty('--cards-visible', view);
            cards.forEach((card) => {
                card.style.flexBasis = (100 / view) + '%';
                card.style.maxWidth = (100 / view) + '%';
            });
            if (currentIndex > maxIndex) currentIndex = maxIndex;
            if (currentIndex < 0) currentIndex = 0;
            buildDots();
            update();
        }

        function update() {
            const offset = -(currentIndex * (100 / view));
            track.style.transform = `translateX(${offset}%)`;
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= maxIndex;
            Array.from(dotsContainer.children).forEach((dot, idx) => {
                dot.classList.toggle('is-active', idx === currentIndex);
            });

            const hideNav = maxIndex <= 0;
            prevBtn.style.display = hideNav ? 'none' : '';
            nextBtn.style.display = hideNav ? 'none' : '';
            dotsContainer.style.display = hideNav ? 'none' : '';
        }

        function goTo(index) {
            if (index < 0) index = 0;
            if (index > maxIndex) index = maxIndex;
            currentIndex = index;
            update();
        }

        prevBtn.addEventListener('click', () => goTo(currentIndex - 1));
        nextBtn.addEventListener('click', () => goTo(currentIndex + 1));

        const onResize = debounce(updateWidths, 150);
        window.addEventListener('resize', onResize);

        updateWidths();
    }

    function onReady() {
        document.querySelectorAll('.wp-block-noyona-blog-slide').forEach(initCarousel);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
