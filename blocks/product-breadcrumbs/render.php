<?php
/**
 * PDP breadcrumbs (Home / Shop / Category / Product).
 *
 * @package viteseo-noyona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product = wc_get_product( get_the_ID() );
if ( ! $product ) {
	return;
}

$shop_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'shop' ) : 0;
$shop_url = $shop_id > 0 ? get_permalink( $shop_id ) : home_url( '/shop/' );
$shop_label = $shop_id > 0 ? get_the_title( $shop_id ) : __( 'Shop', 'viteseo-noyona-childtheme' );

$cat_link = null;
$cat_name = '';
$terms    = get_the_terms( $product->get_id(), 'product_cat' );
if ( $terms && ! is_wp_error( $terms ) ) {
	$deepest = null;
	$max_d   = -1;
	foreach ( $terms as $term ) {
		$d   = 0;
		$cur = $term;
		while ( $cur && $cur->parent ) {
			$d++;
			$next = get_term( $cur->parent, 'product_cat' );
			if ( ! $next || is_wp_error( $next ) ) {
				break;
			}
			$cur = $next;
		}
		if ( $d > $max_d ) {
			$max_d   = $d;
			$deepest = $term;
		}
	}
	if ( ! $deepest ) {
		$deepest = $terms[0];
	}
	$cat_name = $deepest->name;
	$tlink    = get_term_link( $deepest );
	$cat_link = is_wp_error( $tlink ) ? null : $tlink;
}

$parts = array(
	array(
		'label' => __( 'Home', 'viteseo-noyona-childtheme' ),
		'url'   => home_url( '/' ),
	),
	array(
		'label' => $shop_label,
		'url'   => $shop_url,
	),
);

if ( $cat_link && $cat_name ) {
	$parts[] = array(
		'label' => $cat_name,
		'url'   => $cat_link,
	);
}

$parts[] = array(
	'label' => $product->get_name(),
	'url'   => '',
);

?>
<nav class="wp-block-noyona-product-breadcrumbs noyona-pdp-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'viteseo-noyona-childtheme' ); ?>">
	<ol class="noyona-pdp-breadcrumbs__list">
		<?php foreach ( $parts as $i => $p ) : ?>
			<li class="noyona-pdp-breadcrumbs__item">
				<?php if ( $p['url'] ) : ?>
					<a class="noyona-pdp-breadcrumbs__link" href="<?php echo esc_url( $p['url'] ); ?>"><?php echo esc_html( $p['label'] ); ?></a>
				<?php else : ?>
					<span class="noyona-pdp-breadcrumbs__current" aria-current="page"><?php echo esc_html( $p['label'] ); ?></span>
				<?php endif; ?>
			</li>
			<?php if ( $i < count( $parts ) - 1 ) : ?>
				<li class="noyona-pdp-breadcrumbs__sep" aria-hidden="true">/</li>
			<?php endif; ?>
		<?php endforeach; ?>
	</ol>
</nav>
