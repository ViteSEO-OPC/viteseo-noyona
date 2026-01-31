/**
 * Responsive carousel logic for Product Highlight block.
 * - Adjusts items per view on resize
 * - Handles prev/next buttons and dots
 */
(function () {
    function debounce(fn, wait) {
        let t;
        return function () {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, arguments), wait);
        };
    }

    function perView() {
        const w = window.innerWidth;
        if (w >= 1100) return 3;
        if (w >= 768) return 2;
        return 1;
    }

    function initCarousel(root) {
        const track = root.querySelector('.product-highlight__track');
        const cards = Array.from(root.querySelectorAll('.product-highlight__card'));
        const prevBtn = root.querySelector('.ph-prev');
        const nextBtn = root.querySelector('.ph-next');
        const dotsContainer = root.querySelector('.product-highlight__dots');

        if (!track || cards.length === 0) return;

        let currentPage = 0;
        let view = perView();
        let totalPages = Math.ceil(cards.length / view);

        function buildDots() {
            dotsContainer.innerHTML = '';
            for (let i = 0; i < totalPages; i++) {
                const dot = document.createElement('button');
                dot.className = 'ph-dot' + (i === currentPage ? ' active' : '');
                dot.setAttribute('aria-label', 'Go to page ' + (i + 1));
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            }
        }

        function updateWidths() {
            view = perView();
            totalPages = Math.ceil(cards.length / view);
            track.style.setProperty('--cards-visible', view);
            cards.forEach((card) => {
                card.style.flexBasis = (100 / view) + '%';
                card.style.maxWidth = (100 / view) + '%';
            });
            if (currentPage >= totalPages) currentPage = totalPages - 1;
            if (currentPage < 0) currentPage = 0;
            buildDots();
            update();
        }

        function update() {
            const offset = -(currentPage * 100);
            track.style.transform = `translateX(${offset}%)`;
            prevBtn.disabled = currentPage === 0;
            nextBtn.disabled = currentPage >= totalPages - 1;
            Array.from(dotsContainer.children).forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentPage);
            });

            const hideNav = totalPages <= 1;
            prevBtn.style.display = hideNav ? 'none' : '';
            nextBtn.style.display = hideNav ? 'none' : '';
            dotsContainer.style.display = hideNav ? 'none' : '';
        }

        function goTo(page) {
            if (page < 0) page = 0;
            if (page > totalPages - 1) page = totalPages - 1;
            currentPage = page;
            update();
        }

        prevBtn.addEventListener('click', () => goTo(currentPage - 1));
        nextBtn.addEventListener('click', () => goTo(currentPage + 1));

        const onResize = debounce(updateWidths, 150);
        window.addEventListener('resize', onResize);

        updateWidths();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.wp-block-noyona-product-highlight').forEach(initCarousel);
    });
})();
