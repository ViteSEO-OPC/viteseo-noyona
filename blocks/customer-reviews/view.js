(function () {
  function initCustomerReviews(block) {
    if (!block) return;

    var cardsWrap = block.querySelector('.customer-reviews-grid__cards');
    var cards = Array.prototype.slice.call(block.querySelectorAll('.customer-reviews-grid__card'));
    var readMoreButton = block.querySelector('[data-read-more]');
    var readMoreLabel = readMoreButton ? readMoreButton.querySelector('span') : null;
    var readMoreIcon = readMoreButton ? readMoreButton.querySelector('.customer-reviews-grid__read-more-icon') : null;
    var revealStep = readMoreButton ? parseInt(readMoreButton.getAttribute('data-initial-visible') || '3', 10) : 3;
    var sortSelect = block.querySelector('[data-review-sort]');
    var withPhotosButton = block.querySelector('[data-review-with-photos]');
    var showingLabel = block.querySelector('[data-review-showing]');
    var helpfulEndpoint = block.getAttribute('data-review-helpful-endpoint') || '';
    var helpfulNonce = block.getAttribute('data-review-helpful-nonce') || '';
    if (!Number.isFinite(revealStep) || revealStep < 1) {
      revealStep = 3;
    }
    var visibleCount = revealStep;
    var showOnlyWithPhotos = false;
    var expandLabel = readMoreButton ? (readMoreButton.getAttribute('data-expand-label') || 'Read More Reviews') : 'Read More Reviews';
    var collapseLabel = readMoreButton ? (readMoreButton.getAttribute('data-collapse-label') || 'Collapse') : 'Collapse';
    var cardItems = cards.map(function (card) {
      return {
        element: card,
        rating: parseFloat(card.getAttribute('data-review-rating') || '0') || 0,
        timestamp: parseInt(card.getAttribute('data-review-timestamp') || '0', 10) || 0,
        helpfulCount: parseInt(card.getAttribute('data-review-helpful-count') || '0', 10) || 0,
        hasPhotos: card.getAttribute('data-review-has-photos') === '1'
      };
    });

    function getVisibleItems() {
      var items = cardItems.slice();
      var mode = sortSelect ? (sortSelect.value || 'recent') : 'recent';

      if (showOnlyWithPhotos) {
        items = items.filter(function (item) {
          return item.hasPhotos;
        });
      }

      items.sort(function (a, b) {
        if (mode === 'highest') {
          if (b.rating !== a.rating) return b.rating - a.rating;
          return b.timestamp - a.timestamp;
        }
        if (mode === 'lowest') {
          if (a.rating !== b.rating) return a.rating - b.rating;
          return b.timestamp - a.timestamp;
        }
        if (mode === 'trustable') {
          if (b.helpfulCount !== a.helpfulCount) return b.helpfulCount - a.helpfulCount;
          return b.timestamp - a.timestamp;
        }
        return b.timestamp - a.timestamp;
      });

      return items;
    }

    function updateShowingLabel(visibleItems, shownCount) {
      if (!showingLabel) return;
      var labelWord = showingLabel.getAttribute('data-review-word') || 'reviews';
      var totalAttr = parseInt(showingLabel.getAttribute('data-total') || String(visibleItems.length), 10);
      var totalCount = showOnlyWithPhotos ? visibleItems.length : (Number.isFinite(totalAttr) ? totalAttr : visibleItems.length);
      showingLabel.textContent = 'Showing ' + shownCount + ' of ' + totalCount + ' ' + labelWord;
    }

    function syncVisibleCards() {
      var visibleItems = getVisibleItems();
      var shownCount = Math.min(visibleCount, visibleItems.length);

      if (cardsWrap) {
        visibleItems.forEach(function (item) {
          cardsWrap.appendChild(item.element);
        });
      }

      cards.forEach(function (card) {
        card.hidden = true;
      });

      for (var i = 0; i < shownCount; i++) {
        visibleItems[i].element.hidden = false;
      }

      updateShowingLabel(visibleItems, shownCount);

      if (readMoreButton) {
        if (visibleItems.length <= revealStep) {
          readMoreButton.hidden = true;
          return;
        }
        readMoreButton.hidden = false;
        var allVisible = visibleCount >= visibleItems.length;
        if (readMoreLabel) {
          readMoreLabel.textContent = allVisible ? collapseLabel : expandLabel;
        }
        if (readMoreIcon) {
          readMoreIcon.classList.remove('fa-angle-down', 'fa-angle-up');
          readMoreIcon.classList.add(allVisible ? 'fa-angle-up' : 'fa-angle-down');
        }
      }
    }

    if (cards.length) {
      syncVisibleCards();

      if (readMoreButton) {
        readMoreButton.addEventListener('click', function () {
          var activeCount = getVisibleItems().length;
          if (visibleCount >= activeCount) {
            visibleCount = revealStep;
          } else {
            visibleCount = Math.min(visibleCount + revealStep, activeCount);
          }
          syncVisibleCards();
        });
      }

      if (sortSelect) {
        sortSelect.addEventListener('change', function () {
          visibleCount = revealStep;
          syncVisibleCards();
        });
      }

      if (withPhotosButton) {
        withPhotosButton.addEventListener('click', function () {
          showOnlyWithPhotos = !showOnlyWithPhotos;
          withPhotosButton.classList.toggle('is-active', showOnlyWithPhotos);
          withPhotosButton.setAttribute('aria-pressed', showOnlyWithPhotos ? 'true' : 'false');
          visibleCount = revealStep;
          syncVisibleCards();
        });
      }
    } else if (readMoreButton) {
      readMoreButton.hidden = true;
    }

    var imageModal = block.querySelector('[data-review-modal]');
    var modalImage = block.querySelector('[data-review-modal-image]');
    var imageModalCloseButtons = block.querySelectorAll('[data-review-modal-close]');
    var mediaButtons = block.querySelectorAll('[data-review-media]');
    var reviewFormModal = block.querySelector('[data-review-form-modal]');
    var reviewFormOpenButtons = block.querySelectorAll('[data-review-form-open]');
    var reviewFormCloseButtons = block.querySelectorAll('[data-review-form-close]');
    var reviewLoginModal = block.querySelector('[data-review-login-modal]');
    var reviewLoginOpenButtons = block.querySelectorAll('[data-review-login-open]');
    var reviewLoginCloseButtons = block.querySelectorAll('[data-review-login-close]');

    if (reviewFormModal) {
      var reviewForm = reviewFormModal.querySelector('form.comment-form');
      if (reviewForm) {
        reviewForm.setAttribute('enctype', 'multipart/form-data');
        reviewForm.setAttribute('encoding', 'multipart/form-data');

        var ratingSelect = reviewForm.querySelector('#rating');
        var ratingStars = Array.prototype.slice.call(reviewForm.querySelectorAll('[data-rating-value]'));
        var uploadInput = reviewForm.querySelector('#noyona_review_images');
        var uploadLabel = reviewForm.querySelector('[data-review-upload-label]');
        var submitButton = reviewForm.querySelector('.customer-reviews-grid__submit');

        function paintRatingStars(value) {
          if (!ratingStars.length) return;
          var numeric = parseInt(value || '0', 10) || 0;
          ratingStars.forEach(function (star) {
            var starValue = parseInt(star.getAttribute('data-rating-value') || '0', 10) || 0;
            star.classList.toggle('is-active', starValue <= numeric);
          });
        }

        if (ratingSelect && ratingStars.length) {
          paintRatingStars(ratingSelect.value);
          ratingStars.forEach(function (star) {
            var getStarValue = function () {
              return star.getAttribute('data-rating-value') || '';
            };

            star.addEventListener('click', function () {
              var value = getStarValue();
              ratingSelect.value = value;
              paintRatingStars(value);
              ratingSelect.dispatchEvent(new Event('change', { bubbles: true }));
            });

            star.addEventListener('mouseenter', function () {
              paintRatingStars(getStarValue());
            });

            star.addEventListener('focus', function () {
              paintRatingStars(getStarValue());
            });
          });

          var starsGroup = reviewForm.querySelector('[data-review-rating-stars]');
          if (starsGroup) {
            starsGroup.addEventListener('mouseleave', function () {
              paintRatingStars(ratingSelect.value);
            });
          }

          ratingStars.forEach(function (star) {
            star.addEventListener('blur', function () {
              paintRatingStars(ratingSelect.value);
            });
          });

          ratingSelect.addEventListener('change', function () {
            paintRatingStars(ratingSelect.value);
          });
        }

        if (uploadInput && uploadLabel) {
          uploadInput.addEventListener('change', function () {
            var count = uploadInput.files ? uploadInput.files.length : 0;
            if (count > 0) {
              uploadLabel.textContent = count === 1 ? '1 photo selected' : (String(count) + ' photos selected');
            } else {
              uploadLabel.textContent = 'Upload photos of your purchase';
            }
          });
        }

        function isRequiredFieldFilled(field) {
          if (!field || field.disabled || !field.required) return true;
          if (field.type === 'checkbox') return !!field.checked;
          if (field.type === 'radio') {
            var checked = reviewForm.querySelector('[name="' + field.name + '"]:checked');
            return !!checked;
          }
          return String(field.value || '').trim() !== '';
        }

        function syncSubmitState() {
          if (!submitButton) return;
          var requiredFields = Array.prototype.slice.call(reviewForm.querySelectorAll('[required]'));
          var valid = requiredFields.every(isRequiredFieldFilled);
          submitButton.disabled = !valid;
        }

        if (submitButton) {
          submitButton.disabled = true;
          reviewForm.addEventListener('input', syncSubmitState);
          reviewForm.addEventListener('change', syncSubmitState);
          syncSubmitState();
        }
      }
    }

    var helpfulButtons = block.querySelectorAll('[data-review-helpful]');
    if (helpfulButtons.length && helpfulEndpoint && helpfulNonce) {
      helpfulButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          if (button.classList.contains('is-loading')) return;
          var commentId = parseInt(button.getAttribute('data-review-comment-id') || '0', 10);
          if (!commentId) return;

          button.classList.add('is-loading');
          var payload = new URLSearchParams();
          payload.set('action', 'noyona_review_helpful_vote');
          payload.set('comment_id', String(commentId));
          payload.set('nonce', helpfulNonce);

          fetch(helpfulEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: payload.toString()
          })
            .then(function (response) {
              if (!response.ok) throw new Error('Request failed');
              return response.json();
            })
            .then(function (json) {
              if (!json || !json.success || !json.data) throw new Error('Invalid response');
              var count = parseInt(json.data.count || '0', 10) || 0;
              var voted = !!json.data.voted;
              var countNode = button.querySelector('[data-review-helpful-count]');
              if (countNode) {
                countNode.textContent = String(count);
                countNode.hidden = count < 1;
              }
              var card = button.closest('.customer-reviews-grid__card');
              if (card) {
                card.setAttribute('data-review-helpful-count', String(count));
                cardItems.forEach(function (item) {
                  if (item.element === card) {
                    item.helpfulCount = count;
                  }
                });
              }
              button.classList.toggle('is-voted', voted);
              button.setAttribute('aria-pressed', voted ? 'true' : 'false');
              if (sortSelect && sortSelect.value === 'trustable') {
                syncVisibleCards();
              }
            })
            .catch(function () {
              // Keep quiet on network failure to avoid disruptive UI.
            })
            .finally(function () {
              button.classList.remove('is-loading');
            });
        });
      });
    }

    function syncModalScrollLock() {
      var imageOpen = imageModal && !imageModal.hidden;
      var formOpen = reviewFormModal && !reviewFormModal.hidden;
      var loginOpen = reviewLoginModal && !reviewLoginModal.hidden;
      document.documentElement.classList.toggle('customer-reviews-modal-open', !!(imageOpen || formOpen || loginOpen));
    }

    function closeImageModal() {
      if (!imageModal) return;
      imageModal.hidden = true;
      if (modalImage) {
        modalImage.removeAttribute('src');
      }
      syncModalScrollLock();
    }

    function openImageModal(imageUrl) {
      if (!imageModal || !modalImage || !imageUrl) return;
      modalImage.setAttribute('src', imageUrl);
      imageModal.hidden = false;
      syncModalScrollLock();
    }

    function closeReviewFormModal() {
      if (!reviewFormModal) return;
      reviewFormModal.hidden = true;
      syncModalScrollLock();
    }

    function openReviewFormModal() {
      if (!reviewFormModal) return;
      reviewFormModal.hidden = false;
      syncModalScrollLock();
      var firstField = reviewFormModal.querySelector('select, textarea, input, button');
      if (firstField && typeof firstField.focus === 'function') {
        window.setTimeout(function () {
          firstField.focus();
        }, 0);
      }
    }

    function closeReviewLoginModal() {
      if (!reviewLoginModal) return;
      reviewLoginModal.hidden = true;
      syncModalScrollLock();
    }

    function openReviewLoginModal() {
      if (!reviewLoginModal) return;
      reviewLoginModal.hidden = false;
      syncModalScrollLock();
      var firstField = reviewLoginModal.querySelector('input, button, a');
      if (firstField && typeof firstField.focus === 'function') {
        window.setTimeout(function () {
          firstField.focus();
        }, 0);
      }
    }

    if (imageModal && modalImage && mediaButtons.length) {
      mediaButtons.forEach(function (button) {
        button.addEventListener('click', function () {
          openImageModal(button.getAttribute('data-full-image'));
        });
      });

      imageModalCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeImageModal);
      });
    }

    if (reviewFormModal && reviewFormOpenButtons.length) {
      reviewFormOpenButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
          event.preventDefault();
          openReviewFormModal();
        });
      });
    }

    if (reviewFormModal && reviewFormCloseButtons.length) {
      reviewFormCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeReviewFormModal);
      });
    }

    if (reviewLoginModal && reviewLoginOpenButtons.length) {
      reviewLoginOpenButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
          event.preventDefault();
          openReviewLoginModal();
        });
      });
    }

    if (reviewLoginModal && reviewLoginCloseButtons.length) {
      reviewLoginCloseButtons.forEach(function (button) {
        button.addEventListener('click', closeReviewLoginModal);
      });
    }

    document.addEventListener('keydown', function (event) {
      if (event.key !== 'Escape') return;
      if (reviewFormModal && !reviewFormModal.hidden) {
        closeReviewFormModal();
        return;
      }
      if (reviewLoginModal && !reviewLoginModal.hidden) {
        closeReviewLoginModal();
        return;
      }
      if (imageModal && !imageModal.hidden) {
        closeImageModal();
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
