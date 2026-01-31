<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$heading    = isset( $attributes['heading'] )    ? trim( (string) $attributes['heading'] )    : '';
$subheading = isset( $attributes['subheading'] ) ? trim( (string) $attributes['subheading'] ) : '';
$cards      = is_array( $attributes['cards'] ?? null ) ? $attributes['cards'] : [];

$carousel_autoplay         = ! empty( $attributes['carouselAutoPlay'] );
$carousel_autoplay_seconds = isset( $attributes['carouselAutoPlaySeconds'] ) ? (int) $attributes['carouselAutoPlaySeconds'] : 0;
if ( $carousel_autoplay_seconds < 0 ) {
  $carousel_autoplay_seconds = 0;
}

$align_class = isset( $attributes['align'] ) ? 'align' . $attributes['align'] : '';

$wrapper = get_block_wrapper_attributes( [
  'class' => 'phone-reviews ' . $align_class . ' child-block',
] );
?>

<div <?= $wrapper; ?>>
  <div class="phone-reviews__header">
    <?php if ( $heading ) : ?>
      <h2 class="phone-reviews__title"><?= esc_html( $heading ); ?></h2>
    <?php endif; ?>

    <?php if ( $subheading ) : ?>
      <p class="phone-reviews__sub"><?= wp_kses_post( $subheading ); ?></p>
    <?php endif; ?>
  </div>

  <?php if ( $cards ) : ?>
    <!-- Desktop: 3 phones -->
    <div class="phone-reviews__grid" data-phone-grid>
      <?php foreach ( $cards as $card ) :
        $label    = isset( $card['label'] )    ? trim( (string) $card['label'] )    : '';
        $videoUrl = isset( $card['videoUrl'] ) ? trim( (string) $card['videoUrl'] ) : '';

        if ( $videoUrl === '' ) {
          continue;
        }

        // Expect a YouTube embed or watch URL; if it's a plain watch URL, convert to embed quickly.
        $embed_src = $videoUrl;

        if ( strpos( $videoUrl, 'youtube.com/watch' ) !== false ) {
          $parts = wp_parse_url( $videoUrl );
          if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $qs );
            if ( ! empty( $qs['v'] ) ) {
              $embed_src = 'https://www.youtube.com/embed/' . rawurlencode( $qs['v'] );
            }
          }
        } elseif ( strpos( $videoUrl, 'youtu.be/' ) !== false ) {
          $parts = wp_parse_url( $videoUrl );
          if ( ! empty( $parts['path'] ) ) {
            $id = ltrim( $parts['path'], '/' );
            $embed_src = 'https://www.youtube.com/embed/' . rawurlencode( $id );
          }
        }
        ?>
        <article
          class="phone-card"
          data-embed-src="<?= esc_url( $embed_src ); ?>"
          data-video-label="<?= esc_attr( $label ?: 'Video review' ); ?>"
        >
          <div class="phone-card__shell">
            <div class="phone-card__screen">
              <iframe
                src="<?= esc_url( $embed_src ); ?>?rel=0"
                title="<?= esc_attr( $label ?: 'Video review' ); ?>"
                loading="lazy"
                allowfullscreen
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              ></iframe>
            </div>
          </div>
          <?php if ( $label ) : ?>
            <p class="phone-card__label"><?= esc_html( $label ); ?></p>
          <?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>

    <!-- Tablet: single video carousel -->
    <div
      class="phone-reviews__carousel"
      data-phone-carousel
      data-autoplay="<?= $carousel_autoplay ? '1' : '0'; ?>"
      data-autoplay-seconds="<?= esc_attr( $carousel_autoplay_seconds ); ?>"
    >
      <div class="phone-reviews__carousel-inner">
        <div class="phone-reviews__carousel-screen">
          <iframe
            src="about:blank"
            title=""
            loading="lazy"
            allowfullscreen
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          ></iframe>
        </div>

        <div class="phone-reviews__carousel-nav">
          <button
            type="button"
            class="phone-reviews__carousel-prev"
            aria-label="<?php esc_attr_e( 'Previous video', 'childtheme' ); ?>"
          >
            <span aria-hidden="true">‹</span>
          </button>

          <button
            type="button"
            class="phone-reviews__carousel-next"
            aria-label="<?php esc_attr_e( 'Next video', 'childtheme' ); ?>"
          >
            <span aria-hidden="true">›</span>
          </button>
        </div>

      </div>
      <p class="phone-reviews__carousel-label" data-phone-carousel-title></p>
    </div>
  <?php endif; ?>

  <!-- Cinematic overlay (desktop click-through) -->
  <div class="phone-reviews__overlay" aria-hidden="true">
    <div class="phone-reviews__overlay-backdrop"></div>

    <div class="phone-reviews__overlay-shell">
      <button class="phone-reviews__overlay-close" type="button" aria-label="<?php esc_attr_e( 'Close video', 'childtheme' ); ?>">
        ×
      </button>
      <div class="phone-reviews__overlay-screen">
        <iframe
          src="about:blank"
          title=""
          loading="lazy"
          allowfullscreen
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
        ></iframe>
      </div>
      <p class="phone-reviews__overlay-label" data-phone-overlay-title></p>
    </div>
  </div>
</div>

<script>
(function(){
  function initPhoneReviews(block){
    var cards    = block.querySelectorAll('.phone-card');
    var overlay  = block.querySelector('.phone-reviews__overlay');
    if (!cards.length || !overlay) return;

    var frame    = overlay.querySelector('iframe');
    var titleEl  = overlay.querySelector('[data-phone-overlay-title]');
    var backdrop = overlay.querySelector('.phone-reviews__overlay-backdrop');
    var closeBtn = overlay.querySelector('.phone-reviews__overlay-close');

    function openOverlay(card){
      var src = card.getAttribute('data-embed-src') || '';
      if (!src) {
        var inlineIframe = card.querySelector('iframe');
        if (inlineIframe) src = inlineIframe.src;
      }
      if (!src) return;

      src = src.replace(/(&|\?)autoplay=\d+/, '');
      var sep = src.indexOf('?') === -1 ? '?' : '&';
      frame.src = src + sep + 'autoplay=1';

      var label = card.getAttribute('data-video-label') || '';
      if (titleEl) titleEl.textContent = label;

      overlay.classList.add('is-open');
      document.documentElement.classList.add('phone-reviews--overlay-open');
    }

    function closeOverlay(){
      overlay.classList.remove('is-open');
      frame.src = '';
      document.documentElement.classList.remove('phone-reviews--overlay-open');
    }

    cards.forEach(function(card){
      card.addEventListener('click', function(e){
        if (e.target.closest('a, button')) return;
        e.preventDefault();
        openOverlay(card);
      });
    });

    if (backdrop){
      backdrop.addEventListener('click', function(e){
        e.preventDefault();
        closeOverlay();
      });
    }

    if (closeBtn){
      closeBtn.addEventListener('click', function(e){
        e.preventDefault();
        closeOverlay();
      });
    }

    document.addEventListener('keydown', function(e){
      if (e.key === 'Escape' && overlay.classList.contains('is-open')){
        closeOverlay();
      }
    });

    /* ===== Tablet carousel logic ===== */
    var carousel = block.querySelector('[data-phone-carousel]');
    if (carousel && cards.length){
      var carouselFrame = carousel.querySelector('iframe');
      var carouselTitle = carousel.querySelector('[data-phone-carousel-title]');
      var prevBtn       = carousel.querySelector('.phone-reviews__carousel-prev');
      var nextBtn       = carousel.querySelector('.phone-reviews__carousel-next');
      var autoplay      = carousel.getAttribute('data-autoplay') === '1';
      var autoplaySec   = parseInt(carousel.getAttribute('data-autoplay-seconds'), 10) || 0;

      var currentIndex  = 0;
      var timerId       = null;

      function loadFromCard(idx){
        if (!cards.length || !carouselFrame) return;

        currentIndex = (idx + cards.length) % cards.length;

        var card = cards[currentIndex];
        var src  = card.getAttribute('data-embed-src') || '';
        if (!src){
          var cardIframe = card.querySelector('iframe');
          if (cardIframe) src = cardIframe.src;
        }
        if (!src) return;

        src = src.replace(/(&|\?)autoplay=\d+/, '');
        var sep = src.indexOf('?') === -1 ? '?' : '&';
        carouselFrame.src = src + sep + 'autoplay=1';

        var label = card.getAttribute('data-video-label') || '';
        if (carouselTitle) carouselTitle.textContent = label;
      }

      function resetAutoplay(){
        if (!autoplay || !autoplaySec) return;
        if (timerId) window.clearTimeout(timerId);
        timerId = window.setTimeout(function(){
          loadFromCard(currentIndex + 1);
          resetAutoplay();
        }, autoplaySec * 1000);
      }

      if (prevBtn){
        prevBtn.addEventListener('click', function(e){
          e.preventDefault();
          loadFromCard(currentIndex - 1);
          resetAutoplay();
        });
      }

      if (nextBtn){
        nextBtn.addEventListener('click', function(e){
          e.preventDefault();
          loadFromCard(currentIndex + 1);
          resetAutoplay();
        });
      }

      // initialise first video
      loadFromCard(0);
      resetAutoplay();
    }
  }

  document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.phone-reviews').forEach(initPhoneReviews);
  });
})();
</script>
