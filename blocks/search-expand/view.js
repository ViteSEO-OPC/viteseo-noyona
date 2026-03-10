/**
 * Front-end behavior for the Search Expand block.
 * - Opens/closes overlay
 * - Fetches suggestions for products, categories, and tags
 */
(function () {
    const API_ROUTES = {
        products: '/wp-json/wc/store/products',
        categories: '/wp-json/wp/v2/categories',
        tags: '/wp-json/wp/v2/tags',
    };

    function fetchJson(url) {
        return fetch(url, { credentials: 'same-origin' }).then(function (res) {
            if (!res.ok) return [];
            return res.json();
        }).catch(function () {
            return [];
        });
    }

    function attachSearch(block) {
        // Inline search behavior used by the current header block.
        const inlineForm = block.querySelector('.search-inline-form');
        const inlineInput = block.querySelector('.search-inline-input');
        const inlineSubmit = block.querySelector('.search-inline-submit');
        if (inlineForm && inlineInput && inlineSubmit) {
            const mobileQuery = window.matchMedia('(max-width: 500px)');

            const collapseMobileInput = function () {
                inlineForm.classList.remove('is-mobile-expanded');
            };

            const syncMode = function () {
                if (!mobileQuery.matches) {
                    collapseMobileInput();
                }
            };

            inlineSubmit.addEventListener('click', function (evt) {
                if (!mobileQuery.matches) {
                    return;
                }

                const isExpanded = inlineForm.classList.contains('is-mobile-expanded');
                if (!isExpanded) {
                    evt.preventDefault();
                    inlineForm.classList.add('is-mobile-expanded');
                    setTimeout(function () {
                        inlineInput.focus();
                    }, 0);
                }
            });

            document.addEventListener('click', function (evt) {
                if (!mobileQuery.matches) {
                    return;
                }
                if (!inlineForm.contains(evt.target)) {
                    collapseMobileInput();
                }
            });

            inlineInput.addEventListener('keydown', function (evt) {
                if (evt.key === 'Escape') {
                    collapseMobileInput();
                    inlineSubmit.focus();
                }
            });

            if (typeof mobileQuery.addEventListener === 'function') {
                mobileQuery.addEventListener('change', syncMode);
            } else if (typeof mobileQuery.addListener === 'function') {
                mobileQuery.addListener(syncMode);
            }
            syncMode();
            return;
        }

        const trigger = block.querySelector('.search-expand-trigger');
        const overlay = block.querySelector('.search-expand-overlay');
        const closeBtn = block.querySelector('.search-expand-close');
        const input = block.querySelector('.search-expand-input');
        const suggestionsGroup = block.querySelector('.search-suggestions-group');
        const termsList = block.querySelector('.suggestions-terms .suggestions-list');
        const productsList = block.querySelector('.suggestions-products .suggestions-list');
        const noResults = block.querySelector('.no-results');
        const viewAllLink = block.querySelector('.view-all-results');

        if (!trigger || !overlay || !input) return;

        function openOverlay() {
            overlay.classList.add('is-open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.classList.add('search-expand-open');
            setTimeout(function () {
                input.focus();
            }, 80);
        }

        function closeOverlay() {
            overlay.classList.remove('is-open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('search-expand-open');
            // reset UI so you don't see leftovers next open
            input.value = '';
            if (typeof hideResults === 'function') hideResults();
        }

        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openOverlay();
        });
        if (closeBtn) closeBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            closeOverlay();
          });

        overlay.addEventListener('click', function (evt) {
            if (evt.target === overlay) closeOverlay();
        });

        document.addEventListener('keydown', function (evt) {
            if (evt.key === 'Escape' && overlay.classList.contains('is-open')) {
                closeOverlay();
            }
        });

        let debounceTimer;
        input.addEventListener('input', function (evt) {
            const query = evt.target.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                hideResults();
                return;
            }

            debounceTimer = setTimeout(function () {
                loadSuggestions(query);
            }, 250);
        });

        function hideResults() {
            suggestionsGroup.classList.add('hidden');
            noResults.classList.add('hidden');
            viewAllLink.classList.add('hidden');
            termsList.innerHTML = '';
            productsList.innerHTML = '';
        }

        function loadSuggestions(query) {
            Promise.all([
                fetchJson(API_ROUTES.products + '?search=' + encodeURIComponent(query) + '&per_page=5'),
                fetchJson(API_ROUTES.categories + '?search=' + encodeURIComponent(query) + '&per_page=5'),
                fetchJson(API_ROUTES.tags + '?search=' + encodeURIComponent(query) + '&per_page=5'),
            ]).then(function (responses) {
                const products = responses[0] || [];
                const categories = responses[1] || [];
                const tags = responses[2] || [];
                renderResults(products, categories, tags, query);
            });
        }

        function renderResults(products, categories, tags, query) {
            termsList.innerHTML = '';
            productsList.innerHTML = '';
            let hasResults = false;

            var terms = categories.concat(tags).slice(0, 5);
            if (terms.length) {
                terms.forEach(function (term) {
                    var li = document.createElement('li');
                    li.innerHTML = '<a href="' + (term.link || '#') + '">' + term.name + '</a>';
                    termsList.appendChild(li);
                });
                hasResults = true;
            }

            if (products.length) {
                products.forEach(function (product) {
                    var name = product.name || (product.title && product.title.rendered) || 'View product';
                    var link = product.permalink || product.link || '#';
                    var image = product.images && product.images[0] ? product.images[0] : null;
                    var thumb = image ? '<img src="' + image.src + '" alt="' + (image.alt || name) + '" class="suggestion-thumb">' : '';

                    var li = document.createElement('li');
                    li.innerHTML = '<a class="product-suggestion" href="' + link + '">' + thumb + '<span>' + name + '</span></a>';
                    productsList.appendChild(li);
                });
                hasResults = true;
            }

            if (hasResults) {
                suggestionsGroup.classList.remove('hidden');
                noResults.classList.add('hidden');
                viewAllLink.classList.remove('hidden');
                viewAllLink.href = '/?s=' + encodeURIComponent(query) + '&post_type=product';
            } else {
                suggestionsGroup.classList.add('hidden');
                noResults.classList.remove('hidden');
                viewAllLink.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var blocks = document.querySelectorAll('.wp-block-noyona-search-expand');
        blocks.forEach(attachSearch);
    });
})();
