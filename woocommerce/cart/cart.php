<?php
/**
 * Noyona — custom cart template.
 *
 * Overrides: woocommerce/templates/cart/cart.php
 * Renders cart items as styled cards with quantity selectors and remove links.
 * All cart functionality (update, remove, coupon) is handled by WooCommerce's
 * native form handler — no custom JS required.
 *
 * @see https://woocommerce.com/document/template-structure/
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>

<form class="woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
	<?php do_action( 'woocommerce_before_cart_table' ); ?>

	<div class="noyona-cart-items">
		<?php
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
			$product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

			if (
				! $_product || ! $_product->exists() || $cart_item['quantity'] <= 0
				|| ! apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key )
			) {
				continue;
			}

			$product_permalink = apply_filters(
				'woocommerce_cart_item_permalink',
				$_product->is_visible() ? $_product->get_permalink( $cart_item ) : '',
				$cart_item,
				$cart_item_key
			);
			$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
			$thumbnail    = apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key );
			$item_price   = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $_product ), $cart_item, $cart_item_key );
			$item_class   = apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key );
			?>

			<div class="noyona-cart-item <?php echo esc_attr( $item_class ); ?>" data-cart-item-key="<?php echo esc_attr( $cart_item_key ); ?>">
				<div class="noyona-cart-item__image">
					<?php
					if ( $product_permalink ) {
						printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), $thumbnail );
					} else {
						echo $thumbnail; // phpcs:ignore
					}
					?>
				</div>

				<div class="noyona-cart-item__details">
					<div class="noyona-cart-item__name">
						<?php
						if ( $product_permalink ) {
							printf( '<a href="%s">%s</a>', esc_url( $product_permalink ), wp_kses_post( $product_name ) );
						} else {
							echo wp_kses_post( $product_name );
						}
						?>
					</div>

					<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore ?>

					<div class="noyona-cart-item__price"><?php echo $item_price; // phpcs:ignore ?></div>

					<div class="noyona-cart-item__actions">
						<div class="noyona-cart-item__qty">
							<span class="noyona-qty-label">Qty:</span>
							<?php
							if ( $_product->is_sold_individually() ) {
								$qty_html = sprintf( '1 <input type="hidden" name="cart[%s][qty]" value="1" />', $cart_item_key );
							} else {
								$qty_html = woocommerce_quantity_input(
									array(
										'input_name'   => "cart[{$cart_item_key}][qty]",
										'input_value'  => $cart_item['quantity'],
										'max_value'    => $_product->get_max_purchase_quantity(),
										'min_value'    => '0',
										'product_name' => $_product->get_name(),
									),
									$_product,
									false
								);
							}
							echo apply_filters( 'woocommerce_cart_item_quantity', $qty_html, $cart_item_key, $cart_item ); // phpcs:ignore
							?>
						</div>

						<?php
						echo apply_filters(
							'woocommerce_cart_item_remove_link',
							sprintf(
								'<a href="%s" class="noyona-remove-item" aria-label="%s" data-product_id="%s" data-product_sku="%s"><i class="fa-regular fa-trash-can"></i></a>',
								esc_url( wc_get_cart_remove_url( $cart_item_key ) ),
								/* translators: %s: product name */
								esc_attr( sprintf( __( 'Remove %s from cart', 'woocommerce' ), wp_strip_all_tags( $_product->get_name() ) ) ),
								esc_attr( $product_id ),
								esc_attr( $_product->get_sku() )
							),
							$cart_item_key
						);
						?>
					</div>
				</div>
			</div>
			<?php
		}
		?>
	</div>

	<button type="submit" class="noyona-update-cart" name="update_cart" value="<?php esc_attr_e( 'Update cart', 'woocommerce' ); ?>" disabled aria-disabled="true">
		<?php esc_html_e( 'Update cart', 'woocommerce' ); ?>
	</button>

	<?php do_action( 'woocommerce_cart_contents' ); ?>
	<?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
	<?php do_action( 'woocommerce_after_cart_table' ); ?>
</form>

<div class="cart-collaterals">
	<?php do_action( 'woocommerce_cart_collaterals' ); ?>
</div>

<?php do_action( 'woocommerce_after_cart' ); ?>
