<?php
/**
 * Checkout page logic for Noyona.
 *
 * - Enqueues checkout-only stylesheet
 * - Customises field placeholders and order
 * - Removes default headings (handled in template override)
 * - Inline JS for UX: scroll-to-error, notes toggle, review-order relay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'noyona_checkout_is_local_env' ) ) {
	/**
	 * Local/dev guard for temporary checkout bypass tools.
	 */
	function noyona_checkout_is_local_env() {
		$env_type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : '';
		if ( in_array( $env_type, array( 'local', 'development' ), true ) ) {
			return true;
		}

		$home = (string) home_url();
		return ( false !== strpos( $home, 'localhost' ) || false !== strpos( $home, '.local' ) || false !== strpos( $home, '127.0.0.1' ) );
	}
}

if ( ! function_exists( 'noyona_checkout_allow_done_preview_bypass' ) ) {
	/**
	 * Keep "done preview" bypass opt-in only.
	 *
	 * Default is disabled even in local/dev so normal checkout flow remains:
	 * cart -> details -> preview -> payment -> done.
	 *
	 * To enable locally for UI-only testing, define:
	 * NOYONA_ENABLE_DONE_PREVIEW_BYPASS = true
	 *
	 * @return bool
	 */
	function noyona_checkout_allow_done_preview_bypass() {
		$enabled_via_constant = defined( 'NOYONA_ENABLE_DONE_PREVIEW_BYPASS' ) && NOYONA_ENABLE_DONE_PREVIEW_BYPASS;
		$enabled              = (bool) apply_filters( 'noyona_checkout_allow_done_preview_bypass', $enabled_via_constant );

		return ( $enabled && function_exists( 'noyona_checkout_is_local_env' ) && noyona_checkout_is_local_env() );
	}
}

if ( ! function_exists( 'noyona_get_checkout_attempt_id' ) ) {
	/**
	 * Return the current checkout attempt id, rotating when the cart changes.
	 *
	 * This token is not authentication. It is an idempotency key that lets the
	 * server recognize duplicate Place Order submissions from the same checkout
	 * attempt.
	 *
	 * @return string
	 */
	function noyona_get_checkout_attempt_id() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : wp_hash( uniqid( 'noyona_checkout_', true ) );
		}

		$cart_hash         = ( WC()->cart && is_callable( array( WC()->cart, 'get_cart_hash' ) ) ) ? (string) WC()->cart->get_cart_hash() : '';
		$attempt_id        = (string) WC()->session->get( 'noyona_checkout_attempt_id', '' );
		$attempt_cart_hash = (string) WC()->session->get( 'noyona_checkout_attempt_cart_hash', '' );

		if ( '' === $attempt_id || $attempt_cart_hash !== $cart_hash ) {
			$attempt_id = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : wp_hash( uniqid( 'noyona_checkout_', true ) );
			WC()->session->set( 'noyona_checkout_attempt_id', $attempt_id );
			WC()->session->set( 'noyona_checkout_attempt_cart_hash', $cart_hash );
			WC()->session->set( 'noyona_checkout_attempt_order_id', 0 );
		}

		return $attempt_id;
	}
}

if ( ! function_exists( 'noyona_checkout_attempt_lock_key' ) ) {
	/**
	 * Build a transient key for a checkout attempt lock.
	 *
	 * @param string $attempt_id Checkout attempt id.
	 * @return string
	 */
	function noyona_checkout_attempt_lock_key( $attempt_id ) {
		return 'noyona_checkout_lock_' . hash( 'sha256', (string) $attempt_id );
	}
}

/**
 * Guard against external/plugin redirects to generic /thank-you page.
 *
 * If we receive /thank-you/?order_id=...&key=..., route back to Woo's native
 * order-received endpoint so our checkout thankyou override drives the UI.
 */
add_action( 'template_redirect', 'noyona_route_generic_thankyou_to_wc_order_received', 4 );
function noyona_route_generic_thankyou_to_wc_order_received() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( ! is_page( array( 'thank-you', 'thankyou' ) ) ) {
		return;
	}

	$order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order_key = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $order_id <= 0 || '' === $order_key ) {
		return;
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		return;
	}

	if ( $order->get_order_key() !== $order_key ) {
		return;
	}

	$target = $order->get_checkout_order_received_url();
	if ( $target ) {
		wp_safe_redirect( $target );
		exit;
	}
}

/**
 * Retire the standalone /preview/ page.
 *
 * The review step is now an in-page panel on /checkout/, so any direct hit on
 * /preview/ (old links, bookmarks, stepper back-links) is sent to /checkout/.
 */
add_action( 'template_redirect', 'noyona_redirect_preview_to_checkout', 2 );
function noyona_redirect_preview_to_checkout() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( ! is_page( 'preview' ) ) {
		return;
	}

	$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' );
	wp_safe_redirect( $checkout_url );
	exit;
}

/**
 * Build the My Account "Orders" URL that opens a specific order's details modal.
 *
 * The account orders panel (inc/shortcodes.php + noyona-order-tracking) groups
 * orders into filter tabs by status (to-pay, to-ship, to-receive, complete,
 * cancel-refund), paginates 10 per page (newest first), and renders one modal
 * per line item with id "noyona-account-order-modal-{order_id}-{item_id}",
 * opened via the matching URL hash (CSS :target). A bare hash to /orders/ won't
 * open the modal unless that order's row is actually rendered — which only
 * happens under the right `order_filter` tab and `orders_page`. This resolves
 * both so the deep link reliably opens the modal.
 *
 * @param WC_Order $order Order to deep-link to.
 * @return string Absolute URL with query args + modal hash, or '' on failure.
 */
function noyona_get_account_order_modal_url( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return '';
	}

	$orders_url = function_exists( 'wc_get_account_endpoint_url' )
		? wc_get_account_endpoint_url( 'orders' )
		: ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/my-account/' ) );

	// The panel renders one modal per product line item; target the first.
	$first_item_id = 0;
	foreach ( $order->get_items( 'line_item' ) as $line_item ) {
		if ( $line_item instanceof WC_Order_Item_Product ) {
			$first_item_id = (int) $line_item->get_id();
			break;
		}
	}

	if ( ! $first_item_id ) {
		return $orders_url;
	}

	// Map the order's status to its filter tab.
	$status          = sanitize_key( $order->get_status() );
	$filters         = function_exists( 'noyona_ot_get_order_status_filters' ) ? (array) noyona_ot_get_order_status_filters() : array();
	$filter_key      = '';
	$filter_statuses = array();
	foreach ( $filters as $key => $config ) {
		$statuses = isset( $config['statuses'] ) ? array_map( 'sanitize_key', (array) $config['statuses'] ) : array();
		if ( in_array( $status, $statuses, true ) ) {
			$filter_key      = (string) $key;
			$filter_statuses = $statuses;
			break;
		}
	}

	$query_args = array();
	if ( '' !== $filter_key ) {
		// Page = position among same-tab orders (newest first, 10/page).
		$orders_page = 1;
		$created     = $order->get_date_created();
		if ( function_exists( 'wc_get_orders' ) && $created instanceof WC_DateTime ) {
			$newer = wc_get_orders(
				array(
					'customer_id'  => (int) $order->get_user_id(),
					'status'       => $filter_statuses,
					'date_created' => '>' . (int) $created->getTimestamp(),
					'return'       => 'ids',
					'limit'        => -1,
				)
			);
			$newer_count = is_array( $newer ) ? count( $newer ) : 0;
			$orders_page = (int) floor( $newer_count / 10 ) + 1;
		}

		$query_args['order_filter'] = $filter_key;
		$query_args['orders_page']  = $orders_page;
	}

	$base = empty( $query_args ) ? $orders_url : add_query_arg( $query_args, $orders_url );

	return $base . '#noyona-account-order-modal-' . (int) $order->get_id() . '-' . $first_item_id;
}

/**
 * Safety net for PayMongo's redirect catcher (remediation plan Phase 3.3).
 *
 * The gateway's cynder_paymongo_catch_redirect() only handles the intent
 * statuses succeeded / processing / awaiting_payment_method / awaiting_next_action.
 * Any other status falls through the if/else with no redirect and no exit,
 * leaving the customer staring at a blank `?wc-api=` response. We hook the SAME
 * WooCommerce API action at a later priority: the plugin handler calls exit() on
 * every path it handles (success, awaiting, and all validation failures), so
 * once it runs PHP terminates and this never fires. It executes ONLY on that
 * unhandled fall-through, where we send the customer somewhere sensible with a
 * clear message instead of a blank screen.
 *
 * Implemented as a hook (not a plugin edit) so a PayMongo plugin update can't
 * wipe it. Resolving an already-paid order to the received page also makes it
 * safe even if a future plugin version returns without exit on a success path.
 */
add_action( 'woocommerce_api_cynder_paymongo_catch_redirect', 'noyona_paymongo_catch_redirect_fallback', 20 );
function noyona_paymongo_catch_redirect_fallback() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- mirrors the gateway's own GET-based redirect handler.
	$order_id  = isset( $_GET['order'] ) ? absint( wp_unslash( $_GET['order'] ) ) : 0;
	$order_key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
	// phpcs:enable WordPress.Security.NonceVerification.Recommended

	$order = null;
	if ( '' !== $order_key && function_exists( 'wc_get_order_id_by_order_key' ) ) {
		$resolved = wc_get_order_id_by_order_key( $order_key );
		if ( $resolved ) {
			$candidate = wc_get_order( $resolved );
			if ( $candidate instanceof WC_Order && ( 0 === $order_id || (int) $candidate->get_id() === $order_id ) ) {
				$order = $candidate;
			}
		}
	}

	if ( function_exists( 'wc_get_logger' ) ) {
		wc_get_logger()->log(
			'warning',
			'[Noyona Catch Redirect Fallback] Unhandled PayMongo intent status for order '
				. ( $order instanceof WC_Order ? $order->get_id() : 'unknown' )
				. '. Redirecting away from a blank page.'
		);
	}

	if ( $order instanceof WC_Order ) {
		// Already paid (e.g. webhook beat the redirect): send to the Done page.
		if ( $order->is_paid() ) {
			wp_safe_redirect( $order->get_checkout_order_received_url() );
			exit;
		}

		if ( function_exists( 'wc_add_notice' ) ) {
			wc_add_notice(
				__( 'We could not confirm your payment status. Your order is awaiting payment — please try again or choose another method.', 'noyona' ),
				'error'
			);
		}
		wp_safe_redirect( $order->get_checkout_payment_url() );
		exit;
	}

	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice( __( 'We could not verify your payment. Please try again.', 'noyona' ), 'error' );
	}
	wp_safe_redirect( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) );
	exit;
}

/**
 * AJAX: lightweight order payment status probe for order-received polling.
 *
 * Uses order ID + order key validation so guests can safely query their own
 * order status without forcing full-page refresh loops.
 */
add_action( 'wp_ajax_noyona_check_order_payment_status', 'noyona_check_order_payment_status' );
add_action( 'wp_ajax_nopriv_noyona_check_order_payment_status', 'noyona_check_order_payment_status' );
function noyona_check_order_payment_status() {
	$order_id  = isset( $_REQUEST['order_id'] ) ? absint( wp_unslash( $_REQUEST['order_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order_key = isset( $_REQUEST['order_key'] ) ? wc_clean( wp_unslash( $_REQUEST['order_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $order_id <= 0 || '' === $order_key ) {
		wp_send_json_error(
			array(
				'message' => 'Missing order reference.',
			),
			400
		);
	}

	$order = wc_get_order( $order_id );
	if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
		wp_send_json_error(
			array(
				'message' => 'Order not found.',
			),
			404
		);
	}

	if ( $order->get_order_key() !== $order_key ) {
		wp_send_json_error(
			array(
				'message' => 'Invalid order key.',
			),
			403
		);
	}

	$payment_context     = strtolower( trim( (string) $order->get_payment_method() . ' ' . (string) $order->get_payment_method_title() ) );
	$is_paymongo_qr      = ( false !== strpos( $payment_context, 'paymongo' ) && false !== strpos( $payment_context, 'qr' ) );
	$is_awaiting_payment = ( $is_paymongo_qr && ! $order->is_paid() && $order->has_status( array( 'pending', 'on-hold' ) ) );

	wp_send_json_success(
		array(
			'order_id'          => (int) $order->get_id(),
			'status'            => (string) $order->get_status(),
			'is_paid'           => (bool) $order->is_paid(),
			'is_paymongo_qr'    => (bool) $is_paymongo_qr,
			'awaiting_payment'  => (bool) $is_awaiting_payment,
		)
	);
}

/**
 * Whether an order was paid with a QR Ph style gateway.
 *
 * Matches both the PayMongo "QR Ph" gateway and the standalone QR PH plugin by
 * looking for "qr" in the payment method id/title, so the auto-cancel logic is
 * gateway-agnostic and survives the theme/plugin folder divergence.
 *
 * @param WC_Order|mixed $order Order instance.
 * @return bool
 */
function noyona_order_is_qr_payment( $order ) {
	if ( ! $order instanceof WC_Order ) {
		return false;
	}

	$context = strtolower( trim( (string) $order->get_payment_method() . ' ' . (string) $order->get_payment_method_title() ) );

	return ( false !== strpos( $context, 'qr' ) );
}

/**
 * Payment window, in seconds, for QR Ph orders before they auto-cancel.
 *
 * Defaults to 30 minutes to match the countdown shown on the order-received
 * page. Filterable via `noyona_qrph_cancel_timeout`.
 *
 * @return int
 */
function noyona_qrph_cancel_timeout_seconds() {
	return (int) apply_filters( 'noyona_qrph_cancel_timeout', 30 * MINUTE_IN_SECONDS );
}

/**
 * Schedule a one-off cancellation check when a QR Ph order is placed.
 *
 * PayMongo confirms paid QR Ph orders via webhook; an abandoned order would
 * otherwise sit on-hold indefinitely (WooCommerce hold-stock only auto-cancels
 * `pending`, never `on-hold`). We schedule a check ~30 minutes out and cancel
 * the order then if it is still unpaid.
 *
 * @param int            $order_id Order ID.
 * @param WC_Order|null  $order    Order instance (when provided by the hook).
 * @return void
 */
add_action( 'woocommerce_order_status_on-hold', 'noyona_qrph_schedule_cancel', 10, 2 );
add_action( 'woocommerce_order_status_pending', 'noyona_qrph_schedule_cancel', 10, 2 );
add_action( 'woocommerce_checkout_order_processed', 'noyona_qrph_schedule_cancel_on_checkout_processed', 20, 3 );

/**
 * Schedule QR Ph cancel check after checkout (new orders skip status transition hooks).
 *
 * @param int      $order_id    Order ID.
 * @param array    $posted_data Posted checkout data.
 * @param WC_Order $order       Created order.
 * @return void
 */
function noyona_qrph_schedule_cancel_on_checkout_processed( $order_id, $posted_data, $order ) {
	noyona_qrph_schedule_cancel( $order_id, $order );
}

function noyona_qrph_schedule_cancel( $order_id, $order = null ) {
	$order = $order instanceof WC_Order ? $order : wc_get_order( $order_id );
	if ( ! $order || ! noyona_order_is_qr_payment( $order ) || $order->is_paid() ) {
		return;
	}

	$hook  = 'noyona_qrph_maybe_cancel_order';
	$args  = array( (int) $order_id );
	$delay = noyona_qrph_cancel_timeout_seconds();

	if ( function_exists( 'as_schedule_single_action' ) && function_exists( 'as_next_scheduled_action' ) ) {
		if ( false === as_next_scheduled_action( $hook, $args, 'noyona' ) ) {
			as_schedule_single_action( time() + $delay, $hook, $args, 'noyona' );
		}
	} elseif ( ! wp_next_scheduled( $hook, $args ) ) {
		wp_schedule_single_event( time() + $delay, $hook, $args );
	}
}

/**
 * Clear a pending cancellation check once an order leaves the awaiting state.
 *
 * @param int $order_id Order ID.
 * @return void
 */
add_action( 'woocommerce_order_status_processing', 'noyona_qrph_unschedule_cancel', 10, 1 );
add_action( 'woocommerce_order_status_completed', 'noyona_qrph_unschedule_cancel', 10, 1 );
add_action( 'woocommerce_order_status_cancelled', 'noyona_qrph_unschedule_cancel', 10, 1 );
add_action( 'woocommerce_order_status_refunded', 'noyona_qrph_unschedule_cancel', 10, 1 );
function noyona_qrph_unschedule_cancel( $order_id ) {
	$hook = 'noyona_qrph_maybe_cancel_order';
	$args = array( (int) $order_id );

	if ( function_exists( 'as_unschedule_all_actions' ) ) {
		as_unschedule_all_actions( $hook, $args, 'noyona' );
	} elseif ( wp_next_scheduled( $hook, $args ) ) {
		wp_clear_scheduled_hook( $hook, $args );
	}
}

/**
 * Cancel an unpaid QR Ph order once its payment window has elapsed.
 *
 * @param int $order_id Order ID.
 * @return void
 */
add_action( 'noyona_qrph_maybe_cancel_order', 'noyona_qrph_maybe_cancel_order' );
function noyona_qrph_maybe_cancel_order( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order || ! noyona_order_is_qr_payment( $order ) ) {
		return;
	}

	// Paid (webhook confirmed) or already moved on — nothing to cancel.
	if ( $order->is_paid() || ! $order->has_status( array( 'pending', 'on-hold' ) ) ) {
		return;
	}

	$minutes = (int) round( noyona_qrph_cancel_timeout_seconds() / MINUTE_IN_SECONDS );

	$order->update_status(
		'cancelled',
		sprintf(
			/* translators: %d: payment window in minutes. */
			__( 'QR Ph payment window (%d minutes) elapsed without a confirmed payment. Order auto-cancelled.', 'noyona-childtheme' ),
			$minutes
		)
	);
}

/**
 * AJAX: persist checkout form fields into the WC customer session so that the
 * server-side guard on /preview/ sees populated values and doesn't redirect
 * the user back to /checkout/.
 */
add_action( 'wp_ajax_noyona_sync_checkout_fields', 'noyona_sync_checkout_fields' );
add_action( 'wp_ajax_nopriv_noyona_sync_checkout_fields', 'noyona_sync_checkout_fields' );
function noyona_sync_checkout_fields() {
	$nonce = isset( $_POST['noyona_sync_checkout_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_sync_checkout_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( ! wp_verify_nonce( $nonce, 'noyona_sync_checkout_fields' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid checkout session.' ), 403 );
	}

	if ( ! function_exists( 'WC' ) || ! WC()->customer ) {
		wp_send_json_error( array( 'message' => 'No customer session.' ), 400 );
	}

	$customer = WC()->customer;

	// phpcs:disable WordPress.Security.NonceVerification.Missing -- mirrors WooCommerce's own update_order_review handler.
	$map = array(
		'billing_first_name' => 'set_billing_first_name',
		'billing_last_name'  => 'set_billing_last_name',
		'billing_email'      => 'set_billing_email',
		'billing_phone'      => 'set_billing_phone',
		'shipping_address_1' => 'set_shipping_address_1',
		'shipping_city'      => 'set_shipping_city',
		'shipping_state'     => 'set_shipping_state',
		'shipping_postcode'  => 'set_shipping_postcode',
		'shipping_country'   => 'set_shipping_country',
	);

	foreach ( $map as $post_key => $setter ) {
		if ( isset( $_POST[ $post_key ] ) && is_callable( array( $customer, $setter ) ) ) {
			$customer->$setter( wc_clean( wp_unslash( $_POST[ $post_key ] ) ) );
		}
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	$customer->save();

	wp_send_json_success();
}

/* ─── Assets ──────────────────────────────────────── */

/**
 * Detect checkout UI contexts for both Woo checkout page and custom review step.
 *
 * @return bool
 */
function noyona_is_checkout_ui_context() {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return true;
	}

	if ( is_page( array( 'checkout', 'preview' ) ) ) {
		return true;
	}

	$request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	$request_lc   = trim( strtolower( untrailingslashit( $request_path ) ), '/' );
	if ( '' === $request_lc ) {
		return false;
	}

	$checkout_path = (string) wp_parse_url(
		function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ),
		PHP_URL_PATH
	);
	$checkout_lc = trim( strtolower( untrailingslashit( $checkout_path ) ), '/' );
	if ( '' !== $checkout_lc && ( $request_lc === $checkout_lc || 0 === strpos( $request_lc, $checkout_lc . '/' ) ) ) {
		return true;
	}

	$reviews_path = (string) wp_parse_url( home_url( '/preview/' ), PHP_URL_PATH );
	$reviews_lc   = trim( strtolower( untrailingslashit( $reviews_path ) ), '/' );
	if ( '' !== $reviews_lc && ( $request_lc === $reviews_lc || 0 === strpos( $request_lc, $reviews_lc . '/' ) ) ) {
		return true;
	}

	return false;
}

/**
 * Detect the custom preview step specifically.
 *
 * PayMongo's plugin scripts only treat Woo's native checkout/order-pay URLs as
 * checkout contexts. The custom /preview/ step still submits Woo checkout, so
 * it needs a small bridge for gateway frontend assets.
 *
 * @return bool
 */
function noyona_is_checkout_preview_step() {
	if ( is_page( 'preview' ) ) {
		return true;
	}

	$request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
	$request_lc   = trim( strtolower( untrailingslashit( $request_path ) ), '/' );
	$preview_path = (string) wp_parse_url( home_url( '/preview/' ), PHP_URL_PATH );
	$preview_lc   = trim( strtolower( untrailingslashit( $preview_path ) ), '/' );

	return ( '' !== $preview_lc && ( $request_lc === $preview_lc || 0 === strpos( $request_lc, $preview_lc . '/' ) ) );
}

/**
 * PayMongo methods whose frontend JS must create cynder_paymongo_method_id
 * before Woo creates an order.
 *
 * @param string $payment_method Payment method ID.
 * @return bool
 */
function noyona_paymongo_method_requires_frontend_token( $payment_method ) {
	$payment_method = sanitize_key( (string) $payment_method );

	return in_array(
		$payment_method,
		array(
			defined( 'PAYMONGO_CARD' ) ? PAYMONGO_CARD : 'paymongo',
			defined( 'PAYMONGO_CARD_INSTALLMENT' ) ? PAYMONGO_CARD_INSTALLMENT : 'paymongo_card_installment',
		),
		true
	);
}

/**
 * Force WooCommerce to use child-theme checkout template overrides.
 *
 * Some environments/plugins can short-circuit template resolution and fall back
 * to core templates; this guarantees our custom checkout flow is rendered.
 *
 * @param string $template      Located template path.
 * @param string $template_name Template name requested by WooCommerce.
 * @param string $template_path Template base path.
 * @return string
 */
add_filter( 'woocommerce_locate_template', 'noyona_force_checkout_templates', 99999, 3 );
function noyona_force_checkout_templates( $template, $template_name, $template_path ) {
	$template_name_lc = strtolower( (string) $template_name );
	$is_target        = (
		'checkout/form-checkout.php' === $template_name_lc
		|| 'checkout/thankyou.php' === $template_name_lc
		|| ( false !== strpos( $template_name_lc, 'checkout/' ) && false !== strpos( $template_name_lc, 'thankyou.php' ) )
	);

	if ( ! $is_target ) {
		return $template;
	}

	$relative = false !== strpos( $template_name_lc, 'form-checkout.php' ) ? 'checkout/form-checkout.php' : 'checkout/thankyou.php';
	$forced   = trailingslashit( get_stylesheet_directory() ) . 'woocommerce/' . $relative;
	if ( is_readable( $forced ) ) {
		return $forced;
	}

	return $template;
}

/**
 * Final safeguard: force checkout templates at wc_get_template stage.
 *
 * This runs after template location and helps when other code overrides the
 * located path late in the stack.
 *
 * @param string $located       Located template absolute path.
 * @param string $template_name Template name.
 * @param array  $args          Template args.
 * @return string
 */
add_filter( 'wc_get_template', 'noyona_force_wc_get_checkout_templates', 99999, 5 );
function noyona_force_wc_get_checkout_templates( $located, $template_name, $args, $template_path, $default_path ) {
	$template_name_lc = strtolower( (string) $template_name );
	$is_target        = (
		'checkout/form-checkout.php' === $template_name_lc
		|| 'checkout/thankyou.php' === $template_name_lc
		|| ( false !== strpos( $template_name_lc, 'checkout/' ) && false !== strpos( $template_name_lc, 'thankyou.php' ) )
	);

	if ( ! $is_target ) {
		return $located;
	}

	$relative = false !== strpos( $template_name_lc, 'form-checkout.php' ) ? 'checkout/form-checkout.php' : 'checkout/thankyou.php';
	$forced   = trailingslashit( get_stylesheet_directory() ) . 'woocommerce/' . $relative;
	if ( is_readable( $forced ) ) {
		return $forced;
	}

	return $located;
}

/**
 * Fallback: on order-received endpoint, replace block-based order confirmation
 * content with classic checkout shortcode output.
 *
 * This guarantees our `woocommerce/checkout/thankyou.php` override is used
 * even when Woo block templates are active or DB-overridden.
 *
 * @param string $block_content Rendered block output.
 * @param array  $block         Parsed block data.
 * @return string
 */
add_filter( 'render_block', 'noyona_force_classic_thankyou_on_order_received', 5, 2 );
function noyona_force_classic_thankyou_on_order_received( $block_content, $block ) {
	if ( is_admin() && ! wp_doing_ajax() ) {
		return $block_content;
	}

	if ( ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
		return $block_content;
	}

	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
	if ( '' === $block_name || 0 !== strpos( $block_name, 'woocommerce/order-confirmation' ) ) {
		return $block_content;
	}

	static $has_injected_classic_thankyou = false;
	static $classic_thankyou_markup       = null;

	if ( ! $has_injected_classic_thankyou ) {
		// Inject once at the first order-confirmation block encountered.
		if ( null === $classic_thankyou_markup ) {
			$classic_thankyou_markup = do_shortcode( '[woocommerce_checkout]' );
		}
		$has_injected_classic_thankyou = true;
		return (string) $classic_thankyou_markup;
	}

	// Suppress remaining order-confirmation blocks.
	return '';
}

/**
 * Enqueue checkout UI styles.
 *
 * @return void
 */
function noyona_enqueue_checkout_ui_styles() {
	$checkout_css_path = get_stylesheet_directory() . '/assets/css/noyona-checkout.css';
	$cart_css_path     = get_stylesheet_directory() . '/assets/css/noyona-cart.css';

	wp_enqueue_style(
		'noyona-cart',
		get_stylesheet_directory_uri() . '/assets/css/noyona-cart.css',
		array( 'woocom-ct-style', 'woocom-ct-header' ),
		file_exists( $cart_css_path ) ? (string) filemtime( $cart_css_path ) : wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'noyona-checkout',
		get_stylesheet_directory_uri() . '/assets/css/noyona-checkout.css',
		array( 'woocom-ct-style', 'woocom-ct-header', 'noyona-cart' ),
		file_exists( $checkout_css_path ) ? (string) filemtime( $checkout_css_path ) : wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'noyona_checkout_enqueue_styles', 20 );
function noyona_checkout_enqueue_styles() {
	if ( ! noyona_is_checkout_ui_context() ) {
		return;
	}

	noyona_enqueue_checkout_ui_styles();
}

/**
 * Ensure WooCommerce checkout runtime scripts are available on custom review step.
 *
 * The /reviews/ URL is outside Woo's default is_checkout() detection, so we
 * proactively enqueue checkout scripts there to keep place-order flow working.
 *
 * @return void
 */
add_action( 'wp_enqueue_scripts', 'noyona_checkout_enqueue_runtime_scripts', 25 );
function noyona_checkout_enqueue_runtime_scripts() {
	if ( ! noyona_is_checkout_ui_context() ) {
		return;
	}

	$handles = array(
		'wc-country-select',
		'wc-address-i18n',
		'wc-checkout',
	);

	foreach ( $handles as $handle ) {
		if ( wp_script_is( $handle, 'registered' ) ) {
			wp_enqueue_script( $handle );
		}
	}
}

/**
 * Bridge PayMongo checkout scripts onto every checkout UI context.
 *
 * The gateway plugin localizes `cynder_paymongo_cc_params` and binds its card
 * tokenizer only when its own `is_checkout()` check passes. Since Phase 2 the
 * review/place-order step is an in-page panel on /checkout/ (the legacy
 * /preview/ page is gone), but custom step URLs (e.g. /reviews/) can still fall
 * outside Woo's native detection. Loading + localizing the card scripts across
 * the whole checkout UI context guarantees `checkout_place_order_paymongo`
 * tokenization is always wired wherever the order is actually placed; without
 * it the form submits with an empty cynder_paymongo_method_id and fails with
 * "Your payment method could not be prepared". Re-enqueue/re-localize is
 * idempotent if the plugin already handled it.
 */
add_action( 'wp_enqueue_scripts', 'noyona_checkout_enqueue_paymongo_preview_assets', 35 );
function noyona_checkout_enqueue_paymongo_preview_assets() {
	if ( ! function_exists( 'noyona_is_checkout_ui_context' ) || ! noyona_is_checkout_ui_context() ) {
		return;
	}

	if ( ! defined( 'CYNDER_PAYMONGO_MAIN_FILE' ) ) {
		return;
	}

	$test_mode  = 'yes' === (string) get_option( 'woocommerce_cynder_paymongo_test_mode', 'no' );
	$public_key = (string) get_option( $test_mode ? 'woocommerce_cynder_paymongo_test_public_key' : 'woocommerce_cynder_paymongo_public_key', '' );
	$secret_key = (string) get_option( $test_mode ? 'woocommerce_cynder_paymongo_test_secret_key' : 'woocommerce_cynder_paymongo_secret_key', '' );

	if ( ! $test_mode && ( '' === $public_key || '' === $secret_key ) ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}

	$plugin_version = defined( 'CYNDER_PAYMONGO_VERSION' ) ? CYNDER_PAYMONGO_VERSION : null;
	$plugin_url     = static function( $asset ) {
		return plugins_url( ltrim( (string) $asset, '/' ), CYNDER_PAYMONGO_MAIN_FILE );
	};

	if ( ! wp_style_is( 'paymongo', 'registered' ) ) {
		wp_register_style( 'paymongo', $plugin_url( 'assets/css/paymongo-styles.css' ), array(), $plugin_version );
	}

	if ( ! wp_script_is( 'cleave', 'registered' ) ) {
		wp_register_script( 'cleave', $plugin_url( 'assets/js/cleave.min.js' ), array(), $plugin_version, true );
	}

	if ( ! wp_script_is( 'woocommerce_paymongo_checkout', 'registered' ) ) {
		wp_register_script( 'woocommerce_paymongo_checkout', $plugin_url( 'assets/js/paymongo-checkout.js' ), array( 'jquery' ), $plugin_version, true );
	}

	if ( ! wp_script_is( 'woocommerce_paymongo_client', 'registered' ) ) {
		wp_register_script( 'woocommerce_paymongo_client', $plugin_url( 'assets/js/paymongo-client.js' ), array( 'jquery' ), $plugin_version, true );
	}

	if ( ! wp_script_is( 'woocommerce_paymongo_cc', 'registered' ) ) {
		wp_register_script( 'woocommerce_paymongo_cc', $plugin_url( 'assets/js/paymongo-cc.js' ), array( 'jquery', 'cleave' ), $plugin_version, true );
	}

	if ( ! wp_script_is( 'woocommerce_paymongo_card_installment', 'registered' ) ) {
		wp_register_script( 'woocommerce_paymongo_card_installment', $plugin_url( 'assets/js/paymongo-installment.js' ), array( 'jquery', 'cleave' ), $plugin_version, true );
	}

	$paymongo_client = array(
		'home_url'   => get_home_url(),
		'public_key' => $public_key,
	);

	$customer = WC()->customer;
	$paymongo_cc = array(
		'isCheckout'   => true,
		'isOrderPay'   => false,
		'total_amount' => WC()->cart ? WC()->cart->get_totals()['total'] : 0,
		'billing_first_name' => $customer ? $customer->get_billing_first_name() : '',
		'billing_last_name'  => $customer ? $customer->get_billing_last_name() : '',
		'billing_address_1'  => $customer ? ( $customer->get_billing_address_1() ?: $customer->get_shipping_address_1() ) : '',
		'billing_address_2'  => $customer ? ( $customer->get_billing_address_2() ?: $customer->get_shipping_address_2() ) : '',
		'billing_state'      => $customer ? ( $customer->get_billing_state() ?: $customer->get_shipping_state() ) : '',
		'billing_city'       => $customer ? ( $customer->get_billing_city() ?: $customer->get_shipping_city() ) : '',
		'billing_postcode'   => $customer ? ( $customer->get_billing_postcode() ?: $customer->get_shipping_postcode() ) : '',
		'billing_country'    => $customer ? ( $customer->get_billing_country() ?: $customer->get_shipping_country() ?: 'PH' ) : 'PH',
		'billing_email'      => $customer ? $customer->get_billing_email() : '',
		'billing_phone'      => $customer ? $customer->get_billing_phone() : '',
	);

	wp_localize_script( 'woocommerce_paymongo_client', 'cynder_paymongo_client_params', $paymongo_client );
	wp_localize_script( 'woocommerce_paymongo_cc', 'cynder_paymongo_cc_params', $paymongo_cc );
	wp_localize_script( 'woocommerce_paymongo_card_installment', 'cynder_paymongo_cc_params', $paymongo_cc );

	wp_enqueue_style( 'paymongo' );
	wp_enqueue_script( 'cleave' );
	wp_enqueue_script( 'woocommerce_paymongo_checkout' );
	wp_enqueue_script( 'woocommerce_paymongo_client' );
	wp_enqueue_script( 'woocommerce_paymongo_cc' );

	$installment_settings = (array) get_option( 'woocommerce_paymongo_card_installment_settings', array() );
	if ( isset( $installment_settings['enabled'] ) && 'yes' === $installment_settings['enabled'] ) {
		wp_enqueue_script( 'woocommerce_paymongo_card_installment' );
	}
}

/**
 * Force the PayMongo checkout scripts to defer so they run after deferred jQuery.
 *
 * This site loads jQuery with `strategy=defer` (jQuery executes after the
 * document is parsed). The PayMongo gateway plugin, however, registers its
 * card/token scripts (paymongo-checkout.js, paymongo-client.js, paymongo-cc.js,
 * paymongo-installment.js) as BLOCKING head scripts. Blocking scripts execute
 * the moment the parser reaches them — before deferred jQuery has run — so each
 * one throws `Uncaught ReferenceError: jQuery is not defined`, `new CCForm()`
 * never runs, the `checkout_place_order_paymongo` tokenizer is never bound, and
 * card submits post an empty `cynder_paymongo_method_id` -> "Your payment method
 * could not be prepared".
 *
 * WooCommerce's own `wc-checkout` already defers (which is why its submit/change
 * handlers bind correctly), so we mirror that: add `jquery` to each handle's
 * deps (guarantees jQuery's tag prints first) and set `strategy=defer` so WP
 * emits a real `defer` attribute and the script executes alongside/after jQuery.
 * We hook at print time (like the Wordfence fix) because the gateway registers
 * these handles from its own `wp_enqueue_scripts` callback; the mutation is
 * idempotent. We do not touch any other handle.
 */
add_action( 'wp_print_scripts', 'noyona_defer_paymongo_checkout_scripts', 0 );
add_action( 'wp_print_footer_scripts', 'noyona_defer_paymongo_checkout_scripts', 0 );
function noyona_defer_paymongo_checkout_scripts() {
	if ( is_admin() ) {
		return;
	}

	if ( ! function_exists( 'noyona_is_checkout_ui_context' ) || ! noyona_is_checkout_ui_context() ) {
		return;
	}

	$scripts = wp_scripts();
	if ( ! $scripts instanceof WP_Scripts ) {
		return;
	}

	$handles = array(
		'woocommerce_paymongo_checkout',
		'woocommerce_paymongo_client',
		'woocommerce_paymongo_cc',
		'woocommerce_paymongo_card_installment',
	);

	foreach ( $handles as $handle ) {
		if ( ! isset( $scripts->registered[ $handle ] ) ) {
			continue;
		}

		$script = $scripts->registered[ $handle ];

		if ( ! is_array( $script->deps ) ) {
			$script->deps = array();
		}
		if ( ! in_array( 'jquery', $script->deps, true ) ) {
			$script->deps[] = 'jquery';
		}

		if ( ! is_array( $script->extra ) ) {
			$script->extra = array();
		}
		$script->extra['strategy'] = 'defer';
	}
}

// Safety net for environments/plugins that alter checkout detection or dequeue styles later.
add_action( 'wp_enqueue_scripts', 'noyona_checkout_force_styles_late', 999 );
function noyona_checkout_force_styles_late() {
	if ( ! noyona_is_checkout_ui_context() ) {
		return;
	}

	if ( wp_style_is( 'noyona-checkout', 'enqueued' ) && wp_style_is( 'noyona-cart', 'enqueued' ) ) {
		return;
	}

	noyona_enqueue_checkout_ui_styles();
}

/* ─── Field customisations ────────────────────────── */

add_filter( 'woocommerce_checkout_fields', 'noyona_customise_checkout_fields' );
function noyona_customise_checkout_fields( $fields ) {
	// Phone placeholder — Philippine format. Force required so the frontend shows the
	// required asterisk (matching backend validation) instead of the "(optional)" label.
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['placeholder'] = '+63 ___ ___-____';
		$fields['billing']['billing_phone']['required']    = true;
	}

	// Order notes placeholder
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['placeholder'] = __( 'Add any special delivery instructions or notes for your order...', 'noyona' );
	}

	/*
	 * Contact Information (billing) should ONLY contain:
	 * first_name, last_name, email, phone.
	 * Remove all address-related fields from billing.
	 */
	$billing_address_fields = array(
		'billing_company',
		'billing_country',
		'billing_address_1',
		'billing_address_2',
		'billing_city',
		'billing_state',
		'billing_postcode',
	);
	foreach ( $billing_address_fields as $field_key ) {
		unset( $fields['billing'][ $field_key ] );
	}

	/*
	 * Shipping Address should ONLY contain:
	 * address_1, city, state, postcode.
	 * Remove name fields and country from shipping
	 * (country is set via billing or defaults).
	 */
	$shipping_remove = array(
		'shipping_first_name',
		'shipping_last_name',
		'shipping_company',
		'shipping_country',
		'shipping_address_2',
		'shipping_phone',
	);
	foreach ( $shipping_remove as $field_key ) {
		unset( $fields['shipping'][ $field_key ] );
	}

	// Make shipping_address_1 full width
	if ( isset( $fields['shipping']['shipping_address_1'] ) ) {
		$fields['shipping']['shipping_address_1']['label'] = __( 'Address', 'noyona' );
	}

	// Force shipping_state to render as a SELECT with WooCommerce's PH state codes
	// baked in. Without this WC's `state` field type renders as a plain text input
	// when no country selector is on the page (because we removed shipping_country),
	// which lets free-text values pass the form but fail zone matching.
	if ( isset( $fields['shipping']['shipping_state'] ) && function_exists( 'WC' ) && WC()->countries ) {
		$ph_states = (array) WC()->countries->get_states( 'PH' );
		if ( ! empty( $ph_states ) ) {
			$fields['shipping']['shipping_state']['type']     = 'select';
			$fields['shipping']['shipping_state']['options']  = array( '' => __( 'Select a province / state', 'noyona' ) ) + $ph_states;
			$fields['shipping']['shipping_state']['label']    = __( 'Province / State', 'noyona' );
			$fields['shipping']['shipping_state']['required'] = true;
		}
	}

	return $fields;
}

/*
 * Remove coupon prompt from checkout page.
 * Coupons should only be applied on the cart page.
 */
add_filter( 'woocommerce_coupons_enabled', 'noyona_disable_coupons_on_checkout' );
function noyona_disable_coupons_on_checkout( $enabled ) {
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return false;
	}
	return $enabled;
}

/**
 * Restrict checkout payment methods to PayMongo channels and remove Check payments.
 *
 * - Always removes Woo's built-in "Check payments" gateway (`cheque`).
 * - On checkout UI, keeps every PayMongo gateway that's enabled (QR Ph, GCash, Maya, etc.).
 *   QR Ph entries are listed first so they remain the primary option.
 * - If no PayMongo gateways are detected, returns the original set (minus `cheque`) so the
 *   site doesn't end up with an empty payment list.
 *
 * @param array<string, WC_Payment_Gateway> $gateways Available gateways.
 * @return array<string, WC_Payment_Gateway>
 */
add_filter( 'woocommerce_available_payment_gateways', 'noyona_limit_checkout_payment_gateways', 30 );
function noyona_limit_checkout_payment_gateways( $gateways ) {
	if ( ! is_array( $gateways ) ) {
		return $gateways;
	}

	// Remove the default "Check payments" option everywhere on frontend.
	if ( isset( $gateways['cheque'] ) ) {
		unset( $gateways['cheque'] );
	}

	// Do not alter non-checkout contexts further.
	if ( ! function_exists( 'noyona_is_checkout_ui_context' ) || ! noyona_is_checkout_ui_context() ) {
		return $gateways;
	}

	if ( empty( $gateways ) ) {
		return $gateways;
	}

	$paymongo_qr_gateways    = array();
	$paymongo_other_gateways = array();

	foreach ( $gateways as $gateway_id => $gateway ) {
		$gateway_title = '';
		$method_title  = '';

		if ( is_object( $gateway ) ) {
			$gateway_title = isset( $gateway->title ) ? (string) $gateway->title : '';
			$method_title  = isset( $gateway->method_title ) ? (string) $gateway->method_title : '';
		}

		$haystack = strtolower( trim( (string) $gateway_id . ' ' . $gateway_title . ' ' . $method_title ) );

		$is_paymongo = ( false !== strpos( $haystack, 'paymongo' ) );
		if ( ! $is_paymongo ) {
			continue;
		}

		$is_qr = ( false !== strpos( $haystack, 'qr' ) || false !== strpos( $haystack, 'qrph' ) || false !== strpos( $haystack, 'qr ph' ) );

		if ( $is_qr ) {
			$paymongo_qr_gateways[ $gateway_id ] = $gateway;
		} else {
			$paymongo_other_gateways[ $gateway_id ] = $gateway;
		}
	}

	$merged = $paymongo_qr_gateways + $paymongo_other_gateways;
	if ( ! empty( $merged ) ) {
		return $merged;
	}

	// Final fallback: keep current set (already without "cheque") so checkout never goes
	// completely empty if no PayMongo gateway is enabled yet.
	return $gateways;
}

/*
 * Copy billing name → shipping name so WooCommerce doesn't
 * reject the order for missing shipping_first/last_name.
 */
add_action( 'woocommerce_checkout_create_order', 'noyona_copy_billing_name_to_shipping', 10, 2 );
function noyona_copy_billing_name_to_shipping( $order, $data ) {
	$order->set_shipping_first_name( $order->get_billing_first_name() );
	$order->set_shipping_last_name( $order->get_billing_last_name() );
	$order->set_shipping_country( $order->get_billing_country() ?: 'PH' );

	$get_value = static function( $key, $posted_data = array() ) {
		if ( is_array( $posted_data ) && isset( $posted_data[ $key ] ) ) {
			return wc_clean( (string) $posted_data[ $key ] );
		}

		if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return wc_clean( wp_unslash( (string) $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		return '';
	};

	$shipping_address_1 = $get_value( 'shipping_address_1', $data );
	$shipping_city      = $get_value( 'shipping_city', $data );
	$shipping_state     = $get_value( 'shipping_state', $data );
	$shipping_postcode  = $get_value( 'shipping_postcode', $data );

	// Fallback: recover shipping values from customer session if request payload is sparse.
	if ( function_exists( 'WC' ) && WC()->customer ) {
		if ( '' === $shipping_address_1 ) {
			$shipping_address_1 = wc_clean( (string) WC()->customer->get_shipping_address_1() );
		}
		if ( '' === $shipping_city ) {
			$shipping_city = wc_clean( (string) WC()->customer->get_shipping_city() );
		}
		if ( '' === $shipping_state ) {
			$shipping_state = wc_clean( (string) WC()->customer->get_shipping_state() );
		}
		if ( '' === $shipping_postcode ) {
			$shipping_postcode = wc_clean( (string) WC()->customer->get_shipping_postcode() );
		}
	}

	if ( '' !== $shipping_address_1 ) {
		$order->set_shipping_address_1( $shipping_address_1 );
	}
	if ( '' !== $shipping_city ) {
		$order->set_shipping_city( $shipping_city );
	}
	if ( '' !== $shipping_state ) {
		$order->set_shipping_state( $shipping_state );
	}
	if ( '' !== $shipping_postcode ) {
		$order->set_shipping_postcode( $shipping_postcode );
	}

	$order_comments = $get_value( 'order_comments', $data );
	if ( '' === $order_comments && function_exists( 'WC' ) && WC()->customer ) {
		$order_comments = wc_clean( (string) WC()->customer->get_meta( 'order_comments', true ) );
	}
	if ( '' !== $order_comments && '' === trim( (string) $order->get_customer_note() ) ) {
		$order->set_customer_note( $order_comments );
	}
}

/* ─── Save custom checkout fields ─────────────────── */

add_action( 'woocommerce_checkout_update_order_meta', 'noyona_save_custom_checkout_fields' );
function noyona_save_custom_checkout_fields( $order_id ) {
	if ( ! empty( $_POST['noyona_newsletter'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $order_id, '_noyona_newsletter_optin', 'yes' );

		// Push the opted-in customer to the Brevo newsletter list (best-effort:
		// a Brevo failure must never block order completion).
		if ( function_exists( 'noyona_brevo_is_configured' ) && noyona_brevo_is_configured() ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$email = $order->get_billing_email();
				if ( $email && is_email( $email ) ) {
					noyona_brevo_add_contact(
						$email,
						array(
							'FIRSTNAME' => $order->get_billing_first_name(),
							'LASTNAME'  => $order->get_billing_last_name(),
						)
					);
				}
			}
		}
	}
}

add_filter( 'woocommerce_order_button_text', 'noyona_place_order_button_text' );
function noyona_place_order_button_text( $text ) {
	return __( 'Place Order', 'noyona' );
}

/**
 * Remove WooCommerce privacy paragraph copy on checkout UI.
 *
 * The custom checkout keeps only our compact terms consent row.
 */
add_filter( 'woocommerce_checkout_privacy_policy_text', 'noyona_hide_checkout_privacy_policy_text', 20 );
function noyona_hide_checkout_privacy_policy_text( $text ) {
	if ( function_exists( 'noyona_is_checkout_ui_context' ) && noyona_is_checkout_ui_context() ) {
		return '';
	}

	return $text;
}

/**
 * Extra safety: strip Woo privacy text by getter filter too.
 *
 * This catches contexts where Woo fetches the text via wc_get_privacy_policy_text().
 *
 * @param string $text Privacy text.
 * @param string $type Context type.
 * @return string
 */
add_filter( 'woocommerce_get_privacy_policy_text', 'noyona_strip_woocommerce_privacy_copy', 20, 2 );
function noyona_strip_woocommerce_privacy_copy( $text, $type ) {
	if ( 'checkout' === $type && function_exists( 'noyona_is_checkout_ui_context' ) && noyona_is_checkout_ui_context() ) {
		return '';
	}

	return $text;
}

/**
 * Always ship to a separate address (we show shipping fields directly).
 */
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );

/**
 * Always compute shipping from the *shipping* address.
 *
 * We strip every billing-address field in noyona_customise_checkout_fields(),
 * so a billing-based destination would always be empty → no zone match → no
 * rates → ₱0 in cart and "Please enter an address" at checkout.
 *
 * Forced unconditionally (cart, mini-cart, checkout, admin) so zone matching
 * is consistent everywhere. The DB option `woocommerce_ship_to_destination`
 * may still read 'billing'; this filter wins at runtime.
 */
add_filter( 'woocommerce_ship_to_destination', 'noyona_force_ship_to_destination_mode', 20 );
function noyona_force_ship_to_destination_mode( $destination ) {
	return 'shipping';
}

/**
 * Fail fast when PayMongo card token creation did not happen.
 *
 * This prevents Woo from creating a pending "To Pay" order when the custom
 * preview step submits before PayMongo's frontend has attached
 * cynder_paymongo_method_id.
 *
 * @param array    $data   Posted checkout data.
 * @param WP_Error $errors Checkout validation errors.
 */
add_action( 'woocommerce_after_checkout_validation', 'noyona_validate_paymongo_frontend_token_before_order', 40, 2 );
function noyona_validate_paymongo_frontend_token_before_order( $data, $errors ) {
	if ( ! $errors instanceof WP_Error || $errors->has_errors() ) {
		return;
	}

	$payment_method = '';
	if ( is_array( $data ) && isset( $data['payment_method'] ) ) {
		$payment_method = sanitize_key( (string) $data['payment_method'] );
	} elseif ( isset( $_POST['payment_method'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$payment_method = sanitize_key( wp_unslash( $_POST['payment_method'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	if ( '' === $payment_method || ! noyona_paymongo_method_requires_frontend_token( $payment_method ) ) {
		return;
	}

	$method_id = isset( $_POST['cynder_paymongo_method_id'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		? trim( sanitize_text_field( wp_unslash( $_POST['cynder_paymongo_method_id'] ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		: '';

	if ( '' !== $method_id ) {
		return;
	}

	$errors->add(
		'noyona_paymongo_method_token_missing',
		__( 'Your payment method could not be prepared. Please refresh the checkout page and try again before placing your order.', 'noyona' )
	);
}

/**
 * Guard Place Order submissions for the same checkout attempt.
 *
 * Woo already validates the checkout nonce. This adds two protections:
 *
 *   1. A short-lived idempotency lock that absorbs rapid double-clicks before
 *      WooCommerce sets up its own order-resume guard.
 *   2. A block that fires ONLY when the order produced by this attempt is
 *      already PAID.
 *
 * Crucially, an *unpaid* order does NOT block a retry. Every PayMongo method
 * (QR Ph, card, GCash, Maya) creates the order in `pending` BEFORE payment
 * resolves, so a failed/abandoned payment must remain retryable. WooCommerce
 * resumes the same pending order via its `order_awaiting_payment` session key,
 * so retrying never produces a duplicate order.
 *
 * @param array    $data   Posted checkout data.
 * @param WP_Error $errors Checkout validation errors.
 */
add_action( 'woocommerce_after_checkout_validation', 'noyona_lock_checkout_attempt_after_validation', 999, 2 );
function noyona_lock_checkout_attempt_after_validation( $data, $errors ) {
	if ( ! $errors instanceof WP_Error || $errors->has_errors() ) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$attempt_id         = isset( $_POST['noyona_checkout_attempt_id'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_checkout_attempt_id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	$session_attempt_id = (string) WC()->session->get( 'noyona_checkout_attempt_id', '' );

	if ( '' === $attempt_id || '' === $session_attempt_id || ! hash_equals( $session_attempt_id, $attempt_id ) ) {
		$errors->add(
			'noyona_checkout_attempt_invalid',
			__( 'Your checkout session expired. Please refresh the checkout page and try again.', 'noyona' )
		);
		return;
	}

	// Block only if the order created by THIS attempt has already been paid.
	// Unpaid orders are intentionally allowed through so the customer can retry
	// payment; WooCommerce resumes the same pending order rather than duplicating.
	$attempt_order_id = absint( WC()->session->get( 'noyona_checkout_attempt_order_id', 0 ) );
	if ( $attempt_order_id > 0 && function_exists( 'wc_get_order' ) ) {
		$attempt_order = wc_get_order( $attempt_order_id );
		if (
			$attempt_order instanceof WC_Order
			&& hash_equals( (string) $attempt_order->get_meta( '_noyona_checkout_attempt_id' ), $attempt_id )
			&& $attempt_order->is_paid()
		) {
			$errors->add(
				'noyona_checkout_attempt_paid',
				sprintf(
					/* translators: %s: order number */
					__( 'Order #%s has already been paid. Please check your order history before placing another order.', 'noyona' ),
					esc_html( (string) $attempt_order->get_order_number() )
				)
			);
			return;
		}
	}

	// Short, self-healing double-submit lock. Released on
	// woocommerce_checkout_order_processed (priority 5) regardless of the payment
	// outcome, so a failed payment-intent creation can never strand the customer.
	$lock_key = noyona_checkout_attempt_lock_key( $attempt_id );
	if ( get_transient( $lock_key ) ) {
		$errors->add(
			'noyona_checkout_attempt_locked',
			__( 'Your order is already being processed. Please wait a moment before trying again.', 'noyona' )
		);
		return;
	}

	set_transient(
		$lock_key,
		array(
			'time'    => time(),
			'user_id' => get_current_user_id(),
		),
		30
	);

	WC()->session->set( 'noyona_checkout_attempt_lock_key', $lock_key );
	WC()->session->set( 'noyona_checkout_attempt_lock_id', $attempt_id );
}

/**
 * Record the order produced by a checkout attempt and release the lock.
 *
 * Runs at priority 5 — ahead of the PayMongo plugin's payment-intent creation
 * (also on woocommerce_checkout_order_processed) — so the double-submit lock is
 * always released even if intent creation later throws.
 *
 * Order creation is NOT treated as "completed". Only a *paid* order blocks a
 * retry (see the paid check above), because every PayMongo method creates the
 * order before payment resolves. The attempt id is stamped on the order so that
 * paid status can be tied back to the originating attempt.
 *
 * @param int      $order_id Order ID.
 * @param array    $data     Posted checkout data.
 * @param WC_Order $order    Created order.
 */
add_action( 'woocommerce_checkout_order_processed', 'noyona_mark_checkout_attempt_processed', 5, 3 );
function noyona_mark_checkout_attempt_processed( $order_id, $data, $order ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$lock_key = (string) WC()->session->get( 'noyona_checkout_attempt_lock_key', '' );
	if ( '' !== $lock_key ) {
		delete_transient( $lock_key );
		WC()->session->set( 'noyona_checkout_attempt_lock_key', '' );
	}

	$attempt_id = (string) WC()->session->get( 'noyona_checkout_attempt_id', '' );
	if ( '' === $attempt_id ) {
		return;
	}

	WC()->session->set( 'noyona_checkout_attempt_order_id', absint( $order_id ) );

	if ( $order instanceof WC_Order && '' === (string) $order->get_meta( '_noyona_checkout_attempt_id' ) ) {
		$order->update_meta_data( '_noyona_checkout_attempt_id', $attempt_id );
		$order->save();
	}
}

/**
 * Release the attempt lock when Woo discards an order because creation failed.
 *
 * @param WC_Order $order Discarded order.
 */
add_action( 'woocommerce_checkout_order_exception', 'noyona_release_checkout_attempt_lock_on_exception' );
function noyona_release_checkout_attempt_lock_on_exception( $order ) {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return;
	}

	$lock_key = (string) WC()->session->get( 'noyona_checkout_attempt_lock_key', '' );
	if ( '' !== $lock_key ) {
		delete_transient( $lock_key );
		WC()->session->set( 'noyona_checkout_attempt_lock_key', '' );
	}
}

/**
 * Terminate a stale checkout session whose order is already paid.
 *
 * Covers the confusing case where a customer completed payment (often confirmed
 * asynchronously by the PayMongo webhook) but never returned to the
 * order-received page, so their cart + checkout attempt session survived.
 * Returning to checkout, they would otherwise walk through the entire multi-step
 * form only to be blocked at Place Order with "Order #X already paid".
 *
 * Running on template_redirect in checkout context, we detect that the order
 * tied to THIS session's attempt is already paid and the cart is unchanged since
 * that order, then empty the cart, clear the attempt session, and send them to
 * the completed order's received page. The Place Order paid-check remains as a
 * backstop for races between page load and submit.
 *
 * Safety rules:
 *   - Only acts on the order produced by this session's own attempt id.
 *   - Only acts when that order is paid (unpaid orders stay retryable).
 *   - Only clears when the cart still matches the completed order, so a genuine
 *     new purchase (different items) is never wiped.
 */
add_action( 'template_redirect', 'noyona_terminate_paid_checkout_session', 5 );
function noyona_terminate_paid_checkout_session() {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( ! function_exists( 'noyona_is_checkout_ui_context' ) || ! noyona_is_checkout_ui_context() ) {
		return;
	}

	// Never interfere with the order-received / order-pay endpoints themselves
	// (is_checkout() — and therefore the context check — is also true there).
	if ( function_exists( 'is_wc_endpoint_url' )
		&& ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) )
	) {
		return;
	}

	if ( ! function_exists( 'WC' ) || ! WC()->session || ! WC()->cart ) {
		return;
	}

	$attempt_order_id = absint( WC()->session->get( 'noyona_checkout_attempt_order_id', 0 ) );
	if ( $attempt_order_id <= 0 || ! function_exists( 'wc_get_order' ) ) {
		return;
	}

	$attempt_id = (string) WC()->session->get( 'noyona_checkout_attempt_id', '' );
	$order      = wc_get_order( $attempt_order_id );

	if (
		! $order instanceof WC_Order
		|| '' === $attempt_id
		|| ! hash_equals( (string) $order->get_meta( '_noyona_checkout_attempt_id' ), $attempt_id )
		|| ! $order->is_paid()
	) {
		return;
	}

	// Only clear when the cart is unchanged since that completed order.
	$current_cart_hash = is_callable( array( WC()->cart, 'get_cart_hash' ) ) ? (string) WC()->cart->get_cart_hash() : '';
	$attempt_cart_hash = (string) WC()->session->get( 'noyona_checkout_attempt_cart_hash', '' );
	if ( '' === $current_cart_hash || ! hash_equals( $attempt_cart_hash, $current_cart_hash ) ) {
		return;
	}

	// Terminate the stale session.
	WC()->cart->empty_cart();
	WC()->session->set( 'order_awaiting_payment', null );
	WC()->session->set( 'noyona_checkout_attempt_id', '' );
	WC()->session->set( 'noyona_checkout_attempt_cart_hash', '' );
	WC()->session->set( 'noyona_checkout_attempt_order_id', 0 );

	$lock_key = (string) WC()->session->get( 'noyona_checkout_attempt_lock_key', '' );
	if ( '' !== $lock_key ) {
		delete_transient( $lock_key );
	}
	WC()->session->set( 'noyona_checkout_attempt_lock_key', '' );
	WC()->session->set( 'noyona_checkout_attempt_lock_id', '' );

	if ( function_exists( 'wc_add_notice' ) ) {
		wc_add_notice(
			sprintf(
				/* translators: %s: order number */
				__( 'Your previous order #%s was completed successfully. We have started a fresh cart for you.', 'noyona' ),
				esc_html( (string) $order->get_order_number() )
			),
			'notice'
		);
	}

	$target = $order->get_checkout_order_received_url();
	if ( ! $target ) {
		if ( is_user_logged_in() && function_exists( 'wc_get_account_endpoint_url' ) ) {
			$target = wc_get_account_endpoint_url( 'orders' );
		} elseif ( function_exists( 'wc_get_page_permalink' ) ) {
			$target = wc_get_page_permalink( is_user_logged_in() ? 'myaccount' : 'shop' );
		} else {
			$target = home_url( '/' );
		}
	}

	wp_safe_redirect( $target );
	exit;
}

/* ─── Inline JS ───────────────────────────────────── */

add_action( 'wp_footer', 'noyona_checkout_inline_js' );
function noyona_checkout_inline_js() {
	if ( ! function_exists( 'noyona_is_checkout_ui_context' ) || ! noyona_is_checkout_ui_context() ) {
		return;
	}
	?>
	<script>
	(function() {
		var body = document.body;
		if (!body) return;
		var form = document.querySelector('.noyona-checkout-form');
		var isOrderReceivedPath = window.location.pathname.indexOf('/order-received/') !== -1;
		var isOrderPayPath = window.location.pathname.indexOf('/order-pay/') !== -1;
		var orderPayForm = document.querySelector('form#order_review, form.woocommerce-order-pay, form.woocommerce-checkout');
		if (!form && !isOrderReceivedPath && !isOrderPayPath && !orderPayForm) return;

		/* 1. Scroll to validation errors */
		var lastScrolledCheckoutNoticeSignature = '';
		function getCheckoutNoticeSignature(notice) {
			if (!notice) return '';
			return String(notice.textContent || '').replace(/\s+/g, ' ').trim();
		}

		var observer = new MutationObserver(function() {
			var notice = document.querySelector('.woocommerce-NoticeGroup-checkout');
			var noticeSignature = getCheckoutNoticeSignature(notice);
			if (notice && noticeSignature && noticeSignature !== lastScrolledCheckoutNoticeSignature) {
				lastScrolledCheckoutNoticeSignature = noticeSignature;
				notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
				var firstInvalid = document.querySelector('.woocommerce-invalid input, .woocommerce-invalid select');
				if (firstInvalid) {
					setTimeout(function() { firstInvalid.focus(); }, 600);
				}
			}
		});
		if (form && form.parentElement) {
			observer.observe(form.parentElement, { childList: true, subtree: true });
		}

		/* 2. Notes card toggle on mobile */
		var notesCard = document.querySelector('.noyona-checkout-card--notes');
		if (notesCard) {
			var notesTitle = notesCard.querySelector('.noyona-checkout-card__title');
			if (notesTitle) {
				notesTitle.addEventListener('click', function() {
					if (window.innerWidth <= 900) {
						notesCard.classList.toggle('is-open');
					}
				});
			}
		}

		var reviewUrl = <?php echo wp_json_encode( home_url( '/preview/' ) ); ?>;
		var detailsUrl = <?php echo wp_json_encode( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>;
		var cartUrl = <?php echo wp_json_encode( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>;
		var orderStatusProbeUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
		var checkoutSyncNonce = <?php echo wp_json_encode( wp_create_nonce( 'noyona_sync_checkout_fields' ) ); ?>;
		var allowDonePreviewBypass = <?php echo wp_json_encode( function_exists( 'noyona_checkout_allow_done_preview_bypass' ) && noyona_checkout_allow_done_preview_bypass() ); ?>;
		var donePreviewUrl = <?php
			echo wp_json_encode(
				add_query_arg(
					'noyona_preview_done',
					'1',
					function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' )
				)
			);
		?>;
		var currentPath = window.location.pathname.replace(/\/+$/, '');
		var reviewPath = '';
		try {
			reviewPath = new URL(reviewUrl, window.location.origin).pathname.replace(/\/+$/, '');
		} catch (e) {
			reviewPath = '/preview';
		}

		function ensureCheckoutStepper() {
			var existing = document.querySelector('.noyona-checkout-steps');
			if (existing) return existing;

			var nav = document.createElement('nav');
			nav.className = 'noyona-checkout-steps';
			nav.setAttribute('aria-label', 'Checkout progress');
			nav.innerHTML =
				'<ol>' +
					'<li><span class="noyona-checkout-steps__icon" aria-hidden="true"><i class="fa-solid fa-bag-shopping"></i></span> Cart</li>' +
					'<li><span class="noyona-checkout-steps__icon" aria-hidden="true"><i class="fa-solid fa-truck"></i></span> Details</li>' +
					'<li><span class="noyona-checkout-steps__icon" aria-hidden="true"><i class="fa-solid fa-box"></i></span> Preview</li>' +
					'<li><span class="noyona-checkout-steps__icon" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></span> Payment</li>' +
					'<li><span class="noyona-checkout-steps__icon" aria-hidden="true"><i class="fa-solid fa-check"></i></span> Done</li>' +
				'</ol>';

			var shell = document.querySelector('.noyona-checkout-shell');
			if (shell) {
				shell.insertBefore(nav, shell.firstChild);
				return nav;
			}

			var mainNode = document.querySelector('.noyona-checkout-main') || document.querySelector('main') || document.querySelector('.woocommerce-order');
			if (mainNode && mainNode.parentElement) {
				mainNode.parentElement.insertBefore(nav, mainNode);
				return nav;
			}

			return null;
		}

		// Review is now an in-page panel on /checkout/ (no separate /preview/ page),
		// so the checkout always loads in details mode and toggles client-side.
		// This keeps entered card details in the DOM through Place Order.
		var isReviewStep = false;
		var isAwaitingPayment = !!document.querySelector('[data-noyona-awaiting-payment="1"]');

		if (!isAwaitingPayment && isOrderReceivedPath) {
			function hasPendingQrCopy(text) {
				if (!text) return false;
				return (
					text.indexOf('scan qr code to pay') !== -1 ||
					text.indexOf('waiting for payment') !== -1 ||
					text.indexOf('open your banking app') !== -1 ||
					text.indexOf('scan the qr code below') !== -1
				);
			}

			function isNodeVisible(node) {
				if (!node) return false;
				var style = window.getComputedStyle(node);
				if (!style || style.display === 'none' || style.visibility === 'hidden' || Number(style.opacity) === 0) {
					return false;
				}
				var rect = node.getBoundingClientRect();
				return rect.width > 0 && rect.height > 0;
			}

			function hasVisibleQrPayload(root) {
				if (!root) return false;

				var canvases = root.querySelectorAll('canvas');
				for (var i = 0; i < canvases.length; i++) {
					if (isNodeVisible(canvases[i])) {
						return true;
					}
				}

				var images = root.querySelectorAll('img');
				for (var j = 0; j < images.length; j++) {
					var img = images[j];
					var src = String(img.getAttribute('src') || '').toLowerCase();
					var alt = String(img.getAttribute('alt') || '').toLowerCase();
					var cls = String(img.className || '').toLowerCase();
					if (
						src.indexOf('qr') !== -1 ||
						src.indexOf('qrcode') !== -1 ||
						src.indexOf('qrph') !== -1 ||
						alt.indexOf('qr') !== -1 ||
						alt.indexOf('qrcode') !== -1 ||
						cls.indexOf('qr') !== -1
					) {
						if (isNodeVisible(img)) {
							return true;
						}
					}
				}

				// Treat a paymongo-classed block as a QR payload only if it actually contains
				// a <canvas> or a qr-tagged <img>. Bare paymongo branding (logos, footers used
				// on GCash/Maya order-received pages) must NOT count as a QR payload.
				var paymongoBlocks = root.querySelectorAll('[class*="paymongo"], [id*="paymongo"], [class*="PayMongo"], [id*="PayMongo"]');
				for (var k = 0; k < paymongoBlocks.length; k++) {
					var block = paymongoBlocks[k];
					if (!isNodeVisible(block)) {
						continue;
					}
					if (block.querySelector('canvas')) {
						return true;
					}
					var blockImgs = block.querySelectorAll('img');
					for (var m = 0; m < blockImgs.length; m++) {
						var bImg = blockImgs[m];
						var bSrc = String(bImg.getAttribute('src') || '').toLowerCase();
						var bAlt = String(bImg.getAttribute('alt') || '').toLowerCase();
						var bCls = String(bImg.className || '').toLowerCase();
						var looksLikeQrImg = (
							bSrc.indexOf('qr') !== -1 ||
							bSrc.indexOf('qrcode') !== -1 ||
							bSrc.indexOf('qrph') !== -1 ||
							bAlt.indexOf('qr') !== -1 ||
							bAlt.indexOf('qrcode') !== -1 ||
							bCls.indexOf('qr') !== -1
						);
						if (looksLikeQrImg && isNodeVisible(bImg)) {
							return true;
						}
					}
				}

				return false;
			}

			var paymentOverviewNode = document.querySelector('.woocommerce-order-overview__payment-method');
			var paymentOverviewText = paymentOverviewNode ? String(paymentOverviewNode.textContent || '').toLowerCase() : '';
			var bodyText = String((document.body && (document.body.innerText || document.body.textContent)) || '').toLowerCase();
			var orderRoot = document.querySelector('.woocommerce-order') || document;
			// Use the displayed payment-method label as the source of truth. The label reflects
			// the chosen gateway (e.g. "QR Ph via PayMongo" vs "GCash via PayMongo"), so it
			// cleanly separates QR Ph from GCash/Maya. Do NOT scan whole-body text — branded
			// PayMongo markup on GCash/Maya pages can falsely contain "paymongo" + "qr".
			var looksLikePayMongoQr = (
				paymentOverviewText.indexOf('paymongo') !== -1 &&
				paymentOverviewText.indexOf('qr') !== -1
			);
			var hasQrWaitingCopy = hasPendingQrCopy(bodyText);
			var hasQrPayload = hasVisibleQrPayload(orderRoot);
			var hasOrderReceivedCopy = (
				bodyText.indexOf('order has been received') !== -1 ||
				bodyText.indexOf('order received') !== -1
			);
			if (looksLikePayMongoQr && (hasQrPayload || (hasQrWaitingCopy && !hasOrderReceivedCopy))) {
				isAwaitingPayment = true;
			}
		}

		var hasDoneOrderMarker = !!document.querySelector('[data-noyona-done-order="1"]');
		var isDoneStep = !isAwaitingPayment && (
			hasDoneOrderMarker ||
			(allowDonePreviewBypass && window.location.search.indexOf('noyona_preview_done=1') !== -1)
		);

		var draftStorageKey = 'noyonaCheckoutDraft';
		if (window.location.pathname.indexOf('/order-received/') !== -1 && window.sessionStorage) {
			try {
				window.sessionStorage.removeItem(draftStorageKey);
			} catch (e) {
				// Ignore storage failures.
			}
		}

		function shouldPersistField(name) {
			if (!name) return false;
			return (
				name.indexOf('billing_') === 0 ||
				name.indexOf('shipping_') === 0 ||
				name === 'order_comments' ||
				name === 'payment_method' ||
				name === 'terms' ||
				name === 'noyona_newsletter'
			);
		}

		function persistCheckoutDraft(checkoutForm) {
			if (!checkoutForm || !window.sessionStorage) return;
			var payload = {};
			var fields = checkoutForm.querySelectorAll('input[name], select[name], textarea[name]');
			fields.forEach(function (field) {
				var name = field.name;
				if (!shouldPersistField(name) || field.disabled) return;

				if (field.type === 'radio') {
					if (field.checked) payload[name] = field.value;
					return;
				}

				if (field.type === 'checkbox') {
					payload[name] = field.checked ? (field.value || '1') : '';
					return;
				}

				payload[name] = field.value;
			});

			try {
				window.sessionStorage.setItem(draftStorageKey, JSON.stringify(payload));
			} catch (e) {
				// Ignore storage failures.
			}
		}

		/**
		 * Push checkout form data to the WC server session via the
		 * update_order_review AJAX endpoint so that WC()->customer
		 * is populated before the browser navigates to /preview/.
		 */
		function syncCheckoutToServer(checkoutForm, onDone) {
			if (!checkoutForm) { if (onDone) onDone(); return; }

			var formData = '';
			try {
				formData = window.jQuery
					? window.jQuery(checkoutForm).serialize()
					: new URLSearchParams(new FormData(checkoutForm)).toString();
			} catch (e) {
				if (onDone) onDone();
				return;
			}

			var ajaxUrl = (typeof wc_checkout_params !== 'undefined' && wc_checkout_params.ajax_url)
				? wc_checkout_params.ajax_url
				: (typeof wc_cart_fragments_params !== 'undefined' && wc_cart_fragments_params.ajax_url
					? wc_cart_fragments_params.ajax_url
					: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>');

			var syncPayload = new URLSearchParams(formData);
			if (checkoutSyncNonce) {
				syncPayload.set('noyona_sync_checkout_nonce', checkoutSyncNonce);
			}
			var body = 'action=noyona_sync_checkout_fields&' + syncPayload.toString();

			var xhr = new XMLHttpRequest();
			xhr.open('POST', ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () { if (onDone) onDone(); };
			xhr.onerror = function () { if (onDone) onDone(); };
			xhr.send(body);
		}

		function restoreCheckoutDraft(checkoutForm) {
			if (!checkoutForm || !window.sessionStorage) return;
			var raw = '';
			try {
				raw = window.sessionStorage.getItem(draftStorageKey) || '';
			} catch (e) {
				raw = '';
			}
			if (!raw) return;

			var payload = {};
			try {
				payload = JSON.parse(raw);
			} catch (e) {
				return;
			}
			if (!payload || typeof payload !== 'object') return;

			Object.keys(payload).forEach(function (name) {
				var value = payload[name];
				var nodes = checkoutForm.querySelectorAll('[name="' + name + '"]');
				if (!nodes || !nodes.length) return;

				nodes.forEach(function (field) {
					if (field.type === 'radio') {
						field.checked = String(field.value) === String(value);
					} else if (field.type === 'checkbox') {
						field.checked = String(value) !== '';
					} else {
						field.value = String(value);
					}
					field.dispatchEvent(new Event('input', { bubbles: true }));
					field.dispatchEvent(new Event('change', { bubbles: true }));
				});
			});

			if (window.jQuery) {
				window.jQuery(document.body).trigger('update_checkout');
			}
		}

		var checkoutSummaryRefreshController = null;
		var pendingCouponSummaryRefresh = false;

		function replaceCheckoutSummaryFromDocument(nextDocument) {
			if (!nextDocument) return false;

			var selectors = [
				'.noyona-checkout-card--review-items',
				'.noyona-checkout-card--review-totals'
			];
			var didReplace = false;

			selectors.forEach(function (selector) {
				var current = document.querySelector(selector);
				var next = nextDocument.querySelector(selector);
				if (current && next) {
					current.replaceWith(next);
					didReplace = true;
				}
			});

			if (isReviewStep && form) {
				syncReviewSnapshot(form);
			}

			return didReplace;
		}

		function refreshCheckoutSummaryFromPage() {
			var summaryCard = document.querySelector('.noyona-checkout-card--review-items');
			var totalsCard = document.querySelector('.noyona-checkout-card--review-totals');
			if (!summaryCard && !totalsCard) return;

			if (checkoutSummaryRefreshController && typeof checkoutSummaryRefreshController.abort === 'function') {
				checkoutSummaryRefreshController.abort();
			}
			checkoutSummaryRefreshController = typeof AbortController !== 'undefined' ? new AbortController() : null;

			[summaryCard, totalsCard].forEach(function (card) {
				if (card) {
					card.setAttribute('aria-busy', 'true');
				}
			});

			var url = new URL(window.location.href);
			url.searchParams.set('noyona_checkout_summary_refresh', String(Date.now()));

			fetch(url.toString(), {
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				},
				signal: checkoutSummaryRefreshController ? checkoutSummaryRefreshController.signal : undefined
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('Checkout summary refresh failed');
				}
				return response.text();
			}).then(function (html) {
				var parser = new DOMParser();
				var nextDocument = parser.parseFromString(html, 'text/html');
				replaceCheckoutSummaryFromDocument(nextDocument);
			}).catch(function (error) {
				if (error && error.name === 'AbortError') return;
			}).finally(function () {
				document.querySelectorAll('.noyona-checkout-card--review-items, .noyona-checkout-card--review-totals').forEach(function (card) {
					card.removeAttribute('aria-busy');
				});
				checkoutSummaryRefreshController = null;
			});
		}

		function queueCheckoutSummaryRefresh() {
			pendingCouponSummaryRefresh = true;
			if (window.jQuery) {
				window.jQuery(document.body).trigger('update_checkout');
			}
			window.setTimeout(function () {
				if (!pendingCouponSummaryRefresh) return;
				pendingCouponSummaryRefresh = false;
				refreshCheckoutSummaryFromPage();
			}, 350);
		}

		function readFieldValue(checkoutForm, name) {
			if (!checkoutForm || !name) return '';
			var nodes = checkoutForm.querySelectorAll('[name="' + name + '"]');
			if (!nodes || !nodes.length) return '';

			var first = nodes[0];
			if (first.type === 'radio') {
				var checked = checkoutForm.querySelector('[name="' + name + '"]:checked');
				return checked ? String(checked.value || '').trim() : '';
			}
			if (first.type === 'checkbox') {
				return first.checked ? String(first.value || '1').trim() : '';
			}
			return String(first.value || '').trim();
		}

		function readPaymentLabel(checkoutForm) {
			if (!checkoutForm) return '';
			var selected = checkoutForm.querySelector('input[name="payment_method"]:checked');
			if (!selected) return '';

			var methodItem = selected.closest('.wc_payment_method');
			if (!methodItem) return String(selected.value || '').trim();

			var label = methodItem.querySelector('label');
			if (!label) return String(selected.value || '').trim();
			return String(label.textContent || '').replace(/\s+/g, ' ').trim();
		}

		function isQrPayMongoSelected(checkoutForm) {
			if (!checkoutForm) return false;
			var selected = checkoutForm.querySelector('input[name="payment_method"]:checked');
			if (!selected) return false;

			var methodItem = selected.closest('.wc_payment_method');
			var labelText = methodItem ? String(methodItem.textContent || '') : '';
			var haystack = (String(selected.value || '') + ' ' + labelText).toLowerCase();

			return haystack.indexOf('paymongo') !== -1 && haystack.indexOf('qr') !== -1;
		}

		var payConfirmModal = document.getElementById('noyona-pay-confirm-modal');
		var payConfirmProceed = document.getElementById('noyona-pay-confirm-proceed');
		var payConfirmCloseNodes = payConfirmModal ? payConfirmModal.querySelectorAll('[data-pay-confirm-close]') : [];
		var payConfirmContinueCallback = null;

		function mountPayConfirmModal() {
			if (!payConfirmModal || payConfirmModal.parentElement === document.body) {
				return;
			}
			document.body.appendChild(payConfirmModal);
		}

		mountPayConfirmModal();

		function closePayConfirmModal() {
			if (!payConfirmModal) return;
			payConfirmModal.hidden = true;
			payConfirmContinueCallback = null;
			body.classList.remove('noyona-pay-confirm-open');
		}

		function openPayConfirmModal(onContinue) {
			if (!payConfirmModal) return false;
			mountPayConfirmModal();
			payConfirmContinueCallback = (typeof onContinue === 'function') ? onContinue : null;
			payConfirmModal.hidden = false;
			body.classList.add('noyona-pay-confirm-open');
			return true;
		}

		if (payConfirmCloseNodes && payConfirmCloseNodes.length) {
			payConfirmCloseNodes.forEach(function (node) {
				node.addEventListener('click', function () {
					closePayConfirmModal();
				});
			});
		}

		if (payConfirmProceed) {
			payConfirmProceed.addEventListener('click', function () {
				var callback = payConfirmContinueCallback;
				closePayConfirmModal();
				if (typeof callback === 'function') {
					callback();
				}
			});
		}

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape' && payConfirmModal && !payConfirmModal.hidden) {
				closePayConfirmModal();
			}
		});

		function syncReviewSnapshot(checkoutForm) {
			if (!isReviewStep || !checkoutForm) return;

			var shipNameNode = document.querySelector('[data-review-ship-name]');
			var shipAddressNode = document.querySelector('[data-review-ship-address]');
			var paymentNode = document.querySelector('[data-review-payment-method]');
			var emailNode = document.querySelector('[data-review-email]');
			var phoneNode = document.querySelector('[data-review-phone]');
			var specialInstructionsNode = document.querySelector('[data-review-special-instructions]');
			var noteRowNode = document.querySelector('[data-review-note-row]');
			var noteValueNode = document.querySelector('[data-review-order-note]');

			var firstName = readFieldValue(checkoutForm, 'billing_first_name');
			var lastName = readFieldValue(checkoutForm, 'billing_last_name');
			var shipName = (firstName + ' ' + lastName).trim();

			var addressParts = [
				readFieldValue(checkoutForm, 'shipping_address_1'),
				readFieldValue(checkoutForm, 'shipping_city'),
				readFieldValue(checkoutForm, 'shipping_state'),
				readFieldValue(checkoutForm, 'shipping_postcode')
			].filter(function (part) {
				return !!String(part || '').trim();
			});
			var shipAddress = addressParts.join(', ');

			var paymentMethod = readPaymentLabel(checkoutForm);
			var email = readFieldValue(checkoutForm, 'billing_email');
			var phone = readFieldValue(checkoutForm, 'billing_phone');

			if (shipNameNode) shipNameNode.textContent = shipName || 'Customer';
			if (shipAddressNode) shipAddressNode.textContent = shipAddress || 'Address details will appear here.';
			if (paymentNode) paymentNode.textContent = paymentMethod || 'Payment method';
			if (emailNode) emailNode.textContent = email || 'Email will appear here.';
			if (phoneNode) phoneNode.textContent = phone || 'Phone will appear here.';

			var orderNote = readFieldValue(checkoutForm, 'order_comments');
			if (noteValueNode) noteValueNode.textContent = orderNote;
			if (noteRowNode) noteRowNode.hidden = !orderNote;
			if (specialInstructionsNode) specialInstructionsNode.hidden = !orderNote;
		}

		function wireReviewTerms(checkoutForm) {
			if (!isReviewStep || !checkoutForm) return;
			var customTerms = document.getElementById('noyona-review-terms');
			var nativeTerms = checkoutForm.querySelector('#terms');
			if (!customTerms || !nativeTerms) return;

			customTerms.checked = !!nativeTerms.checked;
			customTerms.addEventListener('change', function () {
				if (customTerms.checked) {
					clearReviewTermsNotice();
				}
				nativeTerms.checked = !!customTerms.checked;
				nativeTerms.dispatchEvent(new Event('change', { bubbles: true }));
			});

			nativeTerms.addEventListener('change', function () {
				customTerms.checked = !!nativeTerms.checked;
				if (customTerms.checked) {
					clearReviewTermsNotice();
				}
			});
		}

		function clearReviewTermsNotice() {
			var existing = document.querySelector('.noyona-review-terms-notice');
			if (existing) {
				existing.remove();
			}
		}

		function showReviewTermsNotice() {
			clearReviewTermsNotice();

			var actions = document.querySelector('.noyona-checkout-actions');
			if (!actions || !actions.parentNode) return;

			var notice = document.createElement('ul');
			notice.className = 'woocommerce-error noyona-review-terms-notice';
			notice.setAttribute('role', 'alert');

			var item = document.createElement('li');
			item.textContent = <?php echo wp_json_encode( __( 'Please agree to the Terms of Service, Shipping Policy, and Refund Policy before placing your order.', 'noyona' ) ); ?>;
			notice.appendChild(item);

			actions.parentNode.insertBefore(notice, actions);
			if (typeof notice.scrollIntoView === 'function') {
				notice.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}

		function ensureStepLink(stepItem, href) {
			if (!stepItem || !href) return;
			if (stepItem.querySelector('a.noyona-checkout-steps__link')) return;

			var content = stepItem.innerHTML;
			stepItem.innerHTML = '';
			var link = document.createElement('a');
			link.className = 'noyona-checkout-steps__link';
			link.href = href;
			link.innerHTML = content;
			stepItem.appendChild(link);
		}

		// On the paid/Done page the order is final, so the stepper must be purely
		// informational: strip any clickable links and block navigation back to
		// cart/details/preview/payment.
		function lockCheckoutSteps() {
			var nav = document.querySelector('.noyona-checkout-steps');
			if (!nav) return;
			nav.classList.add('noyona-checkout-steps--locked');
			nav.querySelectorAll('a.noyona-checkout-steps__link').forEach(function (link) {
				var stepItem = link.parentNode;
				if (stepItem) {
					stepItem.innerHTML = link.innerHTML;
				}
			});
		}

		function submitCheckoutForm(checkoutForm, placeOrderButton) {
			if (!checkoutForm) return;

			// Preferred: trigger WooCommerce checkout submit flow.
			if (window.jQuery) {
				window.jQuery(checkoutForm).trigger('submit');
				return;
			}

			// Native fallback paths.
			if (typeof checkoutForm.requestSubmit === 'function') {
				if (placeOrderButton) {
					checkoutForm.requestSubmit(placeOrderButton);
				} else {
					checkoutForm.requestSubmit();
				}
				return;
			}

			if (placeOrderButton) {
				placeOrderButton.click();
				return;
			}

			checkoutForm.submit();
		}

		function findPendingQrGatewayBlock() {
			var blockWithHeading = Array.prototype.find.call(
				document.querySelectorAll('section, div, article'),
				function (node) {
					var text = String(node.textContent || '').toLowerCase();
					if (text.indexOf('scan qr code to pay') === -1) return false;
					return !!node.querySelector('img');
				}
			);
			if (blockWithHeading) return blockWithHeading;

			// Fallback: only return a paymongo-classed block that actually contains a QR
			// payload (canvas or qr-tagged image). This prevents grabbing GCash/Maya
			// branding blocks that share the "paymongo" class.
			var candidates = document.querySelectorAll('[class*="paymongo"], [id*="paymongo"], [class*="PayMongo"], [id*="PayMongo"]');
			for (var i = 0; i < candidates.length; i++) {
				var cand = candidates[i];
				if (cand.querySelector('canvas')) {
					return cand;
				}
				var imgs = cand.querySelectorAll('img');
				for (var j = 0; j < imgs.length; j++) {
					var src = String(imgs[j].getAttribute('src') || '').toLowerCase();
					var alt = String(imgs[j].getAttribute('alt') || '').toLowerCase();
					var cls = String(imgs[j].className || '').toLowerCase();
					if (
						src.indexOf('qr') !== -1 ||
						src.indexOf('qrcode') !== -1 ||
						src.indexOf('qrph') !== -1 ||
						alt.indexOf('qr') !== -1 ||
						alt.indexOf('qrcode') !== -1 ||
						cls.indexOf('qr') !== -1
					) {
						return cand;
					}
				}
			}
			return null;
		}

		function ensurePendingQrFallbackShell() {
			if (!isAwaitingPayment || document.querySelector('[data-noyona-awaiting-payment="1"]')) {
				return;
			}
			if (document.querySelector('.noyona-pay-fallback')) {
				return;
			}

			var orderRoot = document.querySelector('.woocommerce-order');
			var gatewayBlock = findPendingQrGatewayBlock();
			if (!orderRoot || !gatewayBlock) {
				return;
			}

			var shell = document.createElement('div');
			shell.className = 'noyona-pay-fallback';
			shell.innerHTML = '' +
				'<section class="noyona-pay-hero">' +
					'<div class="noyona-pay-hero__icon" aria-hidden="true"><i class="fa-solid fa-qrcode"></i></div>' +
					'<h1 class="noyona-pay-hero__title">Complete Your Payment</h1>' +
					'<p class="noyona-pay-hero__subtitle">Scan the QR code below to finish your order. The final Done page appears after payment succeeds.</p>' +
				'</section>' +
				'<section class="noyona-pay-card">' +
					'<div class="noyona-pay-card__head">' +
						'<h2 class="noyona-pay-card__title">Pay with QR Ph via PayMongo</h2>' +
					'</div>' +
					'<div class="noyona-pay-card__gateway"></div>' +
					'<p class="noyona-pay-refresh-note">This page refreshes automatically while waiting for payment confirmation.</p>' +
				'</section>';

			var gatewayMount = shell.querySelector('.noyona-pay-card__gateway');
			if (!gatewayMount) {
				return;
			}

			gatewayMount.appendChild(gatewayBlock);
			orderRoot.insertBefore(shell, orderRoot.firstChild);
		}

		function getOrderIdFromPath() {
			var match = window.location.pathname.match(/\/order-received\/(\d+)/);
			if (!match || !match[1]) return 0;
			var value = parseInt(match[1], 10);
			return Number.isFinite(value) ? value : 0;
		}

		function getOrderKeyFromQuery() {
			try {
				var params = new URLSearchParams(window.location.search || '');
				return String(params.get('key') || '').trim();
			} catch (e) {
				return '';
			}
		}

		var paymentPollInFlight = false;
		var paymentPollIntervalMs = 25000;

		function schedulePaymentStatusPoll(delayMs) {
			window.setTimeout(function () {
				pollPaymentStatus();
			}, delayMs);
		}

		function pollPaymentStatus() {
			if (paymentPollInFlight) {
				return;
			}

			var orderId = getOrderIdFromPath();
			var orderKey = getOrderKeyFromQuery();
			if (!orderId || !orderKey || !orderStatusProbeUrl) {
				schedulePaymentStatusPoll(paymentPollIntervalMs);
				return;
			}

			if (!window.fetch || !window.URLSearchParams) {
				window.location.reload();
				return;
			}

			paymentPollInFlight = true;
			var payload = new URLSearchParams();
			payload.set('action', 'noyona_check_order_payment_status');
			payload.set('order_id', String(orderId));
			payload.set('order_key', orderKey);

			window.fetch(orderStatusProbeUrl, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
				},
				credentials: 'same-origin',
				body: payload.toString()
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('status check failed');
				}
				return response.json();
			}).then(function (json) {
				var awaiting = !!(json && json.success && json.data && json.data.awaiting_payment);
				if (!awaiting) {
					window.location.reload();
					return;
				}
				schedulePaymentStatusPoll(paymentPollIntervalMs);
			}).catch(function () {
				schedulePaymentStatusPoll(paymentPollIntervalMs);
			}).finally(function () {
				paymentPollInFlight = false;
			});
		}

		if (isOrderPayPath) {
			body.classList.add('noyona-pay-step');
			body.classList.remove('noyona-done-step');
			body.classList.remove('noyona-review-step');
			body.classList.remove('noyona-details-step');
			ensureCheckoutStepper();

			var orderPayStepItems = document.querySelectorAll('.noyona-checkout-steps li');
			if (orderPayStepItems.length >= 4) {
				ensureStepLink(orderPayStepItems[0], cartUrl);
				ensureStepLink(orderPayStepItems[1], detailsUrl);
				ensureStepLink(orderPayStepItems[2], reviewUrl);
				orderPayStepItems.forEach(function (item) {
					item.classList.remove('is-active');
					item.classList.remove('is-complete');
					item.classList.remove('is-pending');
				});
				if (orderPayStepItems.length >= 5) {
					orderPayStepItems[0].classList.add('is-complete');
					orderPayStepItems[1].classList.add('is-complete');
					orderPayStepItems[2].classList.add('is-complete');
					orderPayStepItems[3].classList.add('is-active');
				} else {
					orderPayStepItems[0].classList.add('is-complete');
					orderPayStepItems[1].classList.add('is-complete');
					orderPayStepItems[2].classList.add('is-complete');
					orderPayStepItems[3].classList.add('is-active');
				}
			}
		} else if (isAwaitingPayment) {
			body.classList.add('noyona-pay-step');
			body.classList.remove('noyona-done-step');
			body.classList.remove('noyona-review-step');
			body.classList.remove('noyona-details-step');
			ensureCheckoutStepper();
			ensurePendingQrFallbackShell();
			schedulePaymentStatusPoll(12000);

			var payStepItems = document.querySelectorAll('.noyona-checkout-steps li');
			if (payStepItems.length >= 4) {
				ensureStepLink(payStepItems[0], cartUrl);
				ensureStepLink(payStepItems[1], detailsUrl);
				ensureStepLink(payStepItems[2], reviewUrl);
				payStepItems.forEach(function (item) {
					item.classList.remove('is-active');
					item.classList.remove('is-complete');
					item.classList.remove('is-pending');
				});
				if (payStepItems.length >= 5) {
					payStepItems[0].classList.add('is-complete');
					payStepItems[1].classList.add('is-complete');
					payStepItems[2].classList.add('is-complete');
					payStepItems[3].classList.add('is-active');
				} else {
					payStepItems[0].classList.add('is-complete');
					payStepItems[1].classList.add('is-complete');
					payStepItems[2].classList.add('is-complete');
					payStepItems[3].classList.add('is-active');
				}
			}
		} else if (isDoneStep) {
			body.classList.add('noyona-done-step');
			body.classList.remove('noyona-pay-step');
			body.classList.remove('noyona-review-step');
			body.classList.remove('noyona-details-step');
			ensureCheckoutStepper();

			var doneStepItems = document.querySelectorAll('.noyona-checkout-steps li');
			if (doneStepItems.length >= 4) {
				lockCheckoutSteps();
				doneStepItems.forEach(function (item) {
					item.classList.remove('is-active');
					item.classList.remove('is-pending');
				});
				if (doneStepItems.length >= 5) {
					doneStepItems[0].classList.add('is-complete');
					doneStepItems[1].classList.add('is-complete');
					doneStepItems[2].classList.add('is-complete');
					doneStepItems[3].classList.add('is-complete');
					doneStepItems[4].classList.add('is-active');
				} else {
					doneStepItems[0].classList.add('is-complete');
					doneStepItems[1].classList.add('is-complete');
					doneStepItems[2].classList.add('is-complete');
					doneStepItems[3].classList.add('is-active');
				}
			}
		} else if (isReviewStep) {
			applyReviewStepUI();
		} else {
			applyDetailsStepUI();
		}

		var reviewUiWired = false;

		function applyReviewStepUI() {
			body.classList.add('noyona-review-step');
			body.classList.remove('noyona-pay-step');
			body.classList.remove('noyona-details-step');
			body.classList.remove('noyona-done-step');
			ensureCheckoutStepper();
			syncReviewSnapshot(form);
			if (!reviewUiWired) {
				wireReviewTerms(form);
				reviewUiWired = true;
			}

			var stepItems = document.querySelectorAll('.noyona-checkout-steps li');
			if (stepItems.length >= 3) {
				ensureStepLink(stepItems[0], cartUrl);
				ensureStepLink(stepItems[1], detailsUrl);
				stepItems.forEach(function (item) {
					item.classList.remove('is-active');
					item.classList.remove('is-complete');
					item.classList.remove('is-pending');
				});
				stepItems[0].classList.add('is-complete');
				stepItems[1].classList.add('is-complete');
				stepItems[2].classList.add('is-active');
			}
		}

		function applyDetailsStepUI() {
			body.classList.add('noyona-details-step');
			body.classList.remove('noyona-pay-step');
			body.classList.remove('noyona-review-step');
			body.classList.remove('noyona-done-step');
			ensureCheckoutStepper();

			var detailsStepItems = document.querySelectorAll('.noyona-checkout-steps li');
			if (detailsStepItems.length >= 2) {
				ensureStepLink(detailsStepItems[0], cartUrl);
				detailsStepItems.forEach(function (item) {
					item.classList.remove('is-active');
					item.classList.remove('is-complete');
					item.classList.remove('is-pending');
				});
				detailsStepItems[0].classList.add('is-complete');
				detailsStepItems[1].classList.add('is-active');
			}
		}

		function updateReviewButtonLabel() {
			var btn = document.getElementById('noyona-review-order');
			if (!btn) return;
			if (isReviewStep) {
				btn.innerHTML = '<span class="noyona-checkout-actions__icon" aria-hidden="true"></span> PLACE ORDER';
			} else {
				btn.innerHTML = 'PREVIEW ORDER <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>';
			}
		}

		function goToReviewStep() {
			isReviewStep = true;
			applyReviewStepUI();
			updateReviewButtonLabel();
			if (window.scrollTo) {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		}

		function goToDetailsStep() {
			isReviewStep = false;
			clearReviewTermsNotice();
			applyDetailsStepUI();
			updateReviewButtonLabel();
			if (window.scrollTo) {
				window.scrollTo({ top: 0, behavior: 'smooth' });
			}
		}

		/* 3. Review Order button → switch to in-page review, then place order */
		var reviewBtn = document.getElementById('noyona-review-order');
		var placeOrder = document.getElementById('place_order');
		if (reviewBtn) {
			var qrPaymentConfirmed = false;
			updateReviewButtonLabel();

			reviewBtn.addEventListener('click', function() {
				// Details → review: validate, then reveal the review panel in-page.
				// No navigation, so card fields (and their values) are preserved.
				if (!isReviewStep) {
					var validation = validateNoyonaCheckoutDetails(form);
					if (!validation.ok) {
						renderNoyonaCheckoutValidationNotice(form, validation);
						focusFirstInvalidField(validation.firstField);
						return;
					}
					if (form && typeof form.reportValidity === 'function' && !form.reportValidity()) {
						return;
					}
					clearNoyonaCheckoutValidationNotice(form);
					goToReviewStep();
					return;
				}

				// Review → place order.
				var customTerms = document.getElementById('noyona-review-terms');
				if (customTerms && !customTerms.checked) {
					showReviewTermsNotice();
					customTerms.focus();
					return;
				}
				clearReviewTermsNotice();
				if (form) {
					var nativeTerms = form.querySelector('#terms');
					if (nativeTerms) {
						nativeTerms.checked = true;
						nativeTerms.dispatchEvent(new Event('change', { bubbles: true }));
					}
				}

				if (isQrPayMongoSelected(form) && !qrPaymentConfirmed) {
					if (openPayConfirmModal(function () {
						qrPaymentConfirmed = true;
						reviewBtn.click();
					})) {
						return;
					}
				}
				qrPaymentConfirmed = false;

				// Local/dev temporary bypass to preview the "Done" page without payment gateway validation.
				if (allowDonePreviewBypass && donePreviewUrl) {
					window.location.assign(donePreviewUrl);
					return;
				}
				submitCheckoutForm(form, placeOrder);
			});
		}

		/* Back button: in review → return to details in-page; in details → cart. */
		var checkoutBackBtn = document.querySelector('.noyona-checkout-actions__back');
		if (checkoutBackBtn) {
			checkoutBackBtn.addEventListener('click', function (e) {
				if (isReviewStep) {
					e.preventDefault();
					goToDetailsStep();
				}
			});
		}

		function getSelectedPaymentMethod(checkoutForm) {
			if (!checkoutForm) return '';
			var checked = checkoutForm.querySelector('input[name="payment_method"]:checked');
			return checked ? String(checked.value || '') : '';
		}

		function normalizeCardDigits(value) {
			return String(value || '').replace(/\D+/g, '');
		}

		function isValidLuhnNumber(digits) {
			if (!/^\d{13,19}$/.test(digits || '')) return false;

			var sum = 0;
			var shouldDouble = false;
			for (var i = digits.length - 1; i >= 0; i--) {
				var digit = parseInt(digits.charAt(i), 10);
				if (shouldDouble) {
					digit *= 2;
					if (digit > 9) digit -= 9;
				}
				sum += digit;
				shouldDouble = !shouldDouble;
			}

			return sum % 10 === 0;
		}

		function isValidCardExpiry(value) {
			var match = String(value || '').trim().match(/^(\d{1,2})\s*\/\s*(\d{2}|\d{4})$/);
			if (!match) return false;

			var month = parseInt(match[1], 10);
			var year = parseInt(match[2], 10);
			if (year < 100) year += 2000;
			if (month < 1 || month > 12) return false;

			var now = new Date();
			var currentMonth = now.getMonth() + 1;
			var currentYear = now.getFullYear();
			return year > currentYear || (year === currentYear && month >= currentMonth);
		}

		function addCheckoutValidationError(errors, field, message) {
			if (field) {
				markCheckoutFieldRow(field, true);
			}
			errors.push({ field: field || null, message: message });
		}

		function markCheckoutFieldValid(field) {
			if (field) {
				markCheckoutFieldRow(field, false);
			}
		}

		function validatePayMongoCardFields(checkoutForm, errors) {
			var paymentMethod = getSelectedPaymentMethod(checkoutForm);
			if (!paymentMethod) {
				var firstMethod = checkoutForm.querySelector('input[name="payment_method"]');
				addCheckoutValidationError(errors, firstMethod, 'Please select a payment method.');
				return;
			}

			var isInstallment = paymentMethod === 'paymongo_card_installment';
			var isCard = paymentMethod === 'paymongo' || isInstallment;
			if (!isCard) return;

			var cardNumber = checkoutForm.querySelector(isInstallment ? '#paymongo_cc_installment_ccNo' : '#paymongo_ccNo');
			var expiry = checkoutForm.querySelector(isInstallment ? '#paymongo_cc_installment_expdate' : '#paymongo_expdate');
			var cvc = checkoutForm.querySelector(isInstallment ? '#paymongo_cc_installment_cvv' : '#paymongo_cvv');
			var cardDigits = normalizeCardDigits(cardNumber ? cardNumber.value : '');
			var cvcDigits = normalizeCardDigits(cvc ? cvc.value : '');

			if (!cardNumber || !cardDigits) {
				addCheckoutValidationError(errors, cardNumber, 'Card number is required.');
			} else if (!isValidLuhnNumber(cardDigits)) {
				addCheckoutValidationError(errors, cardNumber, 'Please enter a valid card number.');
			} else {
				markCheckoutFieldValid(cardNumber);
			}

			if (!expiry || !String(expiry.value || '').trim()) {
				addCheckoutValidationError(errors, expiry, 'Card expiry date is required.');
			} else if (!isValidCardExpiry(expiry.value)) {
				addCheckoutValidationError(errors, expiry, 'Please enter a valid future expiry date (MM/YY).');
			} else {
				markCheckoutFieldValid(expiry);
			}

			if (!cvc || !cvcDigits) {
				addCheckoutValidationError(errors, cvc, 'Card security code is required.');
			} else if (!/^\d{3,4}$/.test(cvcDigits)) {
				addCheckoutValidationError(errors, cvc, 'Please enter a valid 3-4 digit card security code.');
			} else {
				markCheckoutFieldValid(cvc);
			}

			if (isInstallment) {
				var issuer = checkoutForm.querySelector('#paymongo_cc_installment_issuer');
				var tenure = checkoutForm.querySelector('input[name="paymongo_cc_installment_tenure"]:checked');
				var firstTenure = checkoutForm.querySelector('input[name="paymongo_cc_installment_tenure"]');

				if (issuer && !String(issuer.value || '').trim()) {
					addCheckoutValidationError(errors, issuer, 'Please select an installment bank.');
				} else {
					markCheckoutFieldValid(issuer);
				}

				if (firstTenure && !tenure) {
					addCheckoutValidationError(errors, firstTenure, 'Please select an installment plan.');
				} else {
					markCheckoutFieldValid(firstTenure);
				}
			}
		}

		function validateNoyonaCheckoutDetails(checkoutForm) {
			if (!checkoutForm) {
				return { ok: false, errors: [{ message: 'Checkout form not found.' }], firstField: null };
			}

			var requiredFields = [
				{ name: 'billing_first_name', label: 'First name' },
				{ name: 'billing_last_name',  label: 'Last name' },
				{ name: 'billing_email',      label: 'Email address' },
				{ name: 'billing_phone',      label: 'Phone number' },
				{ name: 'shipping_address_1', label: 'Shipping address' },
				{ name: 'shipping_city',      label: 'City' },
				{ name: 'shipping_state',     label: 'Province / State' },
				{ name: 'shipping_postcode',  label: 'ZIP code' }
			];

			var errors = [];
			var firstField = null;
			var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			var phonePattern = /^(\+?63|0)?9\d{9}$/;
			var zipPattern   = /^\d{4,5}$/;

			requiredFields.forEach(function(spec) {
				var field = checkoutForm.querySelector('[name="' + spec.name + '"]');
				if (!field) return;
				var value = String(field.value || '').trim();
				if (!value) {
					markCheckoutFieldRow(field, true);
					errors.push({ field: field, message: spec.label + ' is required.' });
					if (!firstField) firstField = field;
				} else {
					markCheckoutFieldRow(field, false);
				}
			});

			var emailField = checkoutForm.querySelector('[name="billing_email"]');
			if (emailField) {
				var emailValue = String(emailField.value || '').trim();
				if (emailValue && !emailPattern.test(emailValue)) {
					markCheckoutFieldRow(emailField, true);
					errors.push({ field: emailField, message: 'Please enter a valid email address.' });
					if (!firstField) firstField = emailField;
				}
			}

			var phoneField = checkoutForm.querySelector('[name="billing_phone"]');
			if (phoneField) {
				var rawPhone = String(phoneField.value || '').replace(/[\s\-()]/g, '');
				if (rawPhone && !phonePattern.test(rawPhone)) {
					markCheckoutFieldRow(phoneField, true);
					errors.push({ field: phoneField, message: 'Please enter a valid Philippine phone number (e.g. 09171234567).' });
					if (!firstField) firstField = phoneField;
				}
			}

			var postcodeField = checkoutForm.querySelector('[name="shipping_postcode"]');
			if (postcodeField) {
				var rawZip = String(postcodeField.value || '').trim();
				if (rawZip && !zipPattern.test(rawZip)) {
					markCheckoutFieldRow(postcodeField, true);
					errors.push({ field: postcodeField, message: 'Please enter a valid ZIP code (4-5 digits).' });
					if (!firstField) firstField = postcodeField;
				}
			}

			validatePayMongoCardFields(checkoutForm, errors);

			// Sort errors by DOM document order so the "first" error always
			// matches the topmost-on-screen field, regardless of push order
			// (required-field checks vs. format checks).
			errors.sort(function(a, b) {
				if (!a.field || !b.field) return 0;
				if (a.field === b.field) return 0;
				var pos = a.field.compareDocumentPosition(b.field);
				if (pos & Node.DOCUMENT_POSITION_FOLLOWING) return -1;
				if (pos & Node.DOCUMENT_POSITION_PRECEDING) return 1;
				return 0;
			});
			firstField = errors.length > 0 ? (errors[0].field || firstField) : firstField;

			return {
				ok: errors.length === 0,
				errors: errors,
				firstField: firstField
			};
		}

		function markCheckoutFieldRow(field, isInvalid) {
			if (!field) return;
			var row = field.closest('.form-row');
			if (!row) return;
			if (isInvalid) {
				row.classList.add('woocommerce-invalid');
				row.classList.add('woocommerce-invalid-required-field');
				row.classList.remove('woocommerce-validated');
			} else {
				row.classList.remove('woocommerce-invalid');
				row.classList.remove('woocommerce-invalid-required-field');
				row.classList.remove('woocommerce-invalid-email');
				row.classList.remove('woocommerce-invalid-phone');
				row.classList.add('woocommerce-validated');
			}
		}

		function focusFirstInvalidField(field) {
			if (!field) return;
			try {
				field.focus({ preventScroll: true });
			} catch (e) {
				field.focus();
			}
			if (typeof field.scrollIntoView === 'function') {
				field.scrollIntoView({ behavior: 'smooth', block: 'center' });
			}
		}

		function clearNoyonaCheckoutValidationNotice(checkoutForm) {
			var scope = checkoutForm && checkoutForm.parentNode ? checkoutForm.parentNode : document;
			var existing = scope.querySelector('.noyona-checkout-validation-notice');
			if (existing) existing.remove();
		}

		function renderNoyonaCheckoutValidationNotice(checkoutForm, validation) {
			if (!checkoutForm) return;
			clearNoyonaCheckoutValidationNotice(checkoutForm);

			var notice = document.createElement('div');
			notice.className = 'woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout noyona-checkout-validation-notice';
			notice.setAttribute('role', 'alert');

			var list = document.createElement('ul');
			list.className = 'woocommerce-error';
			list.setAttribute('role', 'alert');

			// Show only the first error so the toast surfaces issues one-at-a-time,
			// top-to-bottom, as the user resolves each field.
			var firstError = (validation.errors && validation.errors[0]) || null;
			var firstMessage = firstError && firstError.message
				? String(firstError.message)
				: 'Please correct the highlighted fields.';
			var item = document.createElement('li');
			item.textContent = firstMessage;
			list.appendChild(item);

			notice.appendChild(list);

			var anchor = checkoutForm.querySelector('.noyona-checkout-card--contact') || checkoutForm.firstElementChild;
			if (anchor && anchor.parentNode) {
				anchor.parentNode.insertBefore(notice, anchor);
			} else {
				checkoutForm.insertBefore(notice, checkoutForm.firstChild);
			}
		}

		// Always attach the live snapshot listeners; syncReviewSnapshot() no-ops
		// while in details mode and updates the panel once review mode is active.
		if (form) {
			form.addEventListener('input', function () {
				syncReviewSnapshot(form);
			});
			form.addEventListener('change', function () {
				syncReviewSnapshot(form);
			});
			if (window.jQuery) {
				window.jQuery(document.body).on('updated_checkout', function() {
					syncReviewSnapshot(form);
					if (pendingCouponSummaryRefresh) {
						pendingCouponSummaryRefresh = false;
						refreshCheckoutSummaryFromPage();
					}
				});
				window.jQuery(document.body).on('removed_coupon_in_checkout applied_coupon_in_checkout', function() {
					queueCheckoutSummaryRefresh();
				});
			}
		}

		/* 4. Shipping address helpers (saved addresses + geolocation) */
		function triggerCheckoutRefresh() {
			if (window.jQuery) {
				window.jQuery(document.body).trigger('update_checkout');
			}
			if (isReviewStep && form) {
				syncReviewSnapshot(form);
			}
		}

		// Aliases for province labels that aren't WC's canonical state list — used
		// only when a saved address still holds a pre-normalized free-text value.
		var SHIPPING_STATE_ALIASES = {
			'ncr': '00',
			'mm': '00',
			'metro manila': '00',
			'national capital region': '00',
			'manila': '00'
		};

		function setCheckoutFieldValue(fieldName, rawValue) {
			var value = String(rawValue || '').trim();
			var selector = '[name="' + fieldName + '"]';
			var field = (form && form.querySelector(selector)) || document.querySelector(selector);
			if (!field) return false;

			function dispatchFieldEvents(target) {
				target.dispatchEvent(new Event('input', { bubbles: true }));
				target.dispatchEvent(new Event('change', { bubbles: true }));
			}

			if (field.tagName === 'SELECT') {
				var normalized = value.toLowerCase();
				var matched = false;

				if (fieldName === 'shipping_state' && SHIPPING_STATE_ALIASES[normalized]) {
					var aliasValue = SHIPPING_STATE_ALIASES[normalized];
					for (var aliasIdx = 0; aliasIdx < field.options.length; aliasIdx++) {
						if (String(field.options[aliasIdx].value || '') === aliasValue) {
							field.value = aliasValue;
							matched = true;
							break;
						}
					}
				}

				for (var i = 0; !matched && i < field.options.length; i++) {
					var option = field.options[i];
					var optionValue = String(option.value || '').trim();
					var optionLabel = String(option.textContent || option.innerText || '').trim();
					if (optionValue.toLowerCase() === normalized || optionLabel.toLowerCase() === normalized) {
						field.value = optionValue;
						matched = true;
						break;
					}
				}

				if (!matched && value) {
					for (var j = 0; j < field.options.length; j++) {
						var fuzzyLabel = String(field.options[j].textContent || field.options[j].innerText || '').trim().toLowerCase();
						if (fuzzyLabel && (fuzzyLabel.indexOf(normalized) !== -1 || normalized.indexOf(fuzzyLabel) !== -1)) {
							field.value = String(field.options[j].value || '');
							matched = true;
							break;
						}
					}
				}

				if (!matched && value && field.querySelector('option[value=""]')) {
					var customOption = document.createElement('option');
					customOption.value = value;
					customOption.textContent = value;
					field.appendChild(customOption);
					field.value = value;
				}
				dispatchFieldEvents(field);
				return true;
			}

			field.value = value;
			dispatchFieldEvents(field);
			return true;
		}

		function applyShippingAddress(addressPayload) {
			if (!addressPayload) return;
			setCheckoutFieldValue('shipping_address_1', addressPayload.address);
			setCheckoutFieldValue('shipping_city', addressPayload.city);
			setCheckoutFieldValue('shipping_state', addressPayload.province);
			setCheckoutFieldValue('shipping_postcode', addressPayload.zip_code);
			triggerCheckoutRefresh();
		}

		var savedAddressNode = document.getElementById('noyona-saved-addresses-data');
		var savedAddressSelect = document.getElementById('noyona-saved-address-select');
		var savedAddressMap = {};
		if (savedAddressNode && savedAddressSelect) {
			try {
				var parsedAddresses = JSON.parse(savedAddressNode.textContent || '[]');
				if (Array.isArray(parsedAddresses)) {
					parsedAddresses.forEach(function(item) {
						if (!item || !item.id) return;
						savedAddressMap[String(item.id)] = item;
					});
				}
			} catch (e) {
				savedAddressMap = {};
			}

			savedAddressSelect.addEventListener('change', function() {
				var selectedId = String(savedAddressSelect.value || '').trim();
				if (!selectedId || !savedAddressMap[selectedId]) return;
				applyShippingAddress(savedAddressMap[selectedId]);
			});
		}

		function setLocationButtonState(locBtn, state) {
			if (!locBtn) return;
			if (state === 'loading') {
				locBtn.classList.add('is-loading');
				locBtn.classList.remove('is-done');
				locBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs" aria-hidden="true"></i> Locating...';
				return;
			}
			if (state === 'done') {
				locBtn.classList.remove('is-loading');
				locBtn.classList.add('is-done');
				locBtn.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Address applied';
				window.setTimeout(function() {
					locBtn.classList.remove('is-done');
					locBtn.innerHTML = '<i class="fa-solid fa-location-dot" aria-hidden="true"></i> Use current address';
				}, 2200);
				return;
			}
			locBtn.classList.remove('is-loading');
			locBtn.classList.remove('is-done');
			locBtn.innerHTML = '<i class="fa-solid fa-location-dot" aria-hidden="true"></i> Use current address';
		}

		function buildAddressFromReverseGeocode(payload) {
			var addressData = (payload && payload.address) ? payload.address : {};
			var streetParts = [];
			var houseNo = String(addressData.house_number || '').trim();
			var road = String(addressData.road || addressData.pedestrian || addressData.neighbourhood || '').trim();
			if (houseNo) streetParts.push(houseNo);
			if (road) streetParts.push(road);
			var addressLine = streetParts.join(' ').trim();
			if (!addressLine) {
				addressLine = String(addressData.suburb || addressData.village || addressData.hamlet || payload.display_name || '').split(',')[0].trim();
			}

			var city = String(
				addressData.city ||
				addressData.town ||
				addressData.municipality ||
				addressData.village ||
				addressData.suburb ||
				''
			).trim();
			var province = String(addressData.state || addressData.region || addressData.county || '').trim();
			var zipCode = String(addressData.postcode || '').trim();

			return {
				address: addressLine,
				city: city,
				province: province,
				zip_code: zipCode
			};
		}

		var locBtn = document.getElementById('noyona-use-location');
		if (locBtn) {
			locBtn.addEventListener('click', function() {
				if (!navigator.geolocation || !window.fetch) {
					return;
				}

				setLocationButtonState(locBtn, 'loading');
				navigator.geolocation.getCurrentPosition(
					function(pos) {
						var lat = Number(pos && pos.coords ? pos.coords.latitude : 0);
						var lng = Number(pos && pos.coords ? pos.coords.longitude : 0);
						if (!Number.isFinite(lat) || !Number.isFinite(lng) || (!lat && !lng)) {
							setLocationButtonState(locBtn, 'reset');
							return;
						}

						var reverseUrl = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&lat=' + encodeURIComponent(String(lat)) + '&lon=' + encodeURIComponent(String(lng));
						window.fetch(reverseUrl, {
							method: 'GET',
							headers: {
								'Accept': 'application/json'
							},
							credentials: 'omit'
						}).then(function(response) {
							if (!response.ok) {
								throw new Error('reverse geocode failed');
							}
							return response.json();
						}).then(function(payload) {
							var fromGeo = buildAddressFromReverseGeocode(payload);
							if (!fromGeo.address && !fromGeo.city && !fromGeo.province && !fromGeo.zip_code) {
								throw new Error('empty geocode payload');
							}
							applyShippingAddress(fromGeo);
							setLocationButtonState(locBtn, 'done');
						}).catch(function() {
							setLocationButtonState(locBtn, 'reset');
						});
					},
					function() {
						setLocationButtonState(locBtn, 'reset');
					},
					{ timeout: 10000, enableHighAccuracy: true, maximumAge: 0 }
				);
			});
		}
	})();
	</script>
	<?php
}
/* ----- One-time stale order-confirmation template reset ----- */
/**
 * One-time safeguard:
 * Remove stale DB-saved order confirmation templates so the theme file
 * templates/order-confirmation.html is used consistently.
 */
add_action( 'init', 'noyona_one_time_reset_order_confirmation_template_override', 31 );
function noyona_one_time_reset_order_confirmation_template_override() {
    $safeguard_version = '1';
    $option_key        = 'noyona_order_confirmation_template_safeguard_version';
    if ( $safeguard_version === get_option( $option_key, '' ) ) {
        return;
    }

    if ( ! post_type_exists( 'wp_template' ) ) {
        update_option( $option_key, $safeguard_version, false );
        return;
    }

    $theme_terms = array_values(
        array_unique(
            array_filter(
                array(
                    get_stylesheet(),
                    get_template(),
                )
            )
        )
    );

    $query_args = array(
        'post_type'      => 'wp_template',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'name'           => 'order-confirmation',
    );

    if ( ! empty( $theme_terms ) ) {
        $query_args['tax_query'] = array(
            array(
                'taxonomy' => 'wp_theme',
                'field'    => 'name',
                'terms'    => $theme_terms,
            ),
        );
    }

    $template_ids = get_posts( $query_args );
    if ( ! empty( $template_ids ) ) {
        foreach ( $template_ids as $template_id ) {
            wp_delete_post( (int) $template_id, true );
        }
    }

    update_option( $option_key, $safeguard_version, false );
}

/* ----- Strip <br> from .noyona-pay-meta block markup ----- */
add_filter( 'render_block', 'noyona_strip_checkout_pay_meta_breaks', 35, 2 );
function noyona_strip_checkout_pay_meta_breaks( $block_content, $block ) {
    if ( is_admin() || '' === trim( (string) $block_content ) ) {
        return $block_content;
    }

    if ( false === strpos( (string) $block_content, 'noyona-pay-meta' ) ) {
        return $block_content;
    }

    return preg_replace_callback(
        '/(<section\b[^>]*class=(["\'])[^"\']*\bnoyona-pay-meta\b[^"\']*\2[^>]*>)(.*?)(<\/section>)/is',
        function ( $matches ) {
            $inner = preg_replace( '/\s*<br\s*\/?>\s*/i', '', (string) $matches[3] );
            return $matches[1] . $inner . $matches[4];
        },
        (string) $block_content
    );
}

/* ----- Force /checkout/ as Woo checkout URL ----- */
add_filter( 'woocommerce_get_checkout_url', 'noyona_force_checkout_url', 99 );
function noyona_force_checkout_url( $url ) {
    return esc_url_raw( home_url( '/checkout/' ) );
}

/* ----- Checkout footer: remove empty <p> tags ----- */
add_action( 'wp_footer', 'noyona_render_checkout_empty_paragraph_cleanup' );
function noyona_render_checkout_empty_paragraph_cleanup() {
    if (!is_checkout()) return;
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const paragraphs = document.querySelectorAll('p');

        paragraphs.forEach(function (p) {
            // Ignore important WooCommerce rows
            if (p.classList.contains('form-row')) return;

            // Check if empty (no text and no children except whitespace)
            if (
                p.textContent.trim() === '' &&
                p.children.length === 0
            ) {
                p.remove();
            }
        });
    });
    </script>
    <?php
}

/* ----- Global checkout login modal ----- */
add_action( 'wp_footer', 'noyona_render_global_checkout_login_modal', 40 );
function noyona_render_global_checkout_login_modal() {
    if ( is_admin() || is_user_logged_in() ) {
        return;
    }

    $login_action_url = wp_login_url();
    $register_url     = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_registration_url();
    $redirect_to      = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' );
    $google_login_url = noyona_get_google_login_url( $redirect_to );
    ?>
    <div
        id="noyona-global-checkout-login-modal"
        class="noyona-mini-cart-login-modal"
        data-mini-cart-login-modal-global
        hidden
    >
        <button class="noyona-mini-cart-login-backdrop" type="button" data-mini-cart-login-close aria-label="<?php esc_attr_e( 'Close login modal', 'noyona-childtheme' ); ?>"></button>
        <div class="noyona-mini-cart-login-dialog" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Log in before checkout', 'noyona-childtheme' ); ?>">
            <button class="noyona-mini-cart-login-close" type="button" data-mini-cart-login-close aria-label="<?php esc_attr_e( 'Close login modal', 'noyona-childtheme' ); ?>">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
            <div class="noyona-mini-cart-login-icon" aria-hidden="true">
                <i class="fa-solid fa-lock"></i>
            </div>

            <h3 class="noyona-mini-cart-login-title"><?php esc_html_e( 'Log In to continue checkout', 'noyona-childtheme' ); ?></h3>
            <p class="noyona-mini-cart-login-copy"><?php esc_html_e( 'Please log in to your account before checking out.', 'noyona-childtheme' ); ?></p>

            <form class="noyona-mini-cart-login-form" method="post" action="<?php echo esc_url( $login_action_url ); ?>">
                <label for="noyona-mini-cart-login-email"><?php esc_html_e( 'Email', 'noyona-childtheme' ); ?></label>
                <input id="noyona-mini-cart-login-email" name="log" type="text" required placeholder="<?php esc_attr_e( 'your@email.com', 'noyona-childtheme' ); ?>" />

                <label for="noyona-mini-cart-login-password"><?php esc_html_e( 'Password', 'noyona-childtheme' ); ?></label>
                <input id="noyona-mini-cart-login-password" name="pwd" type="password" required placeholder="<?php esc_attr_e( 'Enter your password', 'noyona-childtheme' ); ?>" />

                <input type="hidden" name="redirect_to" data-mini-cart-login-redirect value="<?php echo esc_url( $redirect_to ); ?>" />
                <button type="submit" class="noyona-mini-cart-login-submit"><?php esc_html_e( 'Log In', 'noyona-childtheme' ); ?></button>
            </form>

            <div class="noyona-mini-cart-login-separator" aria-hidden="true">
                <span></span><em><?php esc_html_e( 'or', 'noyona-childtheme' ); ?></em><span></span>
            </div>

            <a class="noyona-mini-cart-login-google" data-mini-cart-login-action href="<?php echo esc_url( $google_login_url ); ?>">
                <i class="fa-brands fa-google" aria-hidden="true"></i>
                <span><?php esc_html_e( 'Login with Google', 'noyona-childtheme' ); ?></span>
            </a>

            <a class="noyona-mini-cart-login-register" data-mini-cart-register-action href="<?php echo esc_url( $register_url ); ?>">
                <?php esc_html_e( 'Create an Account', 'noyona-childtheme' ); ?>
            </a>

            <p class="noyona-mini-cart-login-note"><?php esc_html_e( 'You must be logged in to proceed to checkout.', 'noyona-childtheme' ); ?></p>
        </div>
    </div>
    <?php
}

