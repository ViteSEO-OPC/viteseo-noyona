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
          $params['mute'] = '0';
          $params['controls'] = '1';
          $embed_src_sound = $base . '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        } else {
          // Handle Generic/Facebook
          if (preg_match('/src="([^"]+)"/', $videoUrl, $match)) {
            $embed_src_muted = $match[1];
          } else {
            $embed_src_muted = $videoUrl;
          }

          // Facebook specific overrides for autoplay/mute
          if ($is_facebook) {
            if (strpos($embed_src_muted, 'autoplay=') === false) {
              $embed_src_muted .= (strpos($embed_src_muted, '?') === false ? '?' : '&') . 'autoplay=false';
            }
            if (strpos($embed_src_muted, 'muted=') === false && strpos($embed_src_muted, 'mute=') === false) {
              $embed_src_muted .= '&muted=true';
            }

            // Unmuted for modal
            $embed_src_sound = str_replace(['muted=true', 'mute=true'], ['muted=false', 'mute=false'], $embed_src_muted);
            $embed_src_sound = str_replace('autoplay=false', 'autoplay=true', $embed_src_sound);
            $embed_src_sound = str_replace('autoplay=0', 'autoplay=1', $embed_src_sound);
            if (strpos($embed_src_sound, 'muted=') === false && strpos($embed_src_sound, 'mute=') === false) {
              $embed_src_sound .= '&muted=false';
            }
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

        ?>
        <article class="phone-card" data-video-state="playing" data-video-type="<?= $is_youtube ? 'youtube' : 'generic'; ?>"
          data-aspect="<?= esc_attr($aspect_ratio); ?>">
          <div class="phone-card__shell">
            <div class="phone-card__screen">
            <iframe
              src="<?= esc_url($embed_src_muted); ?>"
              data-embed-muted="<?= esc_attr($embed_src_muted); ?>"
              data-embed-sound="<?= esc_attr($embed_src_sound); ?>"
              title="<?= esc_attr($label ?: 'Video review'); ?>"
              loading="lazy"
              allowfullscreen
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              style="pointer-events: none;"
            ></iframe>
              <?php if ($is_youtube): ?>
                <button class="phone-card__toggle" aria-label="Pause video">
                  <i class="fas fa-pause"></i>
                </button>
              <?php endif; ?>
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
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
      </div>
      <p class="phone-reviews__overlay-label" data-phone-overlay-title></p>
    </div>
  </div>
</div>