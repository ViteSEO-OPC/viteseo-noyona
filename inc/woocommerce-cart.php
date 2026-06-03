<?php
/**
 * Cart page logic for Noyona.
 *
 * - Enqueues cart-only stylesheet
 * - Removes cross-sells from the cart page
 * - Auto-submits the cart form on quantity change (small inline script)
 *
 * Shipping costs are computed by Noyona_Shipping (J&T zone × weight matrix) in
 * inc/woocommerce-shipping.php. No filter here overrides them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cart-only stylesheet.
 */
add_action( 'wp_enqueue_scripts', 'noyona_cart_enqueue_assets', 20 );
function noyona_cart_enqueue_assets() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	$cart_css_path = get_stylesheet_directory() . '/assets/css/noyona-cart.css';

	wp_enqueue_style(
		'noyona-cart',
		get_stylesheet_directory_uri() . '/assets/css/noyona-cart.css',
		array( 'woocom-ct-style', 'noyona-notices', 'woocom-ct-header' ),
		file_exists( $cart_css_path ) ? (string) filemtime( $cart_css_path ) : wp_get_theme()->get( 'Version' )
	);
}

/**
 * Remove cross-sells from the cart collaterals.
 * Keeps the summary area clean.
 */
add_action( 'wp', 'noyona_remove_cart_cross_sells' );
function noyona_remove_cart_cross_sells() {
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		remove_action( 'woocommerce_cart_collaterals', 'woocommerce_cross_sell_display' );
	}
}

/**
 * When checkout/preview guards redirect an empty cart back here, keep the
 * guard's specific notice and suppress WooCommerce's generic empty-cart copy.
 */
add_action( 'wp', 'noyona_suppress_duplicate_guard_empty_cart_notice' );
function noyona_suppress_duplicate_guard_empty_cart_notice() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}

	if ( ! function_exists( 'wc_get_notices' ) || ! function_exists( 'wc_empty_cart_message' ) ) {
		return;
	}

	$notices      = wc_get_notices( 'notice' );
	$guard_notice = __( 'Your cart is empty. Add items before continuing to checkout.', 'noyona' );

	foreach ( (array) $notices as $notice ) {
		$message = is_array( $notice ) && isset( $notice['notice'] )
			? wp_strip_all_tags( (string) $notice['notice'] )
			: wp_strip_all_tags( (string) $notice );

		if ( $message === $guard_notice ) {
			remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
			return;
		}
	}
}

/**
 * Change the "Proceed to checkout" button text.
 */
add_filter( 'woocommerce_order_button_text', 'noyona_checkout_button_text' );
function noyona_checkout_button_text( $text ) {
	return $text;
}

/**
 * Replace the default proceed-to-checkout button with a styled link.
 * We remove the default button and add our own via the same hook.
 */
remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20 );
add_action( 'woocommerce_proceed_to_checkout', 'noyona_proceed_to_checkout_button', 20 );
function noyona_proceed_to_checkout_button() {
	?>
	<a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="noyona-checkout-btn">
		Continue to Shipping
	</a>
	<?php
}

/**
 * Auto-submit cart form on quantity change.
 * Small inline script — no external JS file needed.
 */
add_action( 'wp_footer', 'noyona_cart_auto_update_script', 40 );
function noyona_cart_auto_update_script() {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	?>
	<script>
	(function () {
		var form = document.querySelector('.woocommerce-cart-form');
		if (!form) return;
		var cartErrorNoticeClass = 'woocommerce-error noyona-mini-cart-stock-notice';
		var cartErrorNoticeSelector = '.woocommerce-error[data-noyona-cart-error="1"]';
		var nativeErrorNoticeSelector = [
			'.noyona-cart-summary-card > .woocommerce-error:not([data-noyona-cart-error="1"])',
			'.woocommerce-cart .woocommerce-notices-wrapper .woocommerce-error:not([data-noyona-cart-error="1"])',
			'.woocommerce-cart .wp-block-woocommerce-store-notices .wc-block-components-notice-banner.is-error',
			'.woocommerce-cart .wc-block-components-notice-banner.is-error'
		].join(', ');

		var timer;
		form.addEventListener('change', function (e) {
			if (e.target.matches('input.qty')) {
				clearTimeout(timer);
				timer = setTimeout(function () {
					var btn = form.querySelector('[name="update_cart"]');
					if (btn) {
						btn.disabled = false;
						btn.removeAttribute('aria-disabled');
						btn.click();
					}
				}, 600);
			}
		});

		function normalizeCartErrorMessages(messages) {
			if (Array.isArray(messages)) {
				return messages.map(function (message) {
					return String(message || '').trim();
				}).filter(Boolean);
			}

			var message = String(messages || '').trim();
			return message ? [message] : [];
		}

		function isStockErrorMessage(message) {
			var text = String(message || '').toLowerCase();
			return text.indexOf('out of stock') !== -1
				|| text.indexOf('cannot be purchased') !== -1
				|| text.indexOf('not have enough stock') !== -1
				|| text.indexOf('sold out') !== -1;
		}

		function getNoticeMessages(notice) {
			if (!notice) return [];

			var items = Array.prototype.slice.call(notice.querySelectorAll('li'));
			if (items.length) {
				var itemMessages = items.map(function (item) {
					return String(item.textContent || '').trim();
				}).filter(Boolean);
				var nonStockMessages = itemMessages.filter(function (message) {
					return !isStockErrorMessage(message);
				});
				return nonStockMessages.length ? nonStockMessages : itemMessages;
			}

			var content = notice.querySelector('.wc-block-components-notice-banner__content');
			var text = String((content || notice).textContent || '').trim();
			return text ? [text] : [];
		}

		function findNativeCartErrorNotice() {
			var notices = Array.prototype.slice.call(document.querySelectorAll(nativeErrorNoticeSelector));
			return notices.find(function (notice) {
				return !notice.closest('.wc-block-mini-cart__drawer');
			}) || null;
		}

		function createCartErrorNotice() {
			var notice = document.createElement('ul');
			notice.className = cartErrorNoticeClass;
			notice.setAttribute('data-noyona-cart-error', '1');
			notice.setAttribute('role', 'alert');
			return notice;
		}

		function mountCartErrorNotice(existingNativeNotice) {
			var notice = document.querySelector(cartErrorNoticeSelector);
			if (notice && notice.tagName && notice.tagName.toLowerCase() === 'ul') {
				notice.className = cartErrorNoticeClass;
				return notice;
			}

			var nextNotice = createCartErrorNotice();
			var anchor = document.querySelector('.woocommerce-cart-form') || document.querySelector('.noyona-cart-summary-card');

			if (notice && notice.parentNode) {
				notice.parentNode.replaceChild(nextNotice, notice);
				return nextNotice;
			}

			if (existingNativeNotice && existingNativeNotice.parentNode) {
				if (existingNativeNotice.parentNode.classList && existingNativeNotice.parentNode.classList.contains('wp-block-woocommerce-store-notices')) {
					existingNativeNotice.remove();
				} else {
					existingNativeNotice.parentNode.replaceChild(nextNotice, existingNativeNotice);
					return nextNotice;
				}
			}

			if (anchor && anchor.parentNode) {
				anchor.parentNode.insertBefore(nextNotice, anchor);
			} else {
				document.body.prepend(nextNotice);
			}

			return nextNotice;
		}

		function showCartErrorNotice(messages) {
			var normalizedMessages = normalizeCartErrorMessages(messages);
			var existingNativeNotice = findNativeCartErrorNotice();
			var existing = mountCartErrorNotice(existingNativeNotice);

			existing.setAttribute('data-noyona-cart-error', '1');
			existing.setAttribute('role', 'alert');

			if (!normalizedMessages.length) {
				normalizedMessages = getNoticeMessages(existing);
			}
			if (!normalizedMessages.length) {
				normalizedMessages = ['<?php echo esc_js( __( 'Please remove sold out items before continuing to checkout.', 'noyona' ) ); ?>'];
			}

			existing.innerHTML = '';
			normalizedMessages.forEach(function (message) {
				var item = document.createElement('li');
				item.textContent = message;
				existing.appendChild(item);
			});
			existing.scrollIntoView({ behavior: 'smooth', block: 'start' });
		}

		function getFirstOutOfStockCartMessage() {
			var outOfStockItem = document.querySelector('.noyona-cart-item--out-of-stock');
			if (!outOfStockItem) return '';

			var nameNode = outOfStockItem.querySelector('.noyona-cart-item__name');
			var name = nameNode ? String(nameNode.textContent || '').trim() : '';
			if (!name) {
				return '<?php echo esc_js( __( 'Please remove sold out items before continuing to checkout.', 'noyona' ) ); ?>';
			}
			return '"' + name + '" is sold out — remove it to continue to checkout.';
		}

		document.addEventListener('click', function (e) {
			var checkoutBtn = e.target.closest('.noyona-checkout-btn');
			if (!checkoutBtn) return;

			var stockMessage = getFirstOutOfStockCartMessage();
			if (!stockMessage) return;

			e.preventDefault();
			showCartErrorNotice(stockMessage);
		});

		var serverErrorNotice = findNativeCartErrorNotice();
		if (serverErrorNotice) {
			showCartErrorNotice(getNoticeMessages(serverErrorNotice));
		}
	})();
	</script>
	<?php
}

/* ----- Expose stock status in Store API cart items ----- */
/**
 * Register stock status on each cart item in the WC Store API so the
 * mini-cart JS can badge out-of-stock products.  Data appears under
 * item.extensions.noyona.in_stock in the /wc/store/cart response.
 */
add_action( 'woocommerce_blocks_loaded', 'noyona_register_store_api_stock_data' );
function noyona_register_store_api_stock_data() {
	if ( ! function_exists( 'woocommerce_store_api_register_endpoint_data' ) ) {
		return;
	}
	// Using the literal 'cart-item' string instead of CartItemSchema::IDENTIFIER
	// so this works across WC versions whose StoreApi namespace differs.
	woocommerce_store_api_register_endpoint_data(
		array(
			'endpoint'        => 'cart-item',
			'namespace'       => 'noyona',
			'data_callback'   => static function ( $cart_item ) {
				$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
				return array(
					'in_stock' => $product && is_callable( array( $product, 'is_in_stock' ) )
						? (bool) $product->is_in_stock()
						: true,
				);
			},
			'schema_callback' => static function () {
				return array(
					'in_stock' => array(
						'description' => 'Whether the product is currently in stock.',
						'type'        => 'boolean',
						'readonly'    => true,
					),
				);
			},
			'schema_type'     => ARRAY_A,
		)
	);
}

/* ----- AJAX stock status for the mini-cart drawer ----- */
add_action( 'wp_ajax_noyona_cart_stock_status', 'noyona_cart_stock_status' );
add_action( 'wp_ajax_nopriv_noyona_cart_stock_status', 'noyona_cart_stock_status' );
function noyona_cart_stock_status() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		wp_send_json_success( array( 'items' => array() ) );
	}

	$items = array();
	foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product || ! is_callable( array( $product, 'is_in_stock' ) ) ) {
			continue;
		}

		$items[] = array(
			'key'        => (string) $cart_item_key,
			'product_id' => isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0,
			'name'       => $product->get_name(),
			'in_stock'   => (bool) $product->is_in_stock(),
		);
	}

	wp_send_json_success( array( 'items' => $items ) );
}

/* ----- Redirect checkout stock bypasses back to cart ----- */
add_action( 'template_redirect', 'noyona_redirect_checkout_with_stock_issues_to_cart', 4 );
function noyona_redirect_checkout_with_stock_issues_to_cart() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
		return;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
		return;
	}

	if ( function_exists( 'is_wc_endpoint_url' ) && ( is_wc_endpoint_url( 'order-pay' ) || is_wc_endpoint_url( 'order-received' ) ) ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
		return;
	}

	foreach ( WC()->cart->get_cart() as $cart_item ) {
		$product  = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		$quantity = isset( $cart_item['quantity'] ) ? (int) $cart_item['quantity'] : 0;

		if ( ! $product || ! is_callable( array( $product, 'is_in_stock' ) ) ) {
			continue;
		}

		$has_stock_issue = ! $product->is_in_stock()
			|| (
				$quantity > 0
				&& is_callable( array( $product, 'has_enough_stock' ) )
				&& ! $product->has_enough_stock( $quantity )
			);

		if ( $has_stock_issue ) {
			wp_safe_redirect( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) );
			exit;
		}
	}
}

/* ----- Suppress default WC out-of-stock cart error notices ----- */
/**
 * WooCommerce shows a toast/banner for out-of-stock items on the cart page.
 * We replace that with an inline badge on each product card instead, so
 * remove the generic notice to avoid duplication.
 */
add_filter( 'woocommerce_cart_item_removed_notice_type', '__return_empty_string' );
add_action( 'woocommerce_check_cart_items', 'noyona_suppress_stock_notices', 999 );
function noyona_suppress_stock_notices() {
	if ( ! function_exists( 'wc_clear_notices' ) ) {
		return;
	}
	$all_notices   = wc_get_notices();
	$error_notices = isset( $all_notices['error'] ) ? (array) $all_notices['error'] : array();
	if ( empty( $error_notices ) ) {
		return;
	}
	$filtered = array();
	foreach ( $error_notices as $notice ) {
		$message = is_array( $notice ) && isset( $notice['notice'] ) ? (string) $notice['notice'] : (string) $notice;
		// Keep non-stock-related errors; drop the generic "out of stock" / "not enough stock" messages.
		if ( false === stripos( $message, 'out of stock' ) && false === stripos( $message, 'cannot be purchased' ) && false === stripos( $message, 'not have enough stock' ) ) {
			$filtered[] = $notice;
		}
	}
	$all_notices['error'] = $filtered;
	wc_set_notices( $all_notices );
}

/* ----- Force mini-cart 'Checkout' link to /cart/ ----- */
add_filter( 'render_block', 'noyona_force_minicart_checkout_link', 30, 2 );
function noyona_force_minicart_checkout_link( $block_content, $block ) {
    if ( is_admin() || empty( $block['blockName'] ) ) {
        return $block_content;
    }

    if ( 'woocommerce/mini-cart-checkout-button-block' !== $block['blockName'] ) {
        return $block_content;
    }

    if ( false === strpos( (string) $block_content, 'wp-block-woocommerce-mini-cart-checkout-button-block' ) ) {
        return $block_content;
    }

    $checkout_url = esc_url( home_url( '/cart/' ) );
    $pattern      = '#<a\b[^>]*class=(["\'])[^"\']*\bwp-block-woocommerce-mini-cart-checkout-button-block\b[^"\']*\1[^>]*>#i';

    return preg_replace_callback(
        $pattern,
        function ( $matches ) use ( $checkout_url ) {
            $anchor = $matches[0];

            // Add our own data attribute so JS can always find this element
            // regardless of WC class name changes.
            if ( false === strpos( $anchor, 'data-noyona-checkout-btn' ) ) {
                $anchor = preg_replace( '#<a\b#i', '<a data-noyona-checkout-btn="1"', $anchor, 1 );
            }

            if ( preg_match( '#\bhref=(["\']).*?\1#i', $anchor ) ) {
                return preg_replace( '#\bhref=(["\']).*?\1#i', 'href="' . $checkout_url . '"', $anchor, 1 );
            }

            return preg_replace( '#<a\b#i', '<a href="' . $checkout_url . '"', $anchor, 1 );
        },
        (string) $block_content,
        1
    );
}

