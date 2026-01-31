/**
 * Blog list pager logic (prev/next + dots).
 */
(function () {
    function debounce(fn, wait) {
        let t;
        return function () {
            clearTimeout(t);
            t = setTimeout(() => fn.apply(this, arguments), wait);
        };
    }

    function perPage() {
        const w = window.innerWidth;
        if (w >= 1100) return 3;
        if (w >= 768) return 2;
        return 1;
    }

    function parsePageSizes(root) {
        const raw = root.dataset.pageSizes;
        if (!raw) return null;
        const sizes = raw.split(',').map((value) => parseInt(value, 10)).filter((value) => Number.isFinite(value) && value > 0);
        return sizes.length ? sizes : null;
    }

    function buildPages(totalItems, sizes, fallbackSize) {
        const pages = [];
        if (sizes && sizes.length) {
            let index = 0;
            let sizeIndex = 0;
            const lastSize = sizes[sizes.length - 1] || fallbackSize || 1;
            while (index < totalItems) {
                const size = sizes[sizeIndex] || lastSize;
                pages.push({ start: index, end: Math.min(index + size, totalItems) });
                index += size;
                sizeIndex++;
            }
            return pages;
        }

        const size = fallbackSize || 1;
        for (let index = 0; index < totalItems; index += size) {
            pages.push({ start: index, end: Math.min(index + size, totalItems) });
        }
        return pages;
    }

    function initPager(root) {
        const cards = Array.from(root.querySelectorAll('.blog-card'));
        const dotsContainer = root.querySelector('.blog-list__dots');
        const navButtons = root.querySelectorAll('.blog-list__nav');

        if (!cards.length || !dotsContainer || navButtons.length < 2) return;

        const prevBtn = navButtons[0];
        const nextBtn = navButtons[1];
        const customSizes = parsePageSizes(root);
        let currentPage = 0;
        let pageSize = perPage();
        let pages = buildPages(cards.length, customSizes, pageSize);
        let totalPages = pages.length;

        function buildDots() {
            dotsContainer.innerHTML = '';
            for (let i = 0; i < totalPages; i++) {
                const dot = document.createElement('button');
                dot.className = 'blog-list__dot' + (i === currentPage ? ' is-active' : '');
                dot.type = 'button';
                dot.setAttribute('aria-label', 'Go to page ' + (i + 1));
                dot.addEventListener('click', () => goTo(i));
                dotsContainer.appendChild(dot);
            }
        }

        function updateVisibility() {
            const currentRange = pages[currentPage] || { start: 0, end: 0 };
            cards.forEach((card, index) => {
                if (index >= currentRange.start && index < currentRange.end) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            prevBtn.disabled = currentPage === 0;
            nextBtn.disabled = currentPage >= totalPages - 1;

            Array.from(dotsContainer.children).forEach((dot, idx) => {
                dot.classList.toggle('is-active', idx === currentPage);
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
            updateVisibility();
        }

        function updateLayout() {
            pageSize = perPage();
            pages = buildPages(cards.length, customSizes, pageSize);
            totalPages = pages.length;
            if (currentPage >= totalPages) currentPage = totalPages - 1;
            buildDots();
            updateVisibility();
        }

        prevBtn.addEventListener('click', () => goTo(currentPage - 1));
        nextBtn.addEventListener('click', () => goTo(currentPage + 1));

        window.addEventListener('resize', debounce(updateLayout, 150));
        updateLayout();
    }

    function onReady() {
        document.querySelectorAll('.wp-block-noyona-blog-list').forEach(initPager);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }
})();
