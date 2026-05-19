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
            const suggestions = block.querySelector('[data-search-suggestions]');
            const resultsList = block.querySelector('[data-search-results]');
            const status = block.querySelector('[data-search-status]');
            const viewAll = block.querySelector('[data-search-view-all]');
            let debounceTimer = null;
            let requestController = null;
            let requestSerial = 0;
            let activeIndex = -1;

            const productSearchUrl = function (query) {
                return '/?s=' + encodeURIComponent(query) + '&post_type=product';
            };

            const showSuggestions = function () {
                if (!suggestions) return;
                suggestions.hidden = false;
                inlineInput.setAttribute('aria-expanded', 'true');
            };

            const hideSuggestions = function () {
                if (!suggestions) return;
                suggestions.hidden = true;
                inlineForm.classList.remove('is-loading');
                inlineInput.setAttribute('aria-expanded', 'false');
                inlineInput.removeAttribute('aria-activedescendant');
                activeIndex = -1;
            };

            const clearResults = function () {
                if (resultsList) resultsList.innerHTML = '';
                if (status) {
                    status.textContent = '';
                    status.classList.remove('is-error');
                }
            };

            const setViewAllUrl = function (query) {
                if (viewAll) viewAll.href = productSearchUrl(query);
            };

            const getResultItems = function () {
                return resultsList ? Array.from(resultsList.querySelectorAll('[data-search-result-item]')) : [];
            };

            const setActiveIndex = function (nextIndex) {
                const items = getResultItems();
                if (!items.length) {
                    activeIndex = -1;
                    inlineInput.removeAttribute('aria-activedescendant');
                    return;
                }

                activeIndex = (nextIndex + items.length) % items.length;
                items.forEach(function (item, index) {
                    const isActive = index === activeIndex;
                    item.classList.toggle('is-active', isActive);
                    item.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    if (isActive) {
                        inlineInput.setAttribute('aria-activedescendant', item.id);
                    }
                });
            };

            const formatPrice = function (product) {
                if (!product || !product.prices || !product.prices.price) {
                    return '';
                }

                const prices = product.prices;
                const parsedMinorUnit = parseInt(prices.currency_minor_unit || '2', 10);
                const minorUnit = Number.isFinite(parsedMinorUnit) ? parsedMinorUnit : 2;
                const divisor = Math.pow(10, minorUnit);
                const rawPrice = parseInt(prices.price, 10);
                if (!Number.isFinite(rawPrice)) {
                    return '';
                }

                const amount = rawPrice / divisor;
                const prefix = prices.currency_prefix || prices.currency_symbol || '';
                const suffix = prices.currency_suffix || '';
                return prefix + amount.toLocaleString(undefined, {
                    minimumFractionDigits: minorUnit,
                    maximumFractionDigits: minorUnit,
                }) + suffix;
            };

            const renderProducts = function (products, query) {
                clearResults();
                setViewAllUrl(query);
                activeIndex = -1;

                const normalizedQuery = query.toLowerCase();
                const sortedProducts = (Array.isArray(products) ? products : []).slice().sort(function (a, b) {
                    const aName = String(a && a.name ? a.name : '').toLowerCase();
                    const bName = String(b && b.name ? b.name : '').toLowerCase();
                    return Number(bName.indexOf(normalizedQuery) !== -1) - Number(aName.indexOf(normalizedQuery) !== -1);
                }).slice(0, 5);

                if (!sortedProducts.length) {
                    if (status) status.textContent = 'No products found';
                    if (viewAll) viewAll.hidden = false;
                    showSuggestions();
                    return;
                }

                if (viewAll) viewAll.hidden = false;
                sortedProducts.forEach(function (product, index) {
                    const name = product && product.name ? String(product.name) : 'View product';
                    const link = product && (product.permalink || product.link) ? String(product.permalink || product.link) : '#';
                    const image = product && product.images && product.images[0] ? product.images[0] : null;
                    const price = formatPrice(product);

                    const item = document.createElement('li');
                    item.className = 'search-inline-suggestions__item';
                    item.id = inlineInput.id + '-suggestion-' + index;
                    item.setAttribute('role', 'option');
                    item.setAttribute('aria-selected', 'false');
                    item.setAttribute('data-search-result-item', '');

                    const anchor = document.createElement('a');
                    anchor.className = 'search-inline-suggestion';
                    anchor.href = link;

                    const media = document.createElement('span');
                    media.className = 'search-inline-suggestion__media';
                    if (image && image.src) {
                        const img = document.createElement('img');
                        img.src = String(image.src);
                        img.alt = image.alt ? String(image.alt) : name;
                        img.loading = 'lazy';
                        img.decoding = 'async';
                        media.appendChild(img);
                    }

                    const body = document.createElement('span');
                    body.className = 'search-inline-suggestion__body';

                    const title = document.createElement('span');
                    title.className = 'search-inline-suggestion__name';
                    title.textContent = name;
                    body.appendChild(title);

                    if (price) {
                        const priceNode = document.createElement('span');
                        priceNode.className = 'search-inline-suggestion__price';
                        priceNode.textContent = price;
                        body.appendChild(priceNode);
                    }

                    if (typeof product.is_in_stock === 'boolean') {
                        const stock = document.createElement('span');
                        stock.className = 'search-inline-suggestion__stock' + (product.is_in_stock ? ' is-in-stock' : ' is-out-of-stock');
                        stock.textContent = product.is_in_stock ? 'In stock' : 'Out of stock';
                        body.appendChild(stock);
                    }

                    anchor.appendChild(media);
                    anchor.appendChild(body);
                    item.appendChild(anchor);
                    resultsList.appendChild(item);
                });

                showSuggestions();
            };

            const loadInlineSuggestions = function (query) {
                const serial = ++requestSerial;
                if (requestController) {
                    requestController.abort();
                }

                requestController = typeof AbortController !== 'undefined' ? new AbortController() : null;
                inlineForm.classList.add('is-loading');
                clearResults();
                if (status) status.textContent = 'Searching...';
                if (viewAll) viewAll.hidden = true;
                showSuggestions();

                const fetchOptions = {
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json' },
                };
                if (requestController) {
                    fetchOptions.signal = requestController.signal;
                }

                fetch(API_ROUTES.products + '?search=' + encodeURIComponent(query) + '&per_page=5', fetchOptions)
                    .then(function (res) {
                        if (!res.ok) throw new Error('Product suggestions failed');
                        return res.json();
                    })
                    .then(function (products) {
                        if (serial !== requestSerial) return;
                        inlineForm.classList.remove('is-loading');
                        renderProducts(products, query);
                    })
                    .catch(function (error) {
                        if (error && error.name === 'AbortError') return;
                        if (serial !== requestSerial) return;
                        inlineForm.classList.remove('is-loading');
                        clearResults();
                        if (status) {
                            status.textContent = 'Search suggestions are unavailable.';
                            status.classList.add('is-error');
                        }
                        if (viewAll) viewAll.hidden = false;
                        setViewAllUrl(query);
                        showSuggestions();
                    });
            };

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
                if (!inlineForm.contains(evt.target)) {
                    hideSuggestions();
                    if (!mobileQuery.matches) {
                        return;
                    }
                    collapseMobileInput();
                }
            });

            inlineInput.addEventListener('input', function () {
                const query = inlineInput.value.trim();
                clearTimeout(debounceTimer);
                activeIndex = -1;

                if (!query) {
                    hideSuggestions();
                    clearResults();
                    return;
                }

                debounceTimer = setTimeout(function () {
                    loadInlineSuggestions(query);
                }, 250);
            });

            inlineInput.addEventListener('keydown', function (evt) {
                if (evt.key === 'Escape') {
                    if (suggestions && !suggestions.hidden) {
                        evt.preventDefault();
                        hideSuggestions();
                        return;
                    }
                    collapseMobileInput();
                    inlineSubmit.focus();
                    return;
                }

                if (evt.key === 'ArrowDown') {
                    const items = getResultItems();
                    if (suggestions && !suggestions.hidden && items.length) {
                        evt.preventDefault();
                        setActiveIndex(activeIndex + 1);
                    }
                    return;
                }

                if (evt.key === 'ArrowUp') {
                    const items = getResultItems();
                    if (suggestions && !suggestions.hidden && items.length) {
                        evt.preventDefault();
                        setActiveIndex(activeIndex - 1);
                    }
                    return;
                }

                if (evt.key === 'Enter' && activeIndex >= 0) {
                    const activeItem = getResultItems()[activeIndex];
                    const activeLink = activeItem ? activeItem.querySelector('a[href]') : null;
                    if (activeLink) {
                        evt.preventDefault();
                        window.location.href = activeLink.href;
                    }
                }
            });

            inlineForm.addEventListener('submit', function () {
                hideSuggestions();
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
