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
        const allCards = Array.from(root.querySelectorAll('.blog-card'));
        const dotsContainer = root.querySelector('.blog-list__dots');
        const navButtons = root.querySelectorAll('.blog-list__nav');
        const filterButtons = Array.from(root.querySelectorAll('.blog-filter[data-blog-filter]'));
        const filterSelect = root.querySelector('.blog-list__filter-select');
        const shareButtons = Array.from(root.querySelectorAll('.blog-card__save[data-share-url]'));
        const shareModal = root.querySelector('.blog-share-modal');
        const shareHint = shareModal ? shareModal.querySelector('.blog-share-modal__hint') : null;

        if (!allCards.length || !dotsContainer || navButtons.length < 2) return;

        const prevBtn = navButtons[0];
        const nextBtn = navButtons[1];
        const customSizes = parsePageSizes(root);
        let currentPage = 0;
        let pageSize = perPage();
        let pages = buildPages(allCards.length, customSizes, pageSize);
        let totalPages = pages.length;

        function activeCards() {
            return allCards.filter((card) => !card.classList.contains('is-filtered-out'));
        }

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
            const visiblePool = activeCards();
            allCards.forEach((card) => {
                card.style.display = 'none';
            });
            visiblePool.forEach((card, index) => {
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
            const pool = activeCards();
            pages = buildPages(pool.length, customSizes, pageSize);
            totalPages = pages.length;
            if (currentPage >= totalPages) currentPage = totalPages - 1;
            buildDots();
            updateVisibility();
        }

        function setActiveFilter(value) {
            const filterValue = (value || 'all').toString();

            // Update UI state
            filterButtons.forEach((btn) => {
                const isActive = btn.dataset.blogFilter === filterValue;
                btn.classList.toggle('is-active', isActive);
                btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
            if (filterSelect && filterSelect.value !== filterValue) {
                filterSelect.value = filterValue;
            }

            // Apply to cards
            allCards.forEach((card) => {
                const cats = (card.dataset.blogCats || '').trim();
                const matches =
                    filterValue === 'all' ||
                    (cats && cats.split(/\s+/).includes(filterValue));
                card.classList.toggle('is-filtered-out', !matches);
            });

            // Reset to first page when filter changes
            currentPage = 0;
            updateLayout();
        }

        prevBtn.addEventListener('click', () => goTo(currentPage - 1));
        nextBtn.addEventListener('click', () => goTo(currentPage + 1));

        window.addEventListener('resize', debounce(updateLayout, 150));

        // Bind filter interactions
        if (filterButtons.length) {
            filterButtons.forEach((btn) => {
                btn.addEventListener('click', () => setActiveFilter(btn.dataset.blogFilter || 'all'));
            });
        }
        if (filterSelect) {
            filterSelect.addEventListener('change', () => setActiveFilter(filterSelect.value || 'all'));
        }

        // Initialize with whatever is marked active in markup; fallback to "all"
        const initialBtn = filterButtons.find((b) => b.classList.contains('is-active'));
        const initialValue = (initialBtn && initialBtn.dataset.blogFilter) || (filterSelect && filterSelect.value) || 'all';
        setActiveFilter(initialValue);

        // Share modal logic
        function setHint(message) {
            if (!shareHint) return;
            shareHint.textContent = message || '';
        }

        function buildShareLinks(url) {
            if (!shareModal) return;
            const encoded = encodeURIComponent(url);
            const map = {
                facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encoded,
                x: 'https://twitter.com/intent/tweet?url=' + encoded,
                linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + encoded,
            };

            shareModal.querySelectorAll('[data-share-platform]').forEach((el) => {
                const platform = el.getAttribute('data-share-platform');
                if (!platform) return;

                if (el.tagName === 'A') {
                    el.setAttribute('href', map[platform] || '#');
                }
                // Buttons handled on click (copy/instagram)
            });
        }

        function openShare(url) {
            if (!shareModal || !url || url === '#') return;
            setHint('');
            buildShareLinks(url);
            shareModal.hidden = false;
            document.body.style.overflow = 'hidden';

            const closeBtn = shareModal.querySelector('.blog-share-modal__close');
            if (closeBtn) closeBtn.focus({ preventScroll: true });

            shareModal.dataset.shareUrl = url;
        }

        function closeShare() {
            if (!shareModal) return;
            shareModal.hidden = true;
            document.body.style.overflow = '';
            setHint('');
            delete shareModal.dataset.shareUrl;
        }

        async function copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                // Fallback
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(textarea);
                return ok;
            }
        }

        if (shareModal) {
            shareModal.addEventListener('click', async (e) => {
                const target = e.target;
                if (!(target instanceof Element)) return;

                const closeEl = target.closest('[data-share-close]');
                if (closeEl) {
                    e.preventDefault();
                    closeShare();
                    return;
                }

                const item = target.closest('[data-share-platform]');
                if (!item) return;

                const platform = item.getAttribute('data-share-platform');
                const url = shareModal.dataset.shareUrl || '';
                if (!platform || !url) return;

                if (platform === 'copy') {
                    e.preventDefault();
                    const ok = await copyToClipboard(url);
                    setHint(ok ? 'Link copied!' : 'Could not copy link.');
                }

                if (platform === 'instagram') {
                    e.preventDefault();
                    const ok = await copyToClipboard(url);
                    setHint(ok ? 'Link copied — paste it into Instagram.' : 'Could not copy link.');
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !shareModal.hidden) closeShare();
            });
        }

        if (shareButtons.length) {
            shareButtons.forEach((btn) => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = btn.dataset.shareUrl || '';
                    openShare(url);
                });
            });
        }
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
