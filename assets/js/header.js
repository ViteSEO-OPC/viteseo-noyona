/**
 * Header behavior: add sticky/scroll classes and wire wishlist drawer.
 */
(function () {
    const STICKY_AT = 120; // px from top before switching to dark pink

    function toggleScrollState() {
        const y = window.scrollY || window.pageYOffset || 0;
        const headerPart = document.querySelector('header.wp-block-template-part');

        if (y > STICKY_AT) {
            document.body.classList.add('has-scrolled-header');
            if (headerPart) headerPart.classList.add('is-sticky');
        } else {
            document.body.classList.remove('has-scrolled-header');
            if (headerPart) headerPart.classList.remove('is-sticky');
        }
    }

    function initWishlist() {
        const toggleBtn = document.querySelector('.header-wishlist-toggle');
        const panel = document.getElementById('wishlist-panel');
        if (!toggleBtn || !panel) return;

        const overlay = panel.querySelector('.wishlist-panel__overlay');
        const closeBtn = panel.querySelector('.wishlist-panel__close');

        function closePanel() {
            panel.classList.remove('is-open');
        }

        toggleBtn.addEventListener('click', function () {
            panel.classList.add('is-open');
        });

        if (overlay) overlay.addEventListener('click', closePanel);
        if (closeBtn) closeBtn.addEventListener('click', closePanel);
        document.addEventListener('keydown', function (evt) {
            if (evt.key === 'Escape' && panel.classList.contains('is-open')) {
                closePanel();
            }
        });
    }

    function initAccountDropdown() {
        document.querySelectorAll('.header-account').forEach((wrapper) => {
            if (wrapper.dataset.accountReady === '1') return;
            wrapper.dataset.accountReady = '1';

            const toggle = wrapper.querySelector('.header-account-toggle');
            const menu = wrapper.querySelector('.header-account-menu');
            if (!toggle || !menu) return;

            function closeMenu() {
                wrapper.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
                menu.setAttribute('aria-hidden', 'true');
            }

            function openMenu() {
                wrapper.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                menu.setAttribute('aria-hidden', 'false');
            }

            toggle.addEventListener('click', function (evt) {
                evt.preventDefault();
                evt.stopPropagation();
                if (wrapper.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            document.addEventListener('click', function (evt) {
                if (!wrapper.contains(evt.target)) {
                    closeMenu();
                }
            });

            document.addEventListener('keydown', function (evt) {
                if (evt.key === 'Escape') {
                    closeMenu();
                }
            });
        });
    }

    function initLogoutLink() {
        const logoutUrl = window.noyonaHeader && window.noyonaHeader.logoutUrl ? window.noyonaHeader.logoutUrl : '';
        if (!logoutUrl) return;
        document.querySelectorAll('.header-account-logout').forEach((link) => {
            link.setAttribute('href', logoutUrl);
        });
    }

    function initMobileNavIcons() {
        const headerIcons = document.querySelector('.header-icons');
        const navRoot = document.querySelector('.main-nav');
        if (!headerIcons || !navRoot) return;

        const originalOrder = Array.from(headerIcons.children);
        if (!originalOrder.length) return;

        const miniCart = headerIcons.querySelector('.header-mini-cart');
        const storeLink = headerIcons.querySelector('.header-store-link');
        const searchBlock = headerIcons.querySelector('.wp-block-noyona-search-expand');
        const mobileQuery = window.matchMedia('(max-width: 959px)');

        const openBtn = navRoot.querySelector('.wp-block-navigation__responsive-container-open');
        const controlsId = openBtn ? openBtn.getAttribute('aria-controls') : '';

        function getNavContent() {
            if (controlsId) {
                const contentById = document.getElementById(`${controlsId}-content`);
                if (contentById) return contentById;

                const responsiveContainer = document.getElementById(controlsId);
                const content = responsiveContainer
                    ? responsiveContainer.querySelector('.wp-block-navigation__responsive-container-content')
                    : null;
                if (content) return content;
            }

            return navRoot.querySelector('.wp-block-navigation__responsive-container-content');
        }

        function ensureIconContainer() {
            const navContent = getNavContent();
            const scope = navContent || navRoot;
            const navList = scope.querySelector('.wp-block-navigation__container, .wp-block-page-list');
            if (!navList) return null;

            let iconContainer = scope.querySelector('.mobile-nav-icons');
            if (!iconContainer) {
                iconContainer = document.createElement('div');
                iconContainer.className = 'mobile-nav-icons';
            }

            navList.insertAdjacentElement('afterend', iconContainer);

            return iconContainer;
        }

        function moveIconsToMenu() {
            const iconContainer = ensureIconContainer();
            if (!iconContainer) return false;

            originalOrder.forEach((node) => {
                if (node !== miniCart && node !== storeLink && node !== searchBlock) {
                    iconContainer.appendChild(node);
                }
            });

            const outsideIcons = [miniCart, storeLink, searchBlock].filter(Boolean);
            outsideIcons.forEach((node) => {
                if (openBtn && navRoot.contains(openBtn)) {
                    navRoot.insertBefore(node, openBtn);
                } else {
                    navRoot.appendChild(node);
                }
            });

            document.body.classList.add('noyona-mobile-nav-icons-ready');
            return true;
        }

        function restoreHeaderIcons() {
            originalOrder.forEach((node) => {
                headerIcons.appendChild(node);
            });
            document.body.classList.remove('noyona-mobile-nav-icons-ready');
        }

        function handleChange() {
            if (mobileQuery.matches) {
                if (!moveIconsToMenu()) {
                    document.body.classList.remove('noyona-mobile-nav-icons-ready');
                }
            } else {
                restoreHeaderIcons();
            }
        }

        function scheduleMenuSync() {
            let attempts = 0;
            const maxAttempts = 12;
            const delay = 80;

            function trySync() {
                attempts += 1;
                if (getNavContent()) {
                    handleChange();
                } else if (attempts < maxAttempts) {
                    setTimeout(trySync, delay);
                }
            }

            trySync();
        }

        handleChange();
        if (mobileQuery.matches) {
            scheduleMenuSync();
        }

        if (typeof mobileQuery.addEventListener === 'function') {
            mobileQuery.addEventListener('change', handleChange);
        } else if (typeof mobileQuery.addListener === 'function') {
            mobileQuery.addListener(handleChange);
        }

        if (!getNavContent() && 'MutationObserver' in window) {
            const observer = new MutationObserver(() => {
                if (getNavContent()) {
                    handleChange();
                    observer.disconnect();
                }
            });
            const watchRoot = controlsId ? document.body : navRoot;
            observer.observe(watchRoot, { childList: true, subtree: true });
        }

        if (openBtn) {
            openBtn.addEventListener('click', scheduleMenuSync);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        toggleScrollState();
        window.addEventListener('scroll', toggleScrollState, { passive: true });
        initWishlist();
        initAccountDropdown();
        initLogoutLink();
        initMobileNavIcons();
    });
})();
