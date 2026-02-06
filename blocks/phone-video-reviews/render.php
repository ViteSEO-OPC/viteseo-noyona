<?php
if (!defined('ABSPATH')) {
  exit;
}

$heading = isset($attributes['heading']) ? trim((string) $attributes['heading']) : '';
$subheading = isset($attributes['subheading']) ? trim((string) $attributes['subheading']) : '';
$cards = is_array($attributes['cards'] ?? null) ? $attributes['cards'] : [];

$carousel_autoplay = !empty($attributes['carouselAutoPlay']);
$carousel_autoplay_seconds = isset($attributes['carouselAutoPlaySeconds']) ? (int) $attributes['carouselAutoPlaySeconds'] : 0;
if ($carousel_autoplay_seconds < 0) {
  $carousel_autoplay_seconds = 0;
}

$align_class = isset($attributes['align']) ? 'align' . $attributes['align'] : '';

$wrapper = get_block_wrapper_attributes([
  'class' => 'phone-reviews ' . $align_class . ' child-block',
]);
?>

<div <?= $wrapper; ?>>
  <div class="phone-reviews__header">
    <?php if ($heading): ?>
      <h2 class="phone-reviews__title">
        <?php
        // Highlight last word logic
        $heading_text = esc_html($heading);
        $words = explode(' ', $heading_text);
        if (count($words) > 1) {
          $last_word = array_pop($words);
          $heading_html = implode(' ', $words) . ' <span class="phone-reviews__title-accent">' . $last_word . '</span>';
        } else {
          $heading_html = $heading_text;
        }
        echo $heading_html;
        ?>
      </h2>
    <?php endif; ?>

    <?php if ($subheading): ?>
      <p class="phone-reviews__sub">
        <?= wp_kses_post($subheading); ?>
      </p>
    <?php endif; ?>
  </div>

  <?php if ($cards): ?>
    <!-- Desktop: 3 phones -->
    <div class="phone-reviews__grid" data-phone-grid>
      <?php foreach ($cards as $card):
        $label = isset($card['label']) ? trim((string) $card['label']) : '';
        $videoUrl = isset($card['videoUrl']) ? trim((string) $card['videoUrl']) : '';

        if ($videoUrl === '') {
          continue;
        }

        $video_id = '';
        if (strpos($videoUrl, 'youtube.com/watch') !== false) {
          $parts = wp_parse_url($videoUrl);
          if (!empty($parts['query'])) {
            parse_str($parts['query'], $qs);
            if (!empty($qs['v'])) {
              $video_id = $qs['v'];
            }
          }
        } elseif (strpos($videoUrl, 'youtu.be/') !== false) {
          $parts = wp_parse_url($videoUrl);
          if (!empty($parts['path'])) {
            $video_id = ltrim($parts['path'], '/');
          }
        } elseif (strpos($videoUrl, 'embed/') !== false) {
          // Try to extract ID from embed url
          $parts = explode('embed/', $videoUrl);
          if (isset($parts[1])) {
            $video_id = explode('?', $parts[1])[0];
          }
        }

        // Construct Autoplay Muted Loop Embed
        // controls=0, disablekb=1, modestbranding=1, rel=0, showinfo=0, iv_load_policy=3
        $embed_src = '';
        if ($video_id) {
          $base = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video_id);

          // Minimal UI params
          $params = [
            'enablejsapi' => '1',
            'autoplay' => '1',
            'mute' => '1',
            'playsinline' => '1',
            'controls' => '0',
            'disablekb' => '1',
            'modestbranding' => '1',
            'rel' => '0',
            'iv_load_policy' => '3',
            'fs' => '0',
          ];

          $embed_src_muted = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

          // Overlay (sound on)
          $params['mute'] = '0';
          $embed_src_sound = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);

          $embed_src = $embed_src_muted;
        } else {
          $embed_src = $videoUrl;
        }

        ?>
        <article class="phone-card" data-video-state="playing">
          <div class="phone-card__shell">
            <div class="phone-card__screen">
              <iframe src="<?= esc_url($embed_src_muted); ?>" data-embed-muted="<?= esc_attr($embed_src_muted); ?>"
                data-embed-sound="<?= esc_attr($embed_src_sound); ?>" title="<?= esc_attr($label ?: 'Video review'); ?>"
                loading="lazy" allowfullscreen
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                style="pointer-events: none;"></iframe>
              <button class="phone-card__toggle" aria-label="Pause video">
                <i class="fas fa-play"></i>
              </button>
            </div>
          </div>
          <?php if ($label): ?>
            <p class="phone-card__label">
              <?= esc_html($label); ?>
            </p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Tablet: single video carousel (Simplified for now, keeping original structure but hiding if needed) -->
    <div class="phone-reviews__carousel" data-phone-carousel data-autoplay="<?= $carousel_autoplay ? '1' : '0'; ?>"
      data-autoplay-seconds="<?= esc_attr($carousel_autoplay_seconds); ?>">
      <!-- ... (Carousel markup preserved for tablet fallbacks if maintained, or we can use the same inline logic) ... -->
      <!-- For brevity, I am keeping the carousel structure technically present but the JS below will focus on the Grid inline logic requested. 
           The user focused on the 'image' which implies the grid view. -->
      <div class="phone-reviews__carousel-inner">
        <div class="phone-reviews__carousel-screen">
          <iframe src="about:blank" title="" loading="lazy" allowfullscreen></iframe>
        </div>
        <div class="phone-reviews__carousel-nav">
          <button type="button" class="phone-reviews__carousel-prev"><span aria-hidden="true">‹</span></button>
          <button type="button" class="phone-reviews__carousel-next"><span aria-hidden="true">›</span></button>
        </div>
      </div>
    </div>

  <?php endif; ?>
  <!-- Cinematic overlay (desktop click-through) -->
  <div class="phone-reviews__overlay" aria-hidden="true">
    <div class="phone-reviews__overlay-backdrop"></div>

    <div class="phone-reviews__overlay-shell">
      <button class="phone-reviews__overlay-close" type="button"
        aria-label="<?php esc_attr_e('Close video', 'childtheme'); ?>">
        ×
      </button>
      <div class="phone-reviews__overlay-screen">
        <iframe src="about:blank" title="" loading="lazy" allowfullscreen
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
      </div>
      <p class="phone-reviews__overlay-label" data-phone-overlay-title></p>
    </div>
  </div>
</div>

<script>
  (function () {
    function initPhoneReviews(block) {
      /* 1. Grid Logic (Desktop) */
      const cards = block.querySelectorAll('.phone-card');
      const overlay = block.querySelector('.phone-reviews__overlay');

      // Overlay elements
      let frame, titleEl, backdrop, closeBtn;

      if (overlay) {
        frame = overlay.querySelector('iframe');
        titleEl = overlay.querySelector('[data-phone-overlay-title]');
        backdrop = overlay.querySelector('.phone-reviews__overlay-backdrop');
        closeBtn = overlay.querySelector('.phone-reviews__overlay-close');
      }

      function openOverlay(card) {
        if (!overlay || !frame) return;

        // Get raw video URL/ID logic
        // We need a way to get the original embed source without the autoplay param potentially
        // But currently the iframe src has autoplay=1. 
        // Let's grab the src from the card iframe.
        const cardIframe = card.querySelector('iframe');
        const src = cardIframe ? (cardIframe.dataset.embedSound || '') : '';
        if (!src) return;
        frame.src = src;

        const label = card.querySelector('.phone-card__label') ? card.querySelector('.phone-card__label').textContent : '';
        if (titleEl) titleEl.textContent = label;

        overlay.classList.add('is-open');
        document.documentElement.classList.add('phone-reviews--overlay-open');
      }

      function closeOverlay() {
        if (!overlay) return;
        overlay.classList.remove('is-open');
        if (frame) frame.src = ''; // Stop video
        document.documentElement.classList.remove('phone-reviews--overlay-open');
      }

      cards.forEach(card => {
        const iframe = card.querySelector('iframe');
        const toggleBtn = card.querySelector('.phone-card__toggle');
        const icon = toggleBtn ? toggleBtn.querySelector('i') : null;

        if (!iframe || !toggleBtn || !icon) return;

        // Initial state: Playing (Muted)
        card.dataset.videoState = 'playing';

        card.addEventListener('click', function (e) {
          // If User clicked the Toggle Button (Play/Pause)
          if (e.target.closest('.phone-card__toggle')) {
            e.preventDefault();
            e.stopPropagation(); // Prevent bubbling to card click

            const isPlaying = card.dataset.videoState === 'playing';
            if (isPlaying) {
              // Pause
              iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
              card.dataset.videoState = 'paused';
              icon.className = 'fas fa-play';
              toggleBtn.style.opacity = '1';
            } else {
              // Play
              iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
              card.dataset.videoState = 'playing';
              icon.className = 'fas fa-pause';
              toggleBtn.style.opacity = '0';
            }
            return;
          }

          // Otherwise, if clicked elsewhere on the card (and not a link/button not handled), Open Modal
          if (e.target.closest('a, button')) return;

          e.preventDefault();
          openOverlay(card);
        });

        // Hover effect for Pause icon
        card.addEventListener('mouseenter', function () {
          if (card.dataset.videoState === 'playing') {
            icon.className = 'fas fa-pause';
            toggleBtn.style.opacity = '1';
          }
        });

        card.addEventListener('mouseleave', function () {
          if (card.dataset.videoState === 'playing') {
            toggleBtn.style.opacity = '0';
          }
        });
      });

      // Overlay Listeners
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


      /* 2. Tablet Carousel Logic */
      var carousel = block.querySelector('[data-phone-carousel]');
      if (carousel && cards.length) {
        var carouselFrame = carousel.querySelector('iframe');
        var prevBtn = carousel.querySelector('.phone-reviews__carousel-prev');
        var nextBtn = carousel.querySelector('.phone-reviews__carousel-next');
        var autoplay = carousel.getAttribute('data-autoplay') === '1';
        var autoplaySec = parseInt(carousel.getAttribute('data-autoplay-seconds'), 10) || 0;

        var currentIndex = 0;
        var timerId = null;

        function loadFromCard(idx) {
          if (!cards.length || !carouselFrame) return;

          currentIndex = (idx + cards.length) % cards.length;

          var card = cards[currentIndex];
          var cardIframe = card.querySelector('iframe');
          var src = cardIframe ? cardIframe.src : '';
          if (!src) return;

          // Ensure carousel frame also plays
          carouselFrame.src = src;
        }

        function resetAutoplay() {
          if (!autoplay || !autoplaySec) return;
          if (timerId) window.clearTimeout(timerId);
          timerId = window.setTimeout(function () {
            loadFromCard(currentIndex + 1);
            resetAutoplay();
          }, autoplaySec * 1000);
        }

        if (prevBtn) {
          prevBtn.addEventListener('click', function (e) {
            e.preventDefault();
            loadFromCard(currentIndex - 1);
            resetAutoplay();
          });
        }

        if (nextBtn) {
          nextBtn.addEventListener('click', function (e) {
            e.preventDefault();
            loadFromCard(currentIndex + 1);
            resetAutoplay();
          });
        }

        // Initial load
        if (cards.length > 0) {
          loadFromCard(0);
          resetAutoplay();
        }
      }
    }

    document.addEventListener('DOMContentLoaded', function () {
      document.querySelectorAll('.phone-reviews').forEach(initPhoneReviews);
    });
  })();
</script>