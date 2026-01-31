document.addEventListener('DOMContentLoaded', function () {
    var faqBlocks = document.querySelectorAll('.faq-list');

    if (!faqBlocks.length) {
        return;
    }

    faqBlocks.forEach(function (block) {
        var categoryButtons = block.querySelectorAll('[data-faq-category-button]');
        var items = block.querySelectorAll('[data-faq-item]');
        var searchInput = block.querySelector('[data-faq-search]');
        var searchButton = block.querySelector('[data-faq-search-button]');
        var emptyState = block.querySelector('.faq-list__empty');
        var activeCategory = '';
        var prefersReducedMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var motionDuration = prefersReducedMotion ? 0 : 320;

        var resetItemStyles = function (item) {
            var answer = item.querySelector('.faq-list__answer');
            if (!answer) {
                return;
            }

            answer.style.height = '';
            answer.style.opacity = '';
            answer.style.transform = '';
            answer.style.transition = '';
            answer.style.paddingTop = '';
            answer.style.paddingBottom = '';
            answer.style.overflow = '';
            answer.style.willChange = '';
            item.dataset.animating = '';
        };

        var getAnswerPadding = function (answer) {
            if (!answer.dataset.paddingTop || !answer.dataset.paddingBottom) {
                var styles = window.getComputedStyle(answer);
                answer.dataset.paddingTop = styles.paddingTop;
                answer.dataset.paddingBottom = styles.paddingBottom;
            }

            return {
                top: answer.dataset.paddingTop,
                bottom: answer.dataset.paddingBottom,
            };
        };

        var toggleItem = function (item, shouldOpen) {
            var answer = item.querySelector('.faq-list__answer');
            if (!answer) {
                if (shouldOpen) {
                    item.setAttribute('open', '');
                } else {
                    item.removeAttribute('open');
                }
                return;
            }

            if (item.dataset.animating === 'true') {
                return;
            }

            var padding = getAnswerPadding(answer);

            if (motionDuration === 0) {
                item.dataset.animating = 'true';
                if (shouldOpen) {
                    item.setAttribute('open', '');
                } else {
                    item.removeAttribute('open');
                }
                resetItemStyles(item);
                return;
            }

            item.dataset.animating = 'true';
            answer.style.overflow = 'hidden';
            answer.style.willChange = 'height, opacity, transform, padding';

            if (shouldOpen) {
                item.setAttribute('open', '');
                var targetHeight = answer.scrollHeight;
                answer.style.height = '0px';
                answer.style.opacity = '0';
                answer.style.transform = 'translateY(-6px)';
                answer.style.paddingTop = '0px';
                answer.style.paddingBottom = '0px';
                answer.offsetHeight;
                answer.style.transition = 'height ' + motionDuration + 'ms ease, opacity ' + motionDuration + 'ms ease, transform ' + motionDuration + 'ms ease, padding ' + motionDuration + 'ms ease';
                answer.style.height = targetHeight + 'px';
                answer.style.opacity = '1';
                answer.style.transform = 'translateY(0)';
                answer.style.paddingTop = padding.top;
                answer.style.paddingBottom = padding.bottom;

                window.setTimeout(function () {
                    resetItemStyles(item);
                }, motionDuration);
                return;
            }

            var startHeight = answer.scrollHeight;
            answer.style.height = startHeight + 'px';
            answer.style.opacity = '1';
            answer.style.transform = 'translateY(0)';
            answer.style.paddingTop = padding.top;
            answer.style.paddingBottom = padding.bottom;
            answer.offsetHeight;
            answer.style.transition = 'height ' + motionDuration + 'ms ease, opacity ' + motionDuration + 'ms ease, transform ' + motionDuration + 'ms ease, padding ' + motionDuration + 'ms ease';
            answer.style.height = '0px';
            answer.style.opacity = '0';
            answer.style.transform = 'translateY(-6px)';
            answer.style.paddingTop = '0px';
            answer.style.paddingBottom = '0px';

            window.setTimeout(function () {
                item.removeAttribute('open');
                resetItemStyles(item);
            }, motionDuration);
        };

        if (categoryButtons.length) {
            activeCategory = categoryButtons[0].dataset.faqCategoryButton || '';
        }

        var applyFilters = function () {
            var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var visibleCount = 0;

            items.forEach(function (item) {
                var itemCategory = item.dataset.faqCategory || '';
                var searchText = item.dataset.faqSearchText || '';
                var matchesCategory = !activeCategory || itemCategory === activeCategory;
                var matchesQuery = !query || searchText.indexOf(query) !== -1;

                if (matchesCategory && matchesQuery) {
                    item.removeAttribute('hidden');
                    visibleCount += 1;
                } else {
                    item.setAttribute('hidden', 'hidden');
                    item.removeAttribute('open');
                    resetItemStyles(item);
                }
            });

            if (emptyState) {
                if (visibleCount === 0) {
                    emptyState.removeAttribute('hidden');
                } else {
                    emptyState.setAttribute('hidden', 'hidden');
                }
            }
        };

        var setActiveCategory = function (categoryId) {
            activeCategory = categoryId;

            categoryButtons.forEach(function (button) {
                var isActive = button.dataset.faqCategoryButton === categoryId;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            applyFilters();
        };

        if (categoryButtons.length) {
            categoryButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    setActiveCategory(button.dataset.faqCategoryButton || '');
                });
            });

            setActiveCategory(activeCategory);
        } else {
            applyFilters();
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilters);
        }

        if (searchButton) {
            searchButton.addEventListener('click', function () {
                applyFilters();
                if (searchInput) {
                    searchInput.focus();
                }
            });
        }

        items.forEach(function (item) {
            var summary = item.querySelector('.faq-list__summary');
            if (!summary) {
                return;
            }

            summary.addEventListener('click', function (event) {
                event.preventDefault();
                var isOpen = item.hasAttribute('open');
                toggleItem(item, !isOpen);
            });
        });
    });
});
