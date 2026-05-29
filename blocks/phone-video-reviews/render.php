<?php
if (!defined('ABSPATH')) {
  exit;
}

$heading = isset($attributes['heading']) ? trim((string) $attributes['heading']) : '';
$subheading = isset($attributes['subheading']) ? trim((string) $attributes['subheading']) : '';
$cards = is_array($attributes['cards'] ?? null) ? $attributes['cards'] : [];
$button_text = isset($attributes['buttonText']) ? trim((string) $attributes['buttonText']) : '';
$button_url = isset($attributes['buttonUrl']) ? trim((string) $attributes['buttonUrl']) : '';
$allowed_button_align = array('left', 'center', 'right');
$button_align_raw = isset($attributes['buttonAlign']) ? (string) $attributes['buttonAlign'] : 'center';
$button_align = in_array($button_align_raw, $allowed_button_align, true) ? $button_align_raw : 'center';
$thumbnail_urls = [
  trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/phone_1.webp',
  trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/phone_2.webp',
  trailingslashit(get_stylesheet_directory_uri()) . 'assets/images/phone_3.webp',
];

if (!function_exists('noyona_phone_reviews_add_query_args')) {
  function noyona_phone_reviews_add_query_args($url, $args) {
    foreach ($args as $key => $value) {
      $url = add_query_arg($key, $value, $url);
    }

    return $url;
  }
}

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
      <?php foreach ($cards as $card_index => $card):
        $label = isset($card['label']) ? trim((string) $card['label']) : '';
        $videoUrl = isset($card['videoUrl']) ? trim((string) $card['videoUrl']) : '';

        if ($videoUrl === '') {
          continue;
        }

        $video_id = '';
        $is_youtube = false;
        $is_facebook = (strpos($videoUrl, 'facebook.com') !== false);

        // YouTube Detection
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
        } elseif (strpos($videoUrl, 'embed/') !== false && strpos($videoUrl, 'youtube') !== false) {
          $parts = explode('embed/', $videoUrl);
          if (isset($parts[1])) {
            $video_id = explode('?', $parts[1])[0];
          }
        }

        $embed_src_muted = '';
        $embed_src_sound = '';
        $aspect_ratio = 'portrait'; // Default
    
        if ($video_id) {
          $is_youtube = true;
          $base = 'https://www.youtube-nocookie.com/embed/' . rawurlencode($video_id);
          $params = [
            'enablejsapi' => '1',
            'autoplay' => '0',
            'mute' => '1',
            'playsinline' => '1',
            'controls' => '0',
            'disablekb' => '1',
            'modestbranding' => '1',
            'rel' => '0',
            'iv_load_policy' => '3',
            'fs' => '0',
            'loop' => '1',
            'playlist' => $video_id,
          ];
          $embed_src_muted = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
          $params['autoplay'] = '1';
          $params['mute'] = '1';
          $params['controls'] = '1';
          $embed_src_sound = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        } else {
          // Handle Generic/Facebook
          if (preg_match('/src="([^"]+)"/', $videoUrl, $match)) {
            $embed_src_muted = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
          } else {
            $embed_src_muted = $videoUrl;
          }

          // Facebook specific overrides for autoplay/mute
          if ($is_facebook) {
            $embed_src_muted = noyona_phone_reviews_add_query_args($embed_src_muted, [
              'autoplay' => 'false',
              'muted' => 'true',
              'show_text' => 'false',
            ]);

            // Browser autoplay policy is reliable only when Facebook starts muted.
            $embed_src_sound = noyona_phone_reviews_add_query_args($embed_src_muted, [
              'autoplay' => 'true',
              'muted' => 'true',
              'mute' => '1',
              'show_text' => 'false',
            ]);
          } else {
            $embed_src_sound = $embed_src_muted;
          }

          // Detect aspect ratio
          if (preg_match('/width=(\d+)&height=(\d+)/', $embed_src_muted, $matches)) {
            $w = (int) $matches[1];
            $h = (int) $matches[2];
            if ($w > $h) {
              $aspect_ratio = 'landscape';
            }
          }
        }

        $thumbnail_url = $thumbnail_urls[$card_index % count($thumbnail_urls)];
        $thumbnail_alt = $label !== '' ? $label : __('Video review thumbnail', 'childtheme');

        ?>
        <article class="phone-card" data-video-type="<?= $is_youtube ? 'youtube' : 'generic'; ?>"
          data-aspect="<?= esc_attr($aspect_ratio); ?>"
          data-embed-sound="<?= esc_attr($embed_src_sound); ?>">
          <div class="phone-card__shell">
            <div class="phone-card__screen">
              <img
                class="phone-card__thumbnail"
                src="<?= esc_url($thumbnail_url); ?>"
                alt="<?= esc_attr($thumbnail_alt); ?>"
                loading="lazy"
                decoding="async"
              />
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
    <div class="phone-reviews__dots" aria-label="<?php echo esc_attr__('Video review slides', 'childtheme'); ?>"></div>
  <?php endif; ?>

  <?php if ($button_text !== ''): ?>
    <div class="phone-reviews__cta phone-reviews__cta--<?= esc_attr($button_align); ?>">
      <a class="phone-reviews__button" href="<?= esc_url($button_url !== '' ? $button_url : '#'); ?>">
        <?= esc_html($button_text); ?>
      </a>
    </div>
  <?php endif; ?>

  <!-- Cinematic overlay (desktop click-through) -->
  <div class="phone-reviews__overlay" aria-hidden="true">
    <div class="phone-reviews__overlay-backdrop"></div>

    <div class="phone-reviews__overlay-shell">
      <button class="phone-reviews__overlay-close" type="button"
        aria-label="<?php esc_attr_e('Close video', 'childtheme'); ?>">
        Close
      </button>
      <div class="phone-reviews__overlay-screen">
        <div class="phone-reviews__overlay-tapcatcher" aria-hidden="true"></div>
        <iframe src="about:blank" title="" loading="lazy" allowfullscreen
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"></iframe>
      </div>
      <p class="phone-reviews__overlay-label" data-phone-overlay-title></p>
    </div>
  </div>
</div>