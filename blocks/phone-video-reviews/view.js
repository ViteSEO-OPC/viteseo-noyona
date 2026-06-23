/**
 * Phone Video Reviews Block
 * - Cards render lightweight thumbnails.
 * - Click a phone card to create the embed in the cinematic modal.
 */
(function () {
  function debounce(fn, wait) {
    let t;
    return function () {
      clearTimeout(t);
      const args = arguments;
      const ctx = this;
      t = setTimeout(function () {
        fn.apply(ctx, args);
      }, wait);
    };
  }

  function initPhoneReviews(block) {
    const cards = Array.from(block.querySelectorAll('.phone-card'));
    const grid = block.querySelector('[data-phone-grid]');
    const dotsContainer = block.querySelector('.phone-reviews__dots');
    const overlay = block.querySelector('.phone-reviews__overlay');
    const carouselMq = window.matchMedia ? window.matchMedia('(max-width: 1240px)') : null;

    if (!cards.length) return;

    let frame = null;
    let titleEl = null;
    let backdrop = null;
    let closeBtn = null;
    let shell = null;
    let controlsTimer = null;
    let suppressCardOpenUntil = 0;
    let dotsFrame = null;
    const isCoarsePointer = !!(window.matchMedia && window.matchMedia('(hover: none) and (pointer: coarse)').matches);

    if (overlay) {
      frame = overlay.querySelector('iframe');
      titleEl = overlay.querySelector('[data-phone-overlay-title]');
      backdrop = overlay.querySelector('.phone-reviews__overlay-backdrop');
      closeBtn = overlay.querySelector('.phone-reviews__overlay-close');
      shell = overlay.querySelector('.phone-reviews__overlay-shell');
    }

    // Show/hide the close button like video controls:
    // - show on interaction (hover/move/click/tap)
    // - auto-hide after idle
    function showOverlayControls() {
      const root = document.documentElement;
      if (!overlay || !overlay.classList.contains('is-open')) return;

      root.classList.add('phone-reviews--overlay-controls');
      if (controlsTimer) window.clearTimeout(controlsTimer);

      // On touch devices, keep Close available so taps can go directly to the player controls.
      if (isCoarsePointer) return;

      controlsTimer = window.setTimeout(() => {
        // Don't hide while the close button is focused
        if (closeBtn && document.activeElement === closeBtn) return;
        root.classList.remove('phone-reviews--overlay-controls');
      }, 2500);
    }

    function openOverlay(card) {
      if (!overlay || !frame) return;

      const aspect = card.dataset.aspect || 'portrait';
      if (shell) shell.dataset.aspect = aspect;

      const src = card.dataset.embedSound || '';
      if (!src) return;

      frame.title = (card.querySelector('.phone-card__label') && card.querySelector('.phone-card__label').textContent) || 'Video review';

      const labelEl = card.querySelector('.phone-card__label');
      if (titleEl) titleEl.textContent = labelEl ? (labelEl.textContent || '') : '';

      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('phone-reviews--overlay-open');
      showOverlayControls();

      requestAnimationFrame(() => {
        frame.src = src;
      });
    }

    function exitAnyFullscreen() {
      const d = document;
      const fsEl =
        d.fullscreenElement ||
        d.webkitFullscreenElement ||
        d.mozFullScreenElement ||
        d.msFullscreenElement ||
        null;

      if (!fsEl) return;

      const exit =
        d.exitFullscreen ||
        d.webkitExitFullscreen ||
        d.mozCancelFullScreen ||
        d.msExitFullscreen ||
        null;

      if (exit) {
        try {
          exit.call(d);
        } catch (_) {}
      }

      // iOS Safari can expose fullscreen methods on the element (video).
      try {
        if (fsEl && typeof fsEl.webkitExitFullscreen === 'function') {
          fsEl.webkitExitFullscreen();
        }
      } catch (_) {}
    }

    function closeOverlay() {
      if (!overlay) return;
      exitAnyFullscreen();
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      if (frame) {
        // Use a safe, explicit blank URL. Empty string can resolve to the current page.
        frame.src = 'about:blank';
        frame.title = '';
      }
      document.documentElement.classList.remove('phone-reviews--overlay-open');
      document.documentElement.classList.remove('phone-reviews--overlay-controls');
      if (controlsTimer) window.clearTimeout(controlsTimer);
    }

    function centerCarouselToMiddle(force) {
      if (!grid || !carouselMq || !carouselMq.matches) return;
      if (!force && grid.dataset.centeredForCarousel === '1') return;
      if (cards.length < 2) return;

      const middleIndex = Math.floor(cards.length / 2);
      const target = cards[middleIndex];
      if (!target) return;

      requestAnimationFrame(() => {
        requestAnimationFrame(() => {
          grid.scrollTo({ left: Math.max(0, scrollLeftForCard(target)), behavior: 'auto' });
          grid.dataset.centeredForCarousel = '1';
          updateDots();
        });
      });
    }

    function scrollLeftForCard(card) {
      return card.offsetLeft - grid.clientWidth / 2 + card.clientWidth / 2;
    }

    function getActiveIndex() {
      if (!grid) return 0;

      const viewportCenter = grid.scrollLeft + grid.clientWidth / 2;
      let bestIndex = 0;
      let bestDistance = Infinity;

      cards.forEach((card, index) => {
        const cardCenter = card.offsetLeft + card.clientWidth / 2;
        const distance = Math.abs(cardCenter - viewportCenter);

        if (distance < bestDistance) {
          bestDistance = distance;
          bestIndex = index;
        }
      });

      return bestIndex;
    }

    function setActiveDot(index) {
      if (!dotsContainer) return;

      Array.from(dotsContainer.children || []).forEach((dot, dotIndex) => {
        const isActive = dotIndex === index;
        dot.classList.toggle('is-active', isActive);
        dot.setAttribute('aria-current', isActive ? 'true' : 'false');
      });
    }

    function updateDots() {
      if (!dotsContainer || !carouselMq) return;

      if (!carouselMq.matches) {
        dotsContainer.style.display = 'none';
        return;
      }

      dotsContainer.style.display = '';
      setActiveDot(getActiveIndex());
    }

    function requestDotsUpdate() {
      if (dotsFrame) return;

      dotsFrame = requestAnimationFrame(() => {
        dotsFrame = null;
        updateDots();
      });
    }

    function buildDots() {
      if (!dotsContainer || !grid || cards.length < 2) return;

      dotsContainer.innerHTML = '';
      cards.forEach((card, index) => {
        const dot = document.createElement('button');
        dot.className = 'phone-reviews__dot';
        dot.type = 'button';
        dot.setAttribute('aria-label', 'Go to video review ' + (index + 1));
        dot.addEventListener('click', () => {
          // Mobile (<=780px): dots are indicators only; users navigate by swipe /
          // native scroll and the scroll listener keeps the active dot in sync.
          // Tablet/desktop (>780px; dots show up to 1240px) keep click-to-navigate,
          // so this uses a dedicated 780px check rather than the 1240px carouselMq.
          if (window.matchMedia && window.matchMedia('(max-width: 780px)').matches) return;
          grid.scrollTo({ left: Math.max(0, scrollLeftForCard(card)), behavior: 'smooth' });
          setActiveDot(index);
        });
        dotsContainer.appendChild(dot);
      });

      updateDots();
    }

    // Card interactions
    cards.forEach((card) => {
      card.addEventListener('click', function (e) {
        // Prevent "click-through" re-open after closing the overlay on touch devices.
        if (suppressCardOpenUntil && Date.now() < suppressCardOpenUntil) {
          e.preventDefault();
          return;
        }

        if (e.target && e.target.closest && e.target.closest('a, button')) return;
        e.preventDefault();
        openOverlay(card);
      });
    });

    // Overlay listeners
    if (backdrop) {
      backdrop.addEventListener('click', function (e) {
        e.preventDefault();
        closeOverlay();
      });
    }
    if (closeBtn) {
      const onClose = function (e) {
        // Use pointer/touch events for reliability on mobile + iframes.
        if (e && e.preventDefault) e.preventDefault();
        if (e && e.stopPropagation) e.stopPropagation();
        if (e && e.stopImmediatePropagation) e.stopImmediatePropagation();
        // Block any immediately-following synthetic click from re-opening the overlay.
        suppressCardOpenUntil = Date.now() + 650;
        closeOverlay();
      };

      closeBtn.addEventListener('click', onClose);
      closeBtn.addEventListener('pointerdown', onClose);
      closeBtn.addEventListener(
        'touchstart',
        function (e) {
          onClose(e);
        },
        { passive: false }
      );
    }

    document.addEventListener('keydown', function (e) {
      if (!overlay || !overlay.classList.contains('is-open')) return;
      showOverlayControls();
      if (e.key === 'Escape') closeOverlay();
    });

    // Reveal/hide controls only when the pointer is over the phone shell
    // (not the backdrop), so the button won't show when mouse is outside.
    if (shell) {
      const onActivity = function () {
        showOverlayControls();
      };

      shell.addEventListener('pointermove', onActivity, { passive: true });
      shell.addEventListener('pointerdown', onActivity);
      shell.addEventListener('touchstart', onActivity, { passive: true });
      shell.addEventListener('mousemove', onActivity, { passive: true });
      shell.addEventListener('focusin', onActivity);

      shell.addEventListener('mouseleave', function () {
        // Hide quickly when leaving the phone area (desktop UX).
        if (controlsTimer) window.clearTimeout(controlsTimer);
        document.documentElement.classList.remove('phone-reviews--overlay-controls');
      });

      if (closeBtn) {
        closeBtn.addEventListener('mouseenter', function () {
          if (controlsTimer) window.clearTimeout(controlsTimer);
          document.documentElement.classList.add('phone-reviews--overlay-controls');
        });
        closeBtn.addEventListener('mouseleave', function () {
          showOverlayControls();
        });
      }

      // Mobile/tablet fallback:
      // Some browsers have buggy hit-testing with cross-origin iframes, where taps meant for
      // the Close button end up being handled by the player (pause/play). We create a "top
      // safe area" in CSS on <=1024px; tapping that area should always close.
      const onTopSafeAreaTap = function (e) {
        if (!overlay || !overlay.classList.contains('is-open')) return;
        if (!shell) return;

        // Only treat taps that land on the shell itself (i.e., padding area), not on inner elements.
        if (e && e.target && e.target !== shell) return;

        let padTop = 0;
        try {
          const cs = window.getComputedStyle(shell);
          padTop = parseFloat(cs && cs.paddingTop ? cs.paddingTop : '0') || 0;
        } catch (_) {}

        if (!padTop) return;

        // If the tap is within the padded top area, close.
        const y = e && typeof e.clientY === 'number' ? e.clientY : null;
        if (y !== null && y <= padTop + 1) {
          if (e && e.preventDefault) e.preventDefault();
          if (e && e.stopPropagation) e.stopPropagation();
          suppressCardOpenUntil = Date.now() + 650;
          closeOverlay();
        }
      };

      shell.addEventListener('pointerdown', onTopSafeAreaTap);
      shell.addEventListener(
        'touchstart',
        function (e) {
          onTopSafeAreaTap(e);
        },
        { passive: false }
      );
    }

    // Note: we intentionally do not intercept iframe taps. Cross-origin embeds don't bubble events,
    // so we keep Close available on touch devices via CSS.

    // On first load, if we're already in carousel mode (<=1240px), start in the middle.
    buildDots();
    centerCarouselToMiddle(false);
    updateDots();
    if (grid) {
      grid.addEventListener('scroll', requestDotsUpdate, { passive: true });
    }

    // When resizing into carousel mode, re-center once.
    if (carouselMq) {
      const onMq = function (e) {
        if (!grid) return;
        if (e.matches) {
          grid.dataset.centeredForCarousel = '0';
          centerCarouselToMiddle(true);
        } else {
          grid.dataset.centeredForCarousel = '0';
        }
        updateDots();
      };
      // Safari uses addListener
      if (carouselMq.addEventListener) {
        carouselMq.addEventListener('change', onMq);
      } else if (carouselMq.addListener) {
        carouselMq.addListener(onMq);
      }
    }

    // If fonts/layout load late, re-center once.
    window.addEventListener('resize', debounce(() => centerCarouselToMiddle(true), 150));
  }

  function onReady() {
    document.querySelectorAll('.phone-reviews').forEach(initPhoneReviews);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', onReady);
  } else {
    onReady();
  }
})();


