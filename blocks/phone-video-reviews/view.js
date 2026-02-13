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

    if (overlay) {
      frame = overlay.querySelector('iframe');
      titleEl = overlay.querySelector('[data-phone-overlay-title]');
      backdrop = overlay.querySelector('.phone-reviews__overlay-backdrop');
      closeBtn = overlay.querySelector('.phone-reviews__overlay-close');
      shell = overlay.querySelector('.phone-reviews__overlay-shell');
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

      if (closeBtn) closeBtn.focus({ preventScroll: true });
    }

    function closeOverlay() {
      if (!overlay) return;
      overlay.classList.remove('is-open');
      overlay.setAttribute('aria-hidden', 'true');
      if (frame) frame.src = '';
      document.documentElement.classList.remove('phone-reviews--overlay-open');
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
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closeOverlay();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && overlay && overlay.classList.contains('is-open')) {
        closeOverlay();
      }
    });

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


