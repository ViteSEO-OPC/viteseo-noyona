(function () {
  function initCustomerReviews(block) {
    if (!block) return;

    var cards = Array.prototype.slice.call(block.querySelectorAll('.customer-reviews-grid__card'));
    var readMoreButton = block.querySelector('[data-read-more]');
    var readMoreLabel = readMoreButton ? readMoreButton.querySelector('span') : null;
    var readMoreIcon = readMoreButton ? readMoreButton.querySelector('.customer-reviews-grid__read-more-icon') : null;
    var revealStep = 3;
    var visibleCount = revealStep;
    var expandLabel = readMoreButton ? (readMoreButton.getAttribute('data-expand-label') || 'Read More Reviews') : 'Read More Reviews';
    var collapseLabel = readMoreButton ? (readMoreButton.getAttribute('data-collapse-label') || 'Collapse') : 'Collapse';

    function syncVisibleCards() {
      cards.forEach(function (card, index) {
        var shouldShow = index < visibleCount;
        card.hidden = !shouldShow;
      });

      if (readMoreButton) {
        if (cards.length <= revealStep) {
          readMoreButton.hidden = true;
          return;
        }
        readMoreButton.hidden = false;
        var allVisible = visibleCount >= cards.length;
        if (readMoreLabel) {
          readMoreLabel.textContent = allVisible ? collapseLabel : expandLabel;
        }
        if (readMoreIcon) {
          readMoreIcon.classList.remove('fa-angle-down', 'fa-angle-up');
          readMoreIcon.classList.add(allVisible ? 'fa-angle-up' : 'fa-angle-down');
        }
      }
    }

    if (cards.length > revealStep) {
      syncVisibleCards();
      if (readMoreButton) {
        readMoreButton.addEventListener('click', function () {
          if (visibleCount >= cards.length) {
            visibleCount = revealStep;
          } else {
            visibleCount = Math.min(visibleCount + revealStep, cards.length);
          }
          syncVisibleCards();
        });
      }
    } else if (readMoreButton) {
      readMoreButton.hidden = true;
    }

    var modal = block.querySelector('[data-review-modal]');
    var modalImage = block.querySelector('[data-review-modal-image]');
    var modalCloseButtons = block.querySelectorAll('[data-review-modal-close]');
    var mediaButtons = block.querySelectorAll('[data-review-media]');

    if (!modal || !modalImage || !mediaButtons.length) return;

    function closeModal() {
      modal.hidden = true;
      document.documentElement.classList.remove('customer-reviews-modal-open');
      modalImage.removeAttribute('src');
    }

    function openModal(imageUrl) {
      if (!imageUrl) return;
      modalImage.setAttribute('src', imageUrl);
      modal.hidden = false;
      document.documentElement.classList.add('customer-reviews-modal-open');
    }

    mediaButtons.forEach(function (button) {
      button.addEventListener('click', function () {
        openModal(button.getAttribute('data-full-image'));
      });
    });

    modalCloseButtons.forEach(function (button) {
      button.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && !modal.hidden) {
        closeModal();
      }
    });
  }

  function boot() {
    document.querySelectorAll('.wp-block-noyona-customer-reviews').forEach(initCustomerReviews);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
