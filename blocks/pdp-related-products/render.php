<?php
/**
 * PDP custom related products block.
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_product' ) ) {
	return;
}

$defaults = array(
	'heading'    => __( 'You Might Also Like', 'viteseo-noyona-childtheme' ),
	'subheading' => __( 'Complete your look with these favorites', 'viteseo-noyona-childtheme' ),
	'viewAllText'=> __( 'View All', 'viteseo-noyona-childtheme' ),
	'viewAllUrl' => '/shop/',
	'limit'      => 3,
);

$atts      = wp_parse_args( is_array( $attributes ) ? $attributes : array(), $defaults );
$heading   = trim( (string) $atts['heading'] );
$subheading = trim( (string) $atts['subheading'] );
$view_text = trim( (string) $atts['viewAllText'] );
$view_url  = trim( (string) $atts['viewAllUrl'] );
$limit     = max( 1, (int) $atts['limit'] );

$current_id = get_the_ID();
$product    = wc_get_product( $current_id );
if ( ! $product ) {
	return;
}

$related_ids = wc_get_related_products( $product->get_id(), $limit );
if ( empty( $related_ids ) ) {
	$related_ids = wc_get_products(
		array(
			'status'  => 'publish',
			'limit'   => $limit,
			'exclude' => array( $product->get_id() ),
			'return'  => 'ids',
			'orderby' => 'date',
			'order'   => 'DESC',
		)
	);
}

if ( empty( $related_ids ) ) {
	return;
}
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'noyona-pdp-related' ) ); ?>>
	<div class="noyona-pdp-related__head">
		<div class="noyona-pdp-related__head-copy">
			<?php if ( '' !== $heading ) : ?>
				<h2 class="noyona-pdp-related__title"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== $subheading ) : ?>
				<p class="noyona-pdp-related__subtitle"><?php echo esc_html( $subheading ); ?></p>
			<?php endif; ?>
		</div>
		<?php if ( '' !== $view_text ) : ?>
			<a class="noyona-pdp-related__view-all" href="<?php echo esc_url( '' !== $view_url ? $view_url : home_url( '/shop/' ) ); ?>">
				<?php echo esc_html( $view_text ); ?>
				<span aria-hidden="true">→</span>
			</a>
		<?php endif; ?>
	</div>

	<div class="noyona-pdp-related__grid">
		<?php foreach ( $related_ids as $related_id ) : ?>
			<?php
			$related = wc_get_product( (int) $related_id );
			if ( ! $related ) {
				continue;
			}
			$item_link  = get_permalink( $related->get_id() );
			$image_html = $related->get_image( 'large', array( 'loading' => 'lazy', 'decoding' => 'async' ) );
			$price_html = $related->get_price_html();
			$title      = $related->get_name();

			$short = $related->get_short_description();
			if ( '' === trim( (string) $short ) ) {
				$short = get_the_excerpt( $related->get_id() );
			}
			$short = wp_trim_words( wp_strip_all_tags( (string) $short ), 15 );

			$categories = wc_get_product_category_list( $related->get_id(), ',', '', '' );
			$category_name = '';
			if ( '' !== $categories ) {
				$pieces = explode( ',', wp_strip_all_tags( $categories ) );
				$category_name = isset( $pieces[0] ) ? trim( (string) $pieces[0] ) : '';
			}

			$rating_avg   = (float) $related->get_average_rating();
			$rating_count = (int) $related->get_rating_count();
			$rating_label = number_format_i18n( max( 0, $rating_avg ), 1 );

			$cart_url = $related->add_to_cart_url();
			$can_ajax = $related->supports( 'ajax_add_to_cart' ) && $related->is_type( 'simple' ) && $related->is_purchasable() && $related->is_in_stock();
			$cart_classes = 'noyona-pdp-related__cart-btn';
			if ( $can_ajax ) {
				$cart_classes .= ' noyona-pdp-related__cart-btn--ajax';
			}
			?>
			<article class="noyona-pdp-related__card">
				<a href="<?php echo esc_url( $item_link ); ?>" class="noyona-pdp-related__image-wrap">
					<?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</a>
				<div class="noyona-pdp-related__body">
					<h3 class="noyona-pdp-related__item-title">
						<a href="<?php echo esc_url( $item_link ); ?>"><?php echo esc_html( $title ); ?></a>
					</h3>

					<?php if ( '' !== $category_name ) : ?>
						<span class="noyona-pdp-related__tag"><?php echo esc_html( $category_name ); ?></span>
					<?php endif; ?>

					<?php if ( '' !== $short ) : ?>
						<p class="noyona-pdp-related__desc"><?php echo esc_html( $short ); ?></p>
					<?php endif; ?>

					<?php if ( '' !== $price_html ) : ?>
						<div class="noyona-pdp-related__price"><?php echo wp_kses_post( $price_html ); ?></div>
					<?php endif; ?>

					<div class="noyona-pdp-related__rating">
						<span class="noyona-pdp-related__stars" aria-hidden="true">★★★★★</span>
						<span class="noyona-pdp-related__rating-text">
							<?php echo esc_html( $rating_label ); ?>/5
							<?php if ( $rating_count > 0 ) : ?>
								<?php echo esc_html( sprintf( __( '(%d reviews)', 'viteseo-noyona-childtheme' ), $rating_count ) ); ?>
							<?php else : ?>
								<?php esc_html_e( '(No reviews yet)', 'viteseo-noyona-childtheme' ); ?>
							<?php endif; ?>
						</span>
					</div>
				</div>

				<?php if ( $can_ajax ) : ?>
					<button
						type="button"
						class="<?php echo esc_attr( $cart_classes ); ?>"
						data-product_id="<?php echo esc_attr( $related->get_id() ); ?>"
						data-product_sku="<?php echo esc_attr( $related->get_sku() ); ?>"
						data-quantity="1"
						data-cart-url="<?php echo esc_url( $cart_url ); ?>"
						aria-label="<?php echo esc_attr( sprintf( __( 'Add %s to cart', 'viteseo-noyona-childtheme' ), $title ) ); ?>"
					>
						<span class="screen-reader-text"><?php esc_html_e( 'Add to cart', 'viteseo-noyona-childtheme' ); ?></span>
					</button>
				<?php else : ?>
					<a
						href="<?php echo esc_url( $item_link ); ?>"
						class="<?php echo esc_attr( $cart_classes ); ?>"
						aria-label="<?php echo esc_attr( sprintf( __( 'View %s', 'viteseo-noyona-childtheme' ), $title ) ); ?>"
					>
						<span class="screen-reader-text"><?php esc_html_e( 'View product', 'viteseo-noyona-childtheme' ); ?></span>
					</a>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
