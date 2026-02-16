/**
 * Phone Video Reviews Block
 * - Click a phone card to open cinematic modal and autoplay with sound (where possible)
 * - YouTube: supports play/pause toggle in-grid via postMessage (enablejsapi=1)
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

      const cardIframe = card.querySelector('iframe');
      const src = cardIframe ? (cardIframe.dataset.embedSound || '') : '';
      if (!src) return;

      frame.src = src;
      frame.title = (card.querySelector('.phone-card__label') && card.querySelector('.phone-card__label').textContent) || 'Video review';

      const labelEl = card.querySelector('.phone-card__label');
      if (titleEl) titleEl.textContent = labelEl ? (labelEl.textContent || '') : '';

      overlay.classList.add('is-open');
      overlay.setAttribute('aria-hidden', 'false');
      document.documentElement.classList.add('phone-reviews--overlay-open');
      showOverlayControls();
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
          const left = target.offsetLeft - grid.clientWidth / 2 + target.clientWidth / 2;
          grid.scrollTo({ left: Math.max(0, left), behavior: 'auto' });
          grid.dataset.centeredForCarousel = '1';
        });
      });
    }

    // Card interactions
    cards.forEach((card) => {
      const iframe = card.querySelector('iframe');
      const toggleBtn = card.querySelector('.phone-card__toggle');
      const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
      const videoType = (card.dataset.videoType || 'youtube').toLowerCase();

      if (!iframe) return;

      // Initial state
      card.dataset.videoState = 'playing';
      if (toggleBtn && icon) {
        // Playing = show pause icon on hover
        icon.className = 'fas fa-pause';
      }

      card.addEventListener('click', function (e) {
        // Prevent "click-through" re-open after closing the overlay on touch devices.
        if (suppressCardOpenUntil && Date.now() < suppressCardOpenUntil) {
          e.preventDefault();
          return;
        }

        // Toggle play/pause (YouTube only)
        if (toggleBtn && e.target && e.target.closest && e.target.closest('.phone-card__toggle')) {
          e.preventDefault();
          e.stopPropagation();

          if (videoType === 'youtube') {
            const isPlaying = card.dataset.videoState === 'playing';
            try {
              if (isPlaying) {
                iframe.contentWindow &&
                  iframe.contentWindow.postMessage(
                    '{"event":"command","func":"pauseVideo","args":""}',
                    '*'
                  );
                card.dataset.videoState = 'paused';
                if (icon) icon.className = 'fas fa-play';
                toggleBtn.style.opacity = '1';
              } else {
                iframe.contentWindow &&
                  iframe.contentWindow.postMessage(
                    '{"event":"command","func":"playVideo","args":""}',
                    '*'
                  );
                card.dataset.videoState = 'playing';
                if (icon) icon.className = 'fas fa-pause';
                toggleBtn.style.opacity = '0';
              }
            } catch (_) {}
          }
          return;
        }

        // Otherwise open modal
        if (e.target && e.target.closest && e.target.closest('a, button')) return;
        e.preventDefault();
        openOverlay(card);
      });

      // Hover effect for Pause icon
      if (toggleBtn) {
        card.addEventListener('mouseenter', function () {
          if (card.dataset.videoState === 'playing') {
            if (icon) icon.className = 'fas fa-pause';
            toggleBtn.style.opacity = '1';
          }
        });
        card.addEventListener('mouseleave', function () {
          if (card.dataset.videoState === 'playing') {
            toggleBtn.style.opacity = '0';
          }
        });
      }
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
    centerCarouselToMiddle(false);

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


