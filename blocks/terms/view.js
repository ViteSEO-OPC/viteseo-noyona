document.addEventListener('DOMContentLoaded', function () {
    var termBlocks = document.querySelectorAll('.terms');

    if (!termBlocks.length) {
        return;
    }

    var prefersReducedMotion = window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var scrollBehavior = prefersReducedMotion ? 'auto' : 'smooth';
    var panelDuration = prefersReducedMotion ? 0 : 600;
    var panelOffset = 8;
    var applyScrollOffset = function () {
        var offset = 0;
        var header = document.querySelector('header.wp-block-template-part');
        if (header) {
            offset += header.getBoundingClientRect().height;
        }
        var adminBar = document.getElementById('wpadminbar');
        if (adminBar) {
            offset += adminBar.getBoundingClientRect().height;
        }

        if (!offset) {
            return;
        }

        termBlocks.forEach(function (block) {
            var val = Math.ceil(offset);
            block.style.setProperty('--terms-scroll-offset', val + 'px');
            block.style.setProperty('--toc-top', (val + 20) + 'px');
        });
    };

    applyScrollOffset();
    window.addEventListener('resize', applyScrollOffset);

    termBlocks.forEach(function (block) {
        var tabButtons = block.querySelectorAll('.terms__tab-button');
        var tocPanels = block.querySelectorAll('.terms__toc-panel');
        var contentPanels = block.querySelectorAll('.terms__content-panel');

        var resetPanelStyles = function (panel) {
            panel.style.removeProperty('height');
            panel.style.removeProperty('opacity');
            panel.style.removeProperty('transform');
            panel.style.removeProperty('transition');
        };

        var animatePanel = function (panel, shouldOpen) {
            if (!panel) {
                return;
            }

            if (panel._termsTransitionHandler) {
                panel.removeEventListener('transitionend', panel._termsTransitionHandler);
                panel._termsTransitionHandler = null;
            }

            resetPanelStyles(panel);

            if (prefersReducedMotion) {
                panel.classList.remove('is-collapsed');
                panel.classList.toggle('is-collapsed', !shouldOpen);
                if (shouldOpen) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
                return;
            }

            panel.classList.remove('is-collapsed');
            panel.removeAttribute('hidden');

            var startHeight = shouldOpen ? 0 : panel.scrollHeight;
            var endHeight = shouldOpen ? panel.scrollHeight : 0;

            panel.style.height = startHeight + 'px';
            panel.style.opacity = shouldOpen ? '0' : '1';
            panel.style.transform = shouldOpen ? 'translateY(-' + panelOffset + 'px)' : 'translateY(0)';
            panel.offsetHeight;

            panel.style.transition = 'height ' + panelDuration + 'ms ease, opacity ' + panelDuration + 'ms ease, transform ' + panelDuration + 'ms ease';
            panel.style.height = endHeight + 'px';
            panel.style.opacity = shouldOpen ? '1' : '0';
            panel.style.transform = shouldOpen ? 'translateY(0)' : 'translateY(-' + panelOffset + 'px)';

            panel._termsTransitionHandler = function (event) {
                if (event && event.target !== panel) {
                    return;
                }
                panel.removeEventListener('transitionend', panel._termsTransitionHandler);
                panel._termsTransitionHandler = null;
                resetPanelStyles(panel);
                panel.classList.toggle('is-collapsed', !shouldOpen);
                if (shouldOpen) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            };

            panel.addEventListener('transitionend', panel._termsTransitionHandler);
        };

        var setActiveTab = function (tabId, animateOpen) {
            if (!tabId) {
                return;
            }
            var shouldAnimateOpen = !!animateOpen;

            tabButtons.forEach(function (button) {
                var isActive = button.dataset.tab === tabId;
                button.classList.toggle('is-active', isActive);
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.setAttribute('aria-expanded', isActive ? 'true' : 'false');
            });

            tocPanels.forEach(function (panel) {
                var isActive = panel.dataset.tab === tabId;
                panel.classList.toggle('is-active', isActive);
                if (isActive) {
                    panel.classList.remove('is-collapsed');
                    if (shouldAnimateOpen) {
                        animatePanel(panel, true);
                    } else {
                        panel.removeAttribute('hidden');
                        resetPanelStyles(panel);
                    }
                } else {
                    panel.setAttribute('hidden', 'hidden');
                    panel.classList.remove('is-collapsed');
                    resetPanelStyles(panel);
                }
            });

            contentPanels.forEach(function (panel) {
                var isActive = panel.dataset.tab === tabId;
                panel.classList.toggle('is-active', isActive);
                if (isActive) {
                    panel.removeAttribute('hidden');
                } else {
                    panel.setAttribute('hidden', 'hidden');
                }
            });

            var activeButton = block.querySelector('.terms__tab-button.is-active');
            if (activeButton) {
                activeButton.setAttribute('aria-expanded', 'true');
            }
        };

        if (tabButtons.length) {
            tabButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    if (button.classList.contains('is-active')) {
                        var panel = block.querySelector('.terms__toc-panel[data-tab="' + button.dataset.tab + '"]');
                        if (!panel) {
                            return;
                        }

                        var shouldOpen = panel.classList.contains('is-collapsed') || panel.hasAttribute('hidden');
                        animatePanel(panel, shouldOpen);
                        button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
                        return;
                    }

                    setActiveTab(button.dataset.tab, true);
                    
                    // Prevent 'snap' by scrolling to top of block when changing tabs
                    var blockTop = block.getBoundingClientRect().top + window.pageYOffset;
                    var offset = parseInt(getComputedStyle(block).getPropertyValue('--terms-scroll-offset')) || 0;
                    window.scrollTo({
                        top: blockTop - offset - 20,
                        behavior: scrollBehavior
                    });
                });
            });
        }

        var activeTabId = tabButtons.length ? tabButtons[0].dataset.tab : '';
        if (window.location.hash) {
            var targetFromHash = block.querySelector(window.location.hash);
            if (targetFromHash) {
                var targetPanel = targetFromHash.closest('.terms__content-panel');
                if (targetPanel && targetPanel.dataset.tab) {
                    activeTabId = targetPanel.dataset.tab;
                }
            }
        }

        if (activeTabId) {
            setActiveTab(activeTabId, false);
        }

        var links = block.querySelectorAll('.terms__toc-link[href^="#"]');

        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var href = link.getAttribute('href');
                if (!href || href.length < 2) {
                    return;
                }

                var target = block.querySelector(href);
                if (!target) {
                    return;
                }

                var targetPanel = target.closest('.terms__content-panel');
                if (targetPanel && targetPanel.dataset.tab) {
                    setActiveTab(targetPanel.dataset.tab);
                }

                event.preventDefault();
                target.scrollIntoView({ behavior: scrollBehavior, block: 'start' });

                if (history && history.pushState) {
                    history.pushState(null, '', href);
                } else {
                    window.location.hash = href;
                }
            });
        });
    });
});
