(function () {
    var initFaqLists = function () {
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
        var categoriesWrap = block.querySelector('.faq-list__categories');
        var itemsWrap = block.querySelector('.faq-list__items');
        var activeCategory = '';
        var prefersReducedMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var motionDuration = prefersReducedMotion ? 0 : 320;
        var mobileMq = window.matchMedia('(max-width: 1360px)');
        var panelsByCategory = {};
        var originalOrder = Array.prototype.slice.call(items);

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

        var getContentHeight = function (answer) {
            var styles = window.getComputedStyle(answer);
            var h = answer.scrollHeight;
          
            // scrollHeight includes padding; if box-sizing is content-box, subtract padding
            if (styles.boxSizing !== 'border-box') {
              var pt = parseFloat(styles.paddingTop) || 0;
              var pb = parseFloat(styles.paddingBottom) || 0;
              h = Math.max(0, h - pt - pb);
            }
          
            return h;
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
              
                var targetHeight = getContentHeight(answer); // ✅ change
              
                answer.style.height = '0px';
                answer.style.opacity = '0';
                answer.style.transform = 'translateY(-6px)';
                answer.style.paddingTop = '0px';
                answer.style.paddingBottom = '0px';
              
                answer.offsetHeight;
              
                answer.style.transition =
                  'height ' + motionDuration + 'ms ease, opacity ' + motionDuration + 'ms ease, transform ' + motionDuration + 'ms ease, padding ' + motionDuration + 'ms ease';
              
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

        var ensureMobilePanels = function () {
            if (!categoriesWrap || !itemsWrap) {
                return;
            }

            if (!mobileMq.matches) {
                return;
            }

            // Build per-category panels once
            categoryButtons.forEach(function (btn) {
                var id = btn.dataset.faqCategoryButton || '';
                if (!id || panelsByCategory[id]) {
                    return;
                }
                var panel = document.createElement('div');
                panel.className = 'faq-list__category-panel';
                panel.setAttribute('hidden', 'hidden');

                var panelItems = document.createElement('div');
                panelItems.className = 'faq-list__items';
                panel.appendChild(panelItems);

                btn.insertAdjacentElement('afterend', panel);
                panelsByCategory[id] = panel;
            });

            // Move items into their category panels (keeps functionality, avoids duplication)
            originalOrder.forEach(function (item) {
                var cat = item.dataset.faqCategory || '';
                if (!cat || !panelsByCategory[cat]) {
                    return;
                }
                var panelItems = panelsByCategory[cat].querySelector('.faq-list__items');
                if (panelItems && item.parentElement !== panelItems) {
                    panelItems.appendChild(item);
                }
            });
        };

        var restoreDesktopItems = function () {
            if (!itemsWrap) return;
            if (mobileMq.matches) return;

            // Move items back into main container
            originalOrder.forEach(function (item) {
                if (item.parentElement !== itemsWrap) {
                    itemsWrap.appendChild(item);
                }
            });

            // Hide panels if they exist
            Object.keys(panelsByCategory).forEach(function (key) {
                var panel = panelsByCategory[key];
                if (panel) {
                    panel.setAttribute('hidden', 'hidden');
                }
            });
        };

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
                button.classList.toggle('is-open', isActive && mobileMq.matches);
                button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
            });

            applyFilters();
        };

        var applyFiltersMobile = function () {
            ensureMobilePanels();
            var query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            var visibleCount = 0;

            // Searching mode: filter across ALL categories, auto-open only matching panels.
            if (query) {
                categoryButtons.forEach(function (button) {
                    var id = button.dataset.faqCategoryButton || '';
                    var panel = panelsByCategory[id];
                    if (!id || !panel) return;

                    var panelItems = panel.querySelectorAll('[data-faq-item]');
                    var panelVisible = 0;

                    panelItems.forEach(function (item) {
                        var searchText = item.dataset.faqSearchText || '';
                        var matchesQuery = searchText.indexOf(query) !== -1;
                        if (matchesQuery) {
                            item.removeAttribute('hidden');
                            panelVisible += 1;
                            visibleCount += 1;
                        } else {
                            item.setAttribute('hidden', 'hidden');
                            item.removeAttribute('open');
                            resetItemStyles(item);
                        }
                    });

                    var shouldOpen = panelVisible > 0;
                    button.classList.toggle('is-open', shouldOpen);
                    button.classList.toggle('is-active', shouldOpen);
                    button.setAttribute('aria-pressed', shouldOpen ? 'true' : 'false');
                    if (shouldOpen) {
                        panel.removeAttribute('hidden');
                    } else {
                        panel.setAttribute('hidden', 'hidden');
                    }
                });
            } else {
                // Normal mode: show ONLY the active category panel and reset any previous search hides.
                Object.keys(panelsByCategory).forEach(function (key) {
                    var panel = panelsByCategory[key];
                    if (!panel) return;
                    panel.querySelectorAll('[data-faq-item]').forEach(function (item) {
                        item.removeAttribute('hidden');
                    });
                });

                categoryButtons.forEach(function (button) {
                    var id = button.dataset.faqCategoryButton || '';
                    var panel = panelsByCategory[id];
                    var isOpen = !!activeCategory && id === activeCategory;

                    button.classList.toggle('is-open', isOpen);
                    button.classList.toggle('is-active', isOpen);
                    button.setAttribute('aria-pressed', isOpen ? 'true' : 'false');

                    if (panel) {
                        if (isOpen) {
                            panel.removeAttribute('hidden');
                        } else {
                            panel.setAttribute('hidden', 'hidden');
                        }
                    }
                });

                // Count visible items in open category (or 0 if none open)
                var openPanel = activeCategory ? panelsByCategory[activeCategory] : null;
                if (openPanel) {
                    openPanel.querySelectorAll('[data-faq-item]').forEach(function (item) {
                        if (!item.hasAttribute('hidden')) visibleCount += 1;
                    });
                }
            }

            if (emptyState) {
                if (visibleCount === 0) {
                    emptyState.removeAttribute('hidden');
                } else {
                    emptyState.setAttribute('hidden', 'hidden');
                }
            }
        };

        if (categoryButtons.length) {
            categoryButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    var id = button.dataset.faqCategoryButton || '';
                    if (mobileMq.matches) {
                        // Toggle open/close on mobile
                        if (activeCategory === id) {
                            activeCategory = '';
                        } else {
                            activeCategory = id;
                        }
                        applyFiltersMobile();
                        return;
                    }
                    setActiveCategory(id);
                });
            });

            if (mobileMq.matches) {
                ensureMobilePanels();
                applyFiltersMobile();
            } else {
                setActiveCategory(activeCategory);
            }
        } else {
            if (mobileMq.matches) {
                applyFiltersMobile();
            } else {
                applyFilters();
            }
        }

        if (searchInput) {
            var runSearch = function () {
                if (mobileMq.matches) {
                    applyFiltersMobile();
                } else {
                    applyFilters();
                }
            };

            searchInput.addEventListener('input', runSearch);
            // Handles clicking the native clear "x" on type=search in some browsers.
            searchInput.addEventListener('search', runSearch);
            searchInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    runSearch();
                }
            });
        }

        if (searchButton) {
            searchButton.addEventListener('click', function () {
                if (mobileMq.matches) {
                    applyFiltersMobile();
                } else {
                    applyFilters();
                }
                if (searchInput) {
                    searchInput.focus();
                }
            });
        }

        items.forEach(function (item) {
            var summary = item.querySelector('.faq-list__summary');
            if (!summary) return;
          
            summary.addEventListener('click', function (event) {
              event.preventDefault();
          
              var isOpen = item.hasAttribute('open');
          
              // if opening, close other open items in the same container (panel)
              if (!isOpen && item.parentElement) {
                item.parentElement.querySelectorAll('details.faq-list__item[open]').forEach(function (d) {
                  if (d !== item) toggleItem(d, false);
                });
              }
          
              toggleItem(item, !isOpen);
            });
        }); 

        // Handle viewport changes between mobile accordion and desktop tabs
        if (mobileMq && mobileMq.addEventListener) {
            mobileMq.addEventListener('change', function () {
                if (mobileMq.matches) {
                    ensureMobilePanels();
                    if (!activeCategory && categoryButtons.length) {
                        activeCategory = categoryButtons[0].dataset.faqCategoryButton || '';
                    }
                    applyFiltersMobile();
                } else {
                    restoreDesktopItems();
                    if (!activeCategory && categoryButtons.length) {
                        activeCategory = categoryButtons[0].dataset.faqCategoryButton || '';
                    }
                    setActiveCategory(activeCategory);
                }
            });
        }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFaqLists);
    } else {
        initFaqLists();
    }
})();
