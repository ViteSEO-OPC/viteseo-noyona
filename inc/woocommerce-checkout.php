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

	if ( is_page( array( 'checkout', 'reviews' ) ) ) {
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

	$reviews_path = (string) wp_parse_url( home_url( '/reviews/' ), PHP_URL_PATH );
	$reviews_lc   = trim( strtolower( untrailingslashit( $reviews_path ) ), '/' );
	if ( '' !== $reviews_lc && ( $request_lc === $reviews_lc || 0 === strpos( $request_lc, $reviews_lc . '/' ) ) ) {
		return true;
	}

	return false;
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
add_filter( 'woocommerce_locate_template', 'noyona_force_checkout_templates', 50, 3 );
function noyona_force_checkout_templates( $template, $template_name, $template_path ) {
	$forced_templates = array(
		'checkout/form-checkout.php',
		'checkout/thankyou.php',
	);

	if ( ! in_array( $template_name, $forced_templates, true ) ) {
		return $template;
	}

	$forced = trailingslashit( get_stylesheet_directory() ) . 'woocommerce/' . $template_name;
	if ( file_exists( $forced ) ) {
		return $forced;
	}

	return $template;
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
		'noyona-checkout',
		get_stylesheet_directory_uri() . '/assets/css/noyona-checkout.css',
		array( 'woocom-ct-style', 'woocom-ct-header' ),
		file_exists( $checkout_css_path ) ? (string) filemtime( $checkout_css_path ) : wp_get_theme()->get( 'Version' )
	);

	wp_enqueue_style(
		'noyona-cart',
		get_stylesheet_directory_uri() . '/assets/css/noyona-cart.css',
		array( 'woocom-ct-style', 'woocom-ct-header' ),
		file_exists( $cart_css_path ) ? (string) filemtime( $cart_css_path ) : wp_get_theme()->get( 'Version' )
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
	// Phone placeholder — Philippine format
	if ( isset( $fields['billing']['billing_phone'] ) ) {
		$fields['billing']['billing_phone']['placeholder'] = '+63 ___ ___-____';
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
	);
	foreach ( $shipping_remove as $field_key ) {
		unset( $fields['shipping'][ $field_key ] );
	}

	// Make shipping_address_1 full width
	if ( isset( $fields['shipping']['shipping_address_1'] ) ) {
		$fields['shipping']['shipping_address_1']['label'] = __( 'Address', 'noyona' );
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

/*
 * Copy billing name → shipping name so WooCommerce doesn't
 * reject the order for missing shipping_first/last_name.
 */
add_action( 'woocommerce_checkout_create_order', 'noyona_copy_billing_name_to_shipping', 10, 2 );
function noyona_copy_billing_name_to_shipping( $order, $data ) {
	$order->set_shipping_first_name( $order->get_billing_first_name() );
	$order->set_shipping_last_name( $order->get_billing_last_name() );
	$order->set_shipping_country( $order->get_billing_country() ?: 'PH' );
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
 * Always ship to a separate address (we show shipping fields directly).
 */
add_filter( 'woocommerce_ship_to_different_address_checked', '__return_true' );

/**
 * Ensure checkout uses shipping-address mode in our custom flow.
 * This avoids server setting mismatches where shipping fields disappear.
 */
add_filter( 'woocommerce_ship_to_destination', 'noyona_force_ship_to_destination_mode', 20 );
function noyona_force_ship_to_destination_mode( $destination ) {
	if ( function_exists( 'noyona_is_checkout_ui_context' ) && noyona_is_checkout_ui_context() ) {
		return 'shipping';
	}

	return $destination;
}

/* ─── Save custom checkout fields ─────────────────── */

add_action( 'woocommerce_checkout_update_order_meta', 'noyona_save_custom_checkout_fields' );
function noyona_save_custom_checkout_fields( $order_id ) {
	if ( ! empty( $_POST['noyona_newsletter'] ) ) {
		update_post_meta( $order_id, '_noyona_newsletter_optin', 'yes' );
	}
	if ( ! empty( $_POST['noyona_gift_order'] ) ) {
		update_post_meta( $order_id, '_noyona_gift_order', 'yes' );
	}
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
		if (!form && !isOrderReceivedPath) return;

		/* 1. Scroll to validation errors */
		var observer = new MutationObserver(function() {
			var notice = document.querySelector('.woocommerce-NoticeGroup-checkout');
			if (notice) {
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

		var reviewUrl = <?php echo wp_json_encode( home_url( '/reviews/' ) ); ?>;
		var detailsUrl = <?php echo wp_json_encode( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : home_url( '/checkout/' ) ); ?>;
		var cartUrl = <?php echo wp_json_encode( function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/cart/' ) ); ?>;
		var allowDonePreviewBypass = <?php echo wp_json_encode( noyona_checkout_is_local_env() ); ?>;
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
			reviewPath = '/reviews';
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
					'<li><span class="noyona-checkout-steps__icon" aria-hidden="true"><i class="fa-solid fa-box"></i></span> Reviews</li>' +
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

		var isReviewStep = currentPath === reviewPath;
		var isAwaitingPayment = !!document.querySelector('[data-noyona-awaiting-payment="1"]');

		if (!isAwaitingPayment && isOrderReceivedPath) {
			var paymentOverviewNode = document.querySelector('.woocommerce-order-overview__payment-method');
			var paymentOverviewText = paymentOverviewNode ? String(paymentOverviewNode.textContent || '').toLowerCase() : '';
			var bodyText = String((document.body && document.body.textContent) || '').toLowerCase();
			var looksLikePayMongoQr = (
				(paymentOverviewText.indexOf('paymongo') !== -1 && paymentOverviewText.indexOf('qr') !== -1) ||
				(bodyText.indexOf('paymongo') !== -1 && bodyText.indexOf('qr') !== -1)
			);
			var hasQrWaitingCopy = (
				bodyText.indexOf('scan qr code to pay') !== -1 ||
				bodyText.indexOf('waiting for payment') !== -1 ||
				bodyText.indexOf('open your banking app') !== -1 ||
				bodyText.indexOf('scan the qr code below') !== -1
			);
			if (looksLikePayMongoQr && hasQrWaitingCopy) {
				isAwaitingPayment = true;
			}
		}

		var isDoneStep = !isAwaitingPayment && (
			body.classList.contains('woocommerce-order-received') ||
			window.location.pathname.indexOf('/order-received/') !== -1 ||
			(window.location.search.indexOf('noyona_preview_done=1') !== -1)
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
				name === 'noyona_newsletter' ||
				name === 'noyona_gift_order'
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

		function closePayConfirmModal() {
			if (!payConfirmModal) return;
			payConfirmModal.hidden = true;
			payConfirmContinueCallback = null;
			body.classList.remove('noyona-pay-confirm-open');
		}

		function openPayConfirmModal(onContinue) {
			if (!payConfirmModal) return false;
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
		}

		function wireReviewTerms(checkoutForm) {
			if (!isReviewStep || !checkoutForm) return;
			var customTerms = document.getElementById('noyona-review-terms');
			var nativeTerms = checkoutForm.querySelector('#terms');
			if (!customTerms || !nativeTerms) return;

			customTerms.checked = !!nativeTerms.checked;
			customTerms.addEventListener('change', function () {
				nativeTerms.checked = !!customTerms.checked;
				nativeTerms.dispatchEvent(new Event('change', { bubbles: true }));
			});

			nativeTerms.addEventListener('change', function () {
				customTerms.checked = !!nativeTerms.checked;
			});
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

			return document.querySelector('[class*="paymongo"]') || null;
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

		if (isAwaitingPayment) {
			body.classList.add('noyona-pay-step');
			body.classList.remove('noyona-done-step');
			body.classList.remove('noyona-review-step');
			body.classList.remove('noyona-details-step');
			ensureCheckoutStepper();
			ensurePendingQrFallbackShell();
			window.setTimeout(function() {
				window.location.reload();
			}, 10000);

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
				ensureStepLink(doneStepItems[0], cartUrl);
				ensureStepLink(doneStepItems[1], detailsUrl);
				ensureStepLink(doneStepItems[2], reviewUrl);
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
			body.classList.add('noyona-review-step');
			body.classList.remove('noyona-pay-step');
			body.classList.remove('noyona-details-step');
			body.classList.remove('noyona-done-step');
			ensureCheckoutStepper();
			restoreCheckoutDraft(form);
			syncReviewSnapshot(form);
			wireReviewTerms(form);
			var backBtn = document.querySelector('.noyona-checkout-actions__back');
			if (backBtn) {
				backBtn.setAttribute('href', detailsUrl);
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
		} else {
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

		/* 3. Review Order button → go to reviews step, then place order */
		var reviewBtn = document.getElementById('noyona-review-order');
		var placeOrder = document.getElementById('place_order');
		if (reviewBtn) {
			var qrPaymentConfirmed = false;
			if (isReviewStep) {
				reviewBtn.innerHTML = '<i class="fa-solid fa-lock" aria-hidden="true"></i> PLACE ORDER';
				reviewBtn.addEventListener('click', function() {
					var customTerms = document.getElementById('noyona-review-terms');
					if (customTerms && !customTerms.checked) {
						customTerms.focus();
						return;
					}
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
			} else {
				reviewBtn.addEventListener('click', function() {
					if (form && typeof form.reportValidity === 'function' && !form.reportValidity()) {
						return;
					}
					persistCheckoutDraft(form);
					window.location.assign(reviewUrl);
				});
			}
		}

		if (isReviewStep && form) {
			form.addEventListener('input', function () {
				syncReviewSnapshot(form);
			});
			form.addEventListener('change', function () {
				syncReviewSnapshot(form);
			});
			if (window.jQuery) {
				window.jQuery(document.body).on('updated_checkout', function() {
					syncReviewSnapshot(form);
				});
			}
		}

		/* 4. "Use current address" — browser geolocation stub */
		var locBtn = document.getElementById('noyona-use-location');
		if (locBtn) {
			locBtn.addEventListener('click', function() {
				if (!navigator.geolocation) return;
				locBtn.classList.add('is-loading');
				locBtn.textContent = 'Locating…';
				navigator.geolocation.getCurrentPosition(
					function(pos) {
						/* Reverse geocoding requires an API (Google / Mapbox / Nominatim).
						   For now, fill coordinates as proof-of-concept. Replace with
						   your preferred geocoding service. */
						locBtn.textContent = 'Location found';
						locBtn.classList.remove('is-loading');
						locBtn.classList.add('is-done');
						setTimeout(function() {
							locBtn.innerHTML = '<i class="fa-solid fa-location-dot"></i> Use current address';
							locBtn.classList.remove('is-done');
						}, 2000);
					},
					function() {
						locBtn.innerHTML = '<i class="fa-solid fa-location-dot"></i> Use current address';
						locBtn.classList.remove('is-loading');
					},
					{ timeout: 8000 }
				);
			});
		}
	})();
	</script>
	<?php
}
