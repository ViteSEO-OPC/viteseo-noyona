<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ATTRIBUTES
 */
$section_title  = isset( $attributes['sectionTitle'] ) ? sanitize_text_field( $attributes['sectionTitle'] ) : 'Products';
$view_all_label = isset( $attributes['viewAllLabel'] ) ? sanitize_text_field( $attributes['viewAllLabel'] ) : 'Filter by type';
$view_all_url   = isset( $attributes['viewAllUrl'] ) ? esc_url_raw( $attributes['viewAllUrl'] ) : '#';
$items          = isset( $attributes['items'] ) && is_array( $attributes['items'] ) ? $attributes['items'] : [];
$desktop_cols   = isset( $attributes['desktopCols'] ) ? (int) $attributes['desktopCols'] : 3;
$button_label   = isset( $attributes['buttonLabel'] ) ? sanitize_text_field( $attributes['buttonLabel'] ) : 'View all';

if ( ! in_array( $desktop_cols, [ 3, 4, 5 ], true ) ) {
	$desktop_cols = 3;
}

if ( empty( $items ) ) {
	return '';
}

/**
 * LAYOUT CONFIG
 */
$cols = [
	'xs' => 1,
	'sm' => 2,
	'md' => min( 3, $desktop_cols ),
	'lg' => $desktop_cols,
	'xl' => $desktop_cols,
];
$bps = [ 'xs', 'sm', 'md', 'lg', 'xl' ];

$rowColsPieces = [];
foreach ( $bps as $bp ) {
	if ( ! empty( $cols[ $bp ] ) ) {
		$n = max( 1, (int) $cols[ $bp ] );
		$rowColsPieces[] = ( 'xs' === $bp ) ? "row-cols-$n" : "row-cols-$bp-$n";
	}
}
$rowColsClasses = 'row ' . implode( ' ', $rowColsPieces ) . ' g-3 g-md-4';

$autoplay       = false;
$interval       = 5000;
$pauseOnHover   = true;
$wrap           = true;
$showControls   = true;
$showIndicators = true;

$isCarousel = true;

$ariaLabel = $section_title ? $section_title : 'Product carousel';
$id        = 'product_' . uniqid();
$scope_id  = 'pscope_' . $id;

/**
 * CARD RENDERER
 */
$print_card = function( array $card ) {
	$title         = $card['title'] ?? '';
	$description   = $card['description'] ?? '';
	$price         = $card['price'] ?? '';
	$originalPrice = $card['originalPrice'] ?? '';
	$rating        = isset( $card['rating'] ) ? (float) $card['rating'] : 0;
	$ratingCount   = isset( $card['ratingCount'] ) ? (int) $card['ratingCount'] : 0;
	$image         = $card['image'] ?? '';
	$url           = $card['url'] ?? '#';

    // Generate stars
    $full_stars = floor( $rating );
    $half_star  = ( $rating - $full_stars ) >= 0.5;
    $empty_stars = 5 - $full_stars - ( $half_star ? 1 : 0 );
	?>
	<article class="product-card">
		<div class="product-card__body">
			<!-- Image area -->
			<div class="product-card__media">
				<a href="<?php echo esc_url( $url ); ?>">
					<?php if ( $image ) : ?>
						<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>" />
					<?php else : ?>
						<div class="product-card__thumb-placeholder" style="width:100%;height:100%;background:#eee;display:flex;align-items:center;justify-content:center;">
							<i class="far fa-image" style="font-size:2rem;color:#ccc;"></i>
						</div>
					<?php endif; ?>
				</a>
			</div>

			<!-- SVG path background for the card body -->
			<svg class="product-card__body-bg" viewBox="0 0 466 546" preserveAspectRatio="none" aria-hidden="true">
				<path d="M 24 0 H 442 A 24 24 0 0 1 466 24 V 422 A 24 24 0 0 1 442 446 H 402 A 24 24 0 0 0 380 470 V 522 A 24 24 0 0 1 354 546 H 24 A 24 24 0 0 1 0 522 V 24 A 24 24 0 0 1 24 0 Z"   fill="#fff" /> 
				<!-- stroke="red" stroke-width="2" -->
			</svg>

			<div class="product-card__body-inner">
				<header class="product-card__header">
					<h3 class="product-card__title">
						<a href="<?php echo esc_url( $url ); ?>" style="text-decoration:none;color:inherit;">
							<?php echo esc_html( $title ); ?>
						</a>
					</h3>
					<button class="product-card__favorite" type="button" aria-label="Add to wishlist">
						<i class="far fa-heart"></i>
					</button>
				</header>

				<p class="product-card__description">
					<?php echo esc_html( $description ); ?>
				</p>

				<div class="product-card__footer">
					<div class="product-card__pricing">
						<span class="product-card__price"><?php echo esc_html( $price ); ?></span>
						<?php if ( $originalPrice ) : ?>
							<span class="product-card__price--old"><?php echo esc_html( $originalPrice ); ?></span>
						<?php endif; ?>
					</div>

					<div class="product-card__rating">
						<span class="product-card__stars">
							<?php for ( $i = 0; $i < $full_stars; $i++ ) : ?><i class="fas fa-star"></i><?php endfor; ?>
							<?php if ( $half_star ) : ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
							<?php for ( $i = 0; $i < $empty_stars; $i++ ) : ?><i class="far fa-star"></i><?php endfor; ?>
						</span>
						<span class="product-card__rating-count">(<?php echo esc_html( $ratingCount ); ?>)</span>
					</div>
				</div>
			</div>

			<button class="product-card__cart" type="button" aria-label="Add to cart">
				<span class="product-card__cart-icon">
					<i class="fas fa-shopping-cart"></i>
				</span>
			</button>
		</div>
	</article>
	<?php
};

/**
 * BUILD SLIDES (desktop)
 */
$largest  = 1;
foreach ( $bps as $bp ) {
	if ( ! empty( $cols[ $bp ] ) ) {
		$largest = max( $largest, (int) $cols[ $bp ] );
	}
}
$perSlide = max( 1, $largest );
$slides   = array_chunk( $items, $perSlide );

$data = [
	'data-bs-ride'     => $autoplay ? 'carousel' : false,
	'data-bs-interval' => $autoplay ? $interval : false,
	'data-bs-pause'    => $pauseOnHover ? 'hover' : 'false',
	'data-bs-wrap'     => $wrap ? 'true' : 'false',
	'data-bs-touch'    => 'true',
];
$carouselDataAttr = '';
foreach ( $data as $k => $v ) {
	if ( false !== $v && null !== $v && '' !== $v ) {
		$carouselDataAttr .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
	}
}

// mobile slide IDs for indicators
$slide_ids = [];
foreach ( $items as $idx => $_ ) {
	$slide_ids[] = $id . '-s' . $idx;
}

ob_start();
?>

<section class="carousel-block discover-carousel child-block">
    <div class="discover-carousel__header-wrap">
        <div>
            <h2 class="discover-carousel__title">
                <?php echo esc_html( $section_title ); ?>
            </h2>
            <p class="discover-carousel__subtitle">
                Lorem ipsum dolor sit amet consectetur adipiscing elit. Dolor sit amet consectetur adipiscing elit quisque faucibus.
            </p>
        </div>

		<?php if ( $view_all_label ) : ?>
			<div class="discover-carousel__filter-wrap">
                <button class="discover-carousel__filter-btn">
                    <?php echo esc_html( $view_all_label ); ?>
                    <i class="fas fa-caret-down"></i>
                </button>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $isCarousel ) : ?>
		<?php $ariaLabel_esc = esc_attr( $ariaLabel ); ?>

		<!-- ===== Peek slider (all screens) ===== -->
		<div id="<?php echo esc_attr( $scope_id ); ?>"
			class="peek-shell"
			role="region"
			aria-roledescription="carousel"
			aria-label="<?php echo $ariaLabel_esc; ?>">

			<div class="peek-slider">
				<div class="peek-track">
					<?php foreach ( $items as $i => $card ) : ?>
						<div class="peek-slide" id="<?php echo esc_attr( $slide_ids[ $i ] ); ?>">
							<?php $print_card( $card ); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Prev / Next arrows (desktop only via CSS) -->
			<button type="button"
					class="peek-arrow peek-arrow--prev"
					aria-label="Previous products">
				<span class="peek-arrow__icon">
					<i class="fas fa-chevron-left" aria-hidden="true"></i>
				</span>
			</button>

			<button type="button"
					class="peek-arrow peek-arrow--next"
					aria-label="Next products">
				<span class="peek-arrow__icon">
					<i class="fas fa-chevron-right" aria-hidden="true"></i>
				</span>
			</button>

			<nav class="peek-indicators" aria-label="Carousel indicators">
				<?php foreach ( $items as $i => $_ ) : ?>
					<a class="pi-dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
					data-i="<?php echo (int) $i; ?>"
					href="#<?php echo esc_attr( $slide_ids[ $i ] ); ?>"
					aria-label="<?php printf( 'Go to slide %d', $i + 1 ); ?>"></a>
				<?php endforeach; ?>
			</nav>
		</div>


	<?php endif; ?>

	<div class="discover-carousel__cta-wrap">
		<a href="<?php echo esc_url( $view_all_url ); ?>"
		class="discover-carousel__cta-btn">
			<?php echo esc_html( $button_label ); ?>
		</a>
	</div>

</section>

<script>
/* Peek-slider behavior with arrows + dots (no radios) */
(function(){
  const scope  = document.getElementById('<?php echo esc_js( $scope_id ); ?>');
  if (!scope || scope.dataset.bound === '1') return;
  scope.dataset.bound = '1';

  const container = scope.querySelector('.peek-slider');   // scrollable element
  const track     = scope.querySelector('.peek-track');
  const slides    = Array.from(scope.querySelectorAll('.peek-slide'));
  const dots      = Array.from(scope.querySelectorAll('.pi-dot'));
  const prevBtn   = scope.querySelector('.peek-arrow--prev');
  const nextBtn   = scope.querySelector('.peek-arrow--next');

  if (!container || !slides.length) return;

  let currentIndex = 0;

  function setActive(idx) {
    currentIndex = idx;
    dots.forEach(d => d.classList.remove('is-active'));
    if (dots[idx]) dots[idx].classList.add('is-active');
  }

  function goTo(idx) {
    const total = slides.length;
    if (!total) return;

    // wrap for "infinite" feel
    if (idx < 0) idx = total - 1;
    if (idx >= total) idx = 0;

    const slide = slides[idx];

    // Scroll only the slider container horizontally
    const slideLeft  = slide.offsetLeft;
    const slideWidth = slide.offsetWidth;
    const viewport   = container.clientWidth;

    const targetScrollLeft = slideLeft - (viewport - slideWidth) / 2;

    container.scrollTo({
      left: targetScrollLeft,
      behavior: 'smooth'
    });

    setActive(idx);
  }

  // Dots click → jump to slide
  dots.forEach((dot, i) => {
    dot.addEventListener('click', function(e){
      e.preventDefault();
      goTo(i);
    }, { passive: false });
  });

  // Prev / next arrows
  if (prevBtn) {
    prevBtn.addEventListener('click', function(e){
      e.preventDefault();
      goTo(currentIndex - 1);
    });
  }
  if (nextBtn) {
    nextBtn.addEventListener('click', function(e){
      e.preventDefault();
      goTo(currentIndex + 1);
    });
  }

  // Update active dot based on which card is centered
  function setActiveByCenter() {
    const cRect = container.getBoundingClientRect();
    const cMid  = cRect.left + cRect.width / 2;
    let bestI = 0, bestDist = Infinity;

    slides.forEach((sl, i) => {
      const r = sl.getBoundingClientRect();
      const mid = r.left + r.width / 2;
      const d = Math.abs(mid - cMid);
      if (d < bestDist) {
        bestDist = d;
        bestI = i;
      }
    });

    setActive(bestI);
  }

  let ticking = false;
  const onScroll = () => {
    if (!ticking) {
      window.requestAnimationFrame(() => {
        setActiveByCenter();
        ticking = false;
      });
      ticking = true;
    }
  };

  container.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', setActiveByCenter);

  // Initial state
  setActiveByCenter();
})();
</script>

