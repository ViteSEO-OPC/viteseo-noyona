<?php
if (!defined('ABSPATH')) {
	exit;
}

/**
 * ATTRIBUTES
 */
$section_title = isset($attributes['sectionTitle']) ? sanitize_text_field($attributes['sectionTitle']) : 'Products';
$section_description = isset($attributes['sectionDescription']) ? sanitize_text_field($attributes['sectionDescription']) : '';
$view_all_label = isset($attributes['viewAllLabel']) ? sanitize_text_field($attributes['viewAllLabel']) : 'Filter by type';
$view_all_url = isset($attributes['viewAllUrl']) ? esc_url_raw($attributes['viewAllUrl']) : '#';
$items = isset($attributes['items']) && is_array($attributes['items']) ? $attributes['items'] : [];
$desktop_cols = isset($attributes['desktopCols']) ? (int) $attributes['desktopCols'] : 3;
$button_label = isset($attributes['buttonLabel']) ? sanitize_text_field($attributes['buttonLabel']) : 'View all';

if (!in_array($desktop_cols, [3, 4, 5], true)) {
	$desktop_cols = 3;
}

if (empty($items)) {
	return '';
}

/**
 * LAYOUT CONFIG
 */
$cols = [
	'xs' => 1,
	'sm' => 2,
	'md' => min(3, $desktop_cols),
	'lg' => $desktop_cols,
	'xl' => $desktop_cols,
];
$bps = ['xs', 'sm', 'md', 'lg', 'xl'];

$rowColsPieces = [];
foreach ($bps as $bp) {
	if (!empty($cols[$bp])) {
		$n = max(1, (int) $cols[$bp]);
		$rowColsPieces[] = ('xs' === $bp) ? "row-cols-$n" : "row-cols-$bp-$n";
	}
}
$rowColsClasses = 'row ' . implode(' ', $rowColsPieces) . ' g-3 g-md-4';

$autoplay = false;
$interval = 5000;
$pauseOnHover = true;
$wrap = true;
$showControls = true;
$showIndicators = true;

$isCarousel = true;

$ariaLabel = $section_title ? $section_title : 'Product carousel';
$id = 'product_' . uniqid();
$scope_id = 'pscope_' . $id;

/**
 * CARD RENDERER
 */
$print_card = function (array $card) {
	$title = $card['title'] ?? '';
	$description = $card['description'] ?? '';
	$price = $card['price'] ?? '';
	$originalPrice = $card['originalPrice'] ?? '';
	$rating = isset($card['rating']) ? (float) $card['rating'] : 0;
	$ratingCount = isset($card['ratingCount']) ? (int) $card['ratingCount'] : 0;
	$image = $card['image'] ?? '';
	$url = $card['url'] ?? '#';

	// Generate stars
	$full_stars = floor($rating);
	$half_star = ($rating - $full_stars) >= 0.5;
	$empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
	?>
	<article class="product-card">
		<div class="product-card__body">
			<!-- Image area -->
			<div class="product-card__media">
				<a href="<?php echo esc_url($url); ?>">
					<?php if ($image): ?>
						<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($title); ?>" />
					<?php else: ?>
						<div class="product-card__thumb-placeholder"
							style="width:100%;height:100%;background:#eee;display:flex;align-items:center;justify-content:center;">
							<i class="far fa-image" style="font-size:2rem;color:#ccc;"></i>
						</div>
					<?php endif; ?>
				</a>
			</div>

			<!-- SVG path background for the card body -->
			<svg class="product-card__body-bg" viewBox="0 0 466 546" preserveAspectRatio="none" aria-hidden="true">
				<path
					d="M 24 0 H 442 A 24 24 0 0 1 466 24 V 422 A 24 24 0 0 1 442 446 H 402 A 24 24 0 0 0 380 470 V 522 A 24 24 0 0 1 354 546 H 24 A 24 24 0 0 1 0 522 V 24 A 24 24 0 0 1 24 0 Z"
					fill="#fff" />
				<!-- stroke="red" stroke-width="2" -->
			</svg>

			<div class="product-card__body-inner" data-type="<?php echo esc_attr($card['type'] ?? 'face'); ?>"
				data-price="<?php echo (float) preg_replace('/[^0-9.]/', '', $price); ?>"
				data-rating="<?php echo (float) $rating; ?>">
				<header class="product-card__header">
					<h3 class="product-card__title">
						<a href="<?php echo esc_url($url); ?>" style="text-decoration:none;color:inherit;">
							<?php echo esc_html($title); ?>
						</a>
					</h3>
					<button class="product-card__favorite" type="button" aria-label="Add to wishlist">
						<i class="far fa-heart"></i>
					</button>
				</header>

				<p class="product-card__description">
					<?php echo esc_html($description); ?>
				</p>

				<div class="product-card__footer">
					<div class="product-card__pricing">
						<span class="product-card__price"><?php echo esc_html($price); ?></span>
						<?php if ($originalPrice): ?>
							<span class="product-card__price--old"><?php echo esc_html($originalPrice); ?></span>
						<?php endif; ?>
					</div>

					<div class="product-card__rating">
						<span class="product-card__stars">
							<?php for ($i = 0; $i < $full_stars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
							<?php if ($half_star): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
							<?php for ($i = 0; $i < $empty_stars; $i++): ?><i class="far fa-star"></i><?php endfor; ?>
						</span>
						<span class="product-card__rating-count">(<?php echo esc_html($ratingCount); ?>)</span>
					</div>
				</div>
			</div>

			<a class="product-card__cart" href="<?php echo esc_url($url); ?>" aria-label="Add to cart">
				<span class="product-card__cart-icon">
					<i class="fas fa-shopping-cart"></i>
				</span>
			</a>
		</div>
	</article>
	<?php
};

/**
 * BUILD SLIDES (desktop)
 */
$largest = 1;
foreach ($bps as $bp) {
	if (!empty($cols[$bp])) {
		$largest = max($largest, (int) $cols[$bp]);
	}
}
$perSlide = max(1, $largest);
$slides = array_chunk($items, $perSlide);

$data = [
	'data-bs-ride' => $autoplay ? 'carousel' : false,
	'data-bs-interval' => $autoplay ? $interval : false,
	'data-bs-pause' => $pauseOnHover ? 'hover' : 'false',
	'data-bs-wrap' => $wrap ? 'true' : 'false',
	'data-bs-touch' => 'true',
];
$carouselDataAttr = '';
foreach ($data as $k => $v) {
	if (false !== $v && null !== $v && '' !== $v) {
		$carouselDataAttr .= sprintf(' %s="%s"', esc_attr($k), esc_attr($v));
	}
}

// mobile slide IDs for indicators
$slide_ids = [];
foreach ($items as $idx => $_) {
	$slide_ids[] = $id . '-s' . $idx;
}

ob_start();
?>

<section id="<?php echo esc_attr($scope_id); ?>" class="carousel-block discover-carousel child-block alignwide">
	<div class="discover-carousel__header-wrap">
		<div>
			<h2 class="discover-carousel__title">
				<?php
				$section_title_html = esc_html($section_title);
				$pos_prod = stripos($section_title, 'Products');
				if ($pos_prod !== false) {
					$lbl = 'Products';
					$before = substr($section_title, 0, $pos_prod);
					$match = substr($section_title, $pos_prod, strlen($lbl));
					$after = substr($section_title, $pos_prod + strlen($lbl));
					$section_title_html = esc_html($before) . '<span class="discover-carousel__title-accent">' . esc_html($match) . '</span>' . esc_html($after);
				}
				echo $section_title_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</h2>
			<p class="discover-carousel__subtitle">
				<?php echo esc_html($section_description); ?>
			</p>
		</div>

		<?php if ($view_all_label): ?>
			<div class="discover-carousel__filter-wrap">
				<div class="discover-carousel__filter-dropdown">
					<button class="discover-carousel__filter-btn" type="button" aria-expanded="false">
						<span><?php echo esc_html($view_all_label); ?></span>
						<i class="fas fa-caret-down"></i>
					</button>
					<div class="discover-carousel__filter-menu">
						<button type="button" class="filter-item is-active" data-filter="all">All Products</button>

						<div class="filter-divider">Type</div>
						<div class="filter-group--types">
							<button type="button" class="filter-item" data-filter="type" data-value="eyes">Eyes</button>
							<button type="button" class="filter-item" data-filter="type" data-value="lips">Lips</button>
							<button type="button" class="filter-item" data-filter="type" data-value="face">Face</button>
							<button type="button" class="filter-item" data-filter="type" data-value="body">Body</button>
							<button type="button" class="filter-item" data-filter="type" data-value="hair">Hair</button>
						</div>

						<div class="filter-divider">Max Price</div>
						<div class="filter-range-group">
							<div class="range-labels">
								<span>₱0</span>
								<span id="price-val">₱1000</span>
							</div>
							<input type="range" class="filter-range" id="price-slider" min="0" max="1000" step="10"
								value="1000" data-filter="price-range">
						</div>

						<div class="filter-divider">Min Rating</div>
						<div class="filter-range-group">
							<div class="range-labels">
								<span id="rating-val">0★</span>
								<span>5★</span>
							</div>
							<input type="range" class="filter-range" id="rating-slider" min="0" max="5" step="0.1" value="0"
								data-filter="rating-range">
						</div>

						<div class="filter-footer">
							<button type="button" class="filter-reset-btn">Reset Filters</button>
						</div>
					</div>
				</div>
			</div>
		<?php endif; ?>
	</div>

	<?php if ($isCarousel): ?>
		<?php $ariaLabel_esc = esc_attr($ariaLabel); ?>

		<!-- ===== Peek slider (all screens) ===== -->
		<div class="peek-shell" role="region" aria-roledescription="carousel" aria-label="<?php echo $ariaLabel_esc; ?>">

			<!-- Empty State Message -->
			<div class="discover-carousel__empty-state" style="display: none;">
				<div class="empty-state-inner">
					<i class="fas fa-search"></i>
					<h3>No products found</h3>
					<p>Try adjusting your filters or search for something else.</p>
					<!-- <button type="button" class="filter-reset-btn">Clear all filters</button> -->
				</div>
			</div>

			<div class="peek-slider">
				<div class="peek-track">
					<?php foreach ($items as $i => $card): ?>
						<div class="peek-slide" id="<?php echo esc_attr($slide_ids[$i]); ?>">
							<?php $print_card($card); ?>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Prev / Next arrows (desktop only; positioned inside carousel) -->
			<div class="discover-carousel__arrows" aria-hidden="true">
				<button type="button" class="peek-arrow peek-arrow--prev" aria-label="Previous products">
					<span class="peek-arrow__icon">
						<i class="fas fa-chevron-left" aria-hidden="true"></i>
					</span>
				</button>
				<button type="button" class="peek-arrow peek-arrow--next" aria-label="Next products">
					<span class="peek-arrow__icon">
						<i class="fas fa-chevron-right" aria-hidden="true"></i>
					</span>
				</button>
			</div>

			<nav class="peek-indicators" aria-label="Carousel indicators">
				<?php foreach ($items as $i => $_): ?>
					<a class="pi-dot<?php echo 0 === $i ? ' is-active' : ''; ?>" data-i="<?php echo (int) $i; ?>"
						href="#<?php echo esc_attr($slide_ids[$i]); ?>"
						aria-label="<?php printf('Go to slide %d', $i + 1); ?>"></a>
				<?php endforeach; ?>
			</nav>
		</div>


	<?php endif; ?>

	<div class="discover-carousel__cta-wrap">
		<a href="<?php echo esc_url($view_all_url); ?>" class="discover-carousel__cta-btn">
			<?php echo esc_html($button_label); ?>
		</a>
	</div>

</section>

<script>
	/* Peek-slider behavior with arrows + dots (no radios) */
	(function () {
		const scope = document.getElementById('<?php echo esc_js($scope_id); ?>');
		if (!scope || scope.dataset.bound === '1') return;
		scope.dataset.bound = '1';

		const container = scope.querySelector('.peek-slider');   // scrollable element
		const track = scope.querySelector('.peek-track');
		const slides = Array.from(scope.querySelectorAll('.peek-slide'));
		const dots = Array.from(scope.querySelectorAll('.pi-dot'));
		const prevBtn = scope.querySelector('.peek-arrow--prev');
		const nextBtn = scope.querySelector('.peek-arrow--next');

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
			const slideLeft = slide.offsetLeft;
			const slideWidth = slide.offsetWidth;
			const viewport = container.clientWidth;

			const targetScrollLeft = slideLeft - (viewport - slideWidth) / 2;

			container.scrollTo({
				left: targetScrollLeft,
				behavior: 'smooth'
			});

			setActive(idx);
		}

		// Dots click → jump to slide
		dots.forEach((dot, i) => {
			dot.addEventListener('click', function (e) {
				e.preventDefault();
				goTo(i);
			}, { passive: false });
		});

		// Prev / next arrows
		if (prevBtn) {
			prevBtn.addEventListener('click', function (e) {
				e.preventDefault();
				goTo(currentIndex - 1);
			});
		}
		if (nextBtn) {
			nextBtn.addEventListener('click', function (e) {
				e.preventDefault();
				goTo(currentIndex + 1);
			});
		}

		// Update active dot based on which card is centered
		function setActiveByCenter() {
			const cRect = container.getBoundingClientRect();
			const cMid = cRect.left + cRect.width / 2;
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

		// Filtering State
		let currentType = 'all';
		let currentPrice = 1000;
		let currentRating = 0;

		const filterBtn = scope.querySelector('.discover-carousel__filter-btn');
		const filterBtnTextIcon = filterBtn ? filterBtn.querySelector('span') : null;
		const typeButtons = scope.querySelectorAll('.filter-item');
		const priceSlider = scope.querySelector('#price-slider');
		const ratingSlider = scope.querySelector('#rating-slider');
		const priceVal = scope.querySelector('#price-val');
		const ratingVal = scope.querySelector('#rating-val');
		const resetBtn = scope.querySelector('.filter-reset-btn');
		const arrowsWrap = scope.querySelector('.discover-carousel__arrows');
		const indicatorsWrap = scope.querySelector('.peek-indicators');
		const emptyState = scope.querySelector('.discover-carousel__empty-state');
		const sliderArea = scope.querySelector('.peek-slider');

		function updateFilters() {
			let visibleCount = 0;

			slides.forEach(slide => {
				const cardInner = slide.querySelector('.product-card__body-inner');
				if (!cardInner) return;

				const type = cardInner.dataset.type;
				const price = parseFloat(cardInner.dataset.price);
				const rating = parseFloat(cardInner.dataset.rating);

				const matchesType = (currentType === 'all' || type === currentType);
				const matchesPrice = (price <= currentPrice);
				const matchesRating = (rating >= currentRating);

				if (matchesType && matchesPrice && matchesRating) {
					slide.style.display = 'flex';
					slide.classList.add('peek-slide--visible');
					visibleCount++;
				} else {
					slide.style.display = 'none';
					slide.classList.remove('peek-slide--visible');
				}
			});

			// Toggle Empty State Message
			if (visibleCount === 0) {
				if (emptyState) emptyState.style.display = 'block';
				if (sliderArea) sliderArea.style.display = 'none';
				if (arrowsWrap) arrowsWrap.style.display = 'none';
				if (indicatorsWrap) indicatorsWrap.style.display = 'none';
			} else {
				if (emptyState) emptyState.style.display = 'none';
				if (sliderArea) sliderArea.style.display = 'block';

				if (track) {
					const containerWidth = container.clientWidth;
					const slideSample = slides.find(s => s.style.display !== 'none');
					const slideWidth = slideSample ? slideSample.offsetWidth : 450;
					const gap = 24;
					const totalWidth = (visibleCount * slideWidth) + ((visibleCount - 1) * gap);

					if (totalWidth <= containerWidth) {
						track.classList.add('is-centered');
						if (arrowsWrap) arrowsWrap.style.display = 'none';
						if (indicatorsWrap) indicatorsWrap.style.display = 'none';
					} else {
						track.classList.remove('is-centered');
						if (arrowsWrap) arrowsWrap.style.display = '';
						if (indicatorsWrap) indicatorsWrap.style.display = '';
					}
				}
			}

			dots.forEach((dot, idx) => {
				dot.style.display = slides[idx].style.display === 'none' ? 'none' : 'inline-block';
			});

			typeButtons.forEach(btn => {
				if (btn.dataset.filter === 'all' || btn.classList.contains('filter-reset-btn')) return;
				const filterVal = btn.dataset.value;
				const hasProducts = slides.some(s => {
					const ci = s.querySelector('.product-card__body-inner');
					return ci && ci.dataset.type === filterVal;
				});
				btn.disabled = !hasProducts;
			});

			container.scrollTo({ left: 0, behavior: 'auto' });
			setActiveByCenter();
		}

		typeButtons.forEach(btn => {
			btn.addEventListener('click', function () {
				if (this.dataset.filter !== 'type' && this.dataset.filter !== 'all') return;

				if (this.dataset.filter === 'all') {
					currentPrice = 1000;
					currentRating = 0;
					if (priceSlider) priceSlider.value = 1000;
					if (ratingSlider) ratingSlider.value = 0;
					if (priceVal) priceVal.textContent = '₱1000';
					if (ratingVal) ratingVal.textContent = '0★';
				}

				currentType = this.dataset.value || 'all';
				typeButtons.forEach(b => b.classList.remove('is-active'));
				this.classList.add('is-active');

				if (filterBtnTextIcon) filterBtnTextIcon.textContent = this.textContent;
				updateFilters();
			});
		});

		priceSlider?.addEventListener('input', function () {
			currentPrice = parseFloat(this.value);
			if (priceVal) priceVal.textContent = `₱${currentPrice}`;
			updateFilters();
		});

		ratingSlider?.addEventListener('input', function () {
			currentRating = parseFloat(this.value);
			if (ratingVal) ratingVal.textContent = `${currentRating}★`;
			updateFilters();
		});

		const allResetButtons = scope.querySelectorAll('.filter-reset-btn');
		allResetButtons.forEach(btn => {
			btn.addEventListener('click', function () {
				currentType = 'all';
				currentPrice = 1000;
				currentRating = 0;
				typeButtons.forEach(b => b.classList.remove('is-active'));
				const allBtn = Array.from(typeButtons).find(b => b.dataset.filter === 'all');
				if (allBtn) allBtn.classList.add('is-active');
				if (priceSlider) priceSlider.value = 1000;
				if (ratingSlider) ratingSlider.value = 0;
				if (priceVal) priceVal.textContent = '₱1000';
				if (ratingVal) ratingVal.textContent = '0★';
				if (filterBtnTextIcon) filterBtnTextIcon.textContent = 'All Products';
				updateFilters();
			});
		});

		// Favorite button toggle
		const favoriteButtons = scope.querySelectorAll('.product-card__favorite');
		favoriteButtons.forEach(btn => {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				this.classList.toggle('is-active');
				const icon = this.querySelector('i');
				if (this.classList.contains('is-active')) {
					icon.classList.remove('far');
					icon.classList.add('fas');
				} else {
					icon.classList.remove('fas');
					icon.classList.add('far');
				}
			});
		});

		// Initialize
		slides.forEach(s => s.classList.add('peek-slide--visible'));
		updateFilters();
	})();
</script>