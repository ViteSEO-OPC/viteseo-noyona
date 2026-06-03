document.addEventListener('DOMContentLoaded', function () {
    var termBlocks = document.querySelectorAll('.terms');
    if (!termBlocks.length) return;

    var prefersReducedMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var scrollBehavior = prefersReducedMotion ? 'auto' : 'smooth';

    var applyScrollOffset = function () {
        var offset = 0;
        var header = document.querySelector('header.wp-block-template-part');
        if (header) offset += header.getBoundingClientRect().height;

        var adminBar = document.getElementById('wpadminbar');
        if (adminBar) offset += adminBar.getBoundingClientRect().height;

        termBlocks.forEach(function (block) {
            var val = Math.ceil(offset || 0);
            block.style.setProperty('--terms-scroll-offset', val + 'px');
            block.style.setProperty('--toc-top', (val + 20) + 'px');
        });
    };

    applyScrollOffset();
    window.addEventListener('resize', applyScrollOffset);

    termBlocks.forEach(function (block) {
        var tocPanel = block.querySelector('.terms__toc');
        var tocOpenButton = block.querySelector('[data-terms-toc-open]');
        var tocCloseControls = block.querySelectorAll('[data-terms-toc-close]');
        var tocBackdrop = block.querySelector('.terms__toc-backdrop');
        var tocMobileQuery = window.matchMedia ? window.matchMedia('(max-width: 1023px)') : null;

        var closeTocPanel = function () {
            if (!tocPanel) return;
            tocPanel.classList.remove('is-open');
            if (tocBackdrop) {
                tocBackdrop.classList.remove('is-open');
            }
            if (tocOpenButton) {
                tocOpenButton.setAttribute('aria-expanded', 'false');
            }
            document.documentElement.classList.remove('terms-toc-panel-open');
        };

        var openTocPanel = function () {
            if (!tocPanel) return;
            tocPanel.classList.add('is-open');
            if (tocBackdrop) {
                tocBackdrop.classList.add('is-open');
            }
            if (tocOpenButton) {
                tocOpenButton.setAttribute('aria-expanded', 'true');
            }
            document.documentElement.classList.add('terms-toc-panel-open');
        };

        if (tocOpenButton) {
            tocOpenButton.addEventListener('click', function () {
                if (tocPanel && tocPanel.classList.contains('is-open')) {
                    closeTocPanel();
                } else {
                    openTocPanel();
                }
            });
        }

        tocCloseControls.forEach(function (control) {
            control.addEventListener('click', closeTocPanel);
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeTocPanel();
            }
        });

        var closeTocPanelOnDesktop = function () {
            if (tocMobileQuery && !tocMobileQuery.matches) {
                closeTocPanel();
            }
        };

        if (tocMobileQuery) {
            if (typeof tocMobileQuery.addEventListener === 'function') {
                tocMobileQuery.addEventListener('change', closeTocPanelOnDesktop);
            } else if (typeof tocMobileQuery.addListener === 'function') {
                tocMobileQuery.addListener(closeTocPanelOnDesktop);
            }
        }

        var scrollTopButton = block.querySelector('.terms__scroll-top');
        var updateScrollTopButton = function () {
            if (!scrollTopButton) return;
            var blockTop = block.getBoundingClientRect().top;
            scrollTopButton.classList.toggle('is-visible', blockTop < -240);
        };

        if (scrollTopButton) {
            scrollTopButton.addEventListener('click', function () {
                block.scrollIntoView({ behavior: scrollBehavior, block: 'start' });
            });
            updateScrollTopButton();
            window.addEventListener('scroll', updateScrollTopButton, { passive: true });
            window.addEventListener('resize', updateScrollTopButton);
        }

        var links = Array.prototype.slice.call(
            block.querySelectorAll('.terms__toc-link[href^="#"]')
        );

        if (!links.length) return;

        var itemsById = {};
        var sections = [];

        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href || href.length < 2) return;

            var id = href.slice(1);
            var section = block.querySelector('#' + id);
            if (!section) return;

            var item = link.closest('.terms__toc-item');
            itemsById[id] = item;
            sections.push(section);
        });

        var setActiveById = function (id) {
            links.forEach(function (link) {
                var href = link.getAttribute('href');
                if (!href || href.length < 2) return;
                var linkId = href.slice(1);
                var item = itemsById[linkId];
                if (!item) return;
                item.classList.toggle('is-active', linkId === id);
            });
        };

        var getTopOffset = function () {
            var styleOffset = parseInt(getComputedStyle(block).getPropertyValue('--terms-scroll-offset'), 10);
            return (isNaN(styleOffset) ? 0 : styleOffset) + 28;
        };

        var updateActiveFromViewport = function () {
            if (!sections.length) return;

            var topOffset = getTopOffset();
            // Move the activation line lower than the sticky header so the TOC
            // updates as a section enters the readable area, not only after it passes the header.
            var viewportLead = Math.min(window.innerHeight * 0.28, 220);
            var threshold = topOffset + viewportLead;
            var activeId = sections[0].id;

            sections.forEach(function (section) {
                if (section.getBoundingClientRect().top - threshold <= 0) {
                    activeId = section.id;
                }
            });

            setActiveById(activeId);
        };

        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var href = link.getAttribute('href');
                if (!href || href.length < 2) return;

                var id = href.slice(1);
                var target = block.querySelector('#' + id);
                if (!target) return;

                event.preventDefault();
                closeTocPanel();
                setActiveById(id);
                target.scrollIntoView({ behavior: scrollBehavior, block: 'start' });

                if (history && history.pushState) {
                    history.pushState(null, '', href);
                } else {
                    window.location.hash = href;
                }
            });
        });

        updateActiveFromViewport();
        window.addEventListener('scroll', updateActiveFromViewport, { passive: true });
        window.addEventListener('resize', updateActiveFromViewport);
    });
});

