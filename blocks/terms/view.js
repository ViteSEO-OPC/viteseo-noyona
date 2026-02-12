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

