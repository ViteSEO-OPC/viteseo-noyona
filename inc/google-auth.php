<?php
/**
 * Google-first authentication enhancements (authentication / account-ownership only).
 *
 * - Routes Nextend Social Login (Google) registrations through WooCommerce customer
 *   creation so new users get the `customer` role and WooCommerce scaffolding.
 * - Sends the themed WooCommerce "new account" welcome email exactly once and
 *   suppresses the duplicate WordPress core new-user notification.
 * - Flags Google-created users as needing a first-time password setup.
 * - Restricts Google auto-link to customer accounts (never administrator / shop_manager).
 *
 * All logic is isolated in this file. To roll back, delete this file and remove its
 * entry from the loader list in functions.php. No DB schema or WooCommerce core changes.
 *
 * @package Noyona\Auth
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** User meta flag: account created via Google that has no self-chosen password yet. */
if ( ! defined( 'NOYONA_PASSWORD_SETUP_META' ) ) {
	define( 'NOYONA_PASSWORD_SETUP_META', 'noyona_requires_password_setup' );
}

/**
 * Whether the given user still needs to create a first-time password.
 *
 * @param int $user_id User ID.
 * @return bool
 */
function noyona_user_requires_password_setup( $user_id ) {
	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return false;
	}

	return '1' === (string) get_user_meta( $user_id, NOYONA_PASSWORD_SETUP_META, true );
}

/**
 * Bridge: create the account as a WooCommerce customer during Google registration
 * instead of Nextend's plain wp_insert_user(). The nested user_register action this
 * triggers runs Nextend's registerComplete()/auto-login, which typically exits the
 * request; flag-setting and the welcome email are therefore handled in
 * noyona_nsl_after_google_register() (hooked on nsl_register_new_user), which fires
 * before that exit.
 *
 * @param array  $status    Nextend external-insert status array.
 * @param object $nsl_user  Nextend social user object.
 * @param array  $user_data Prepared user data (user_login, user_email, user_pass, ...).
 * @return array
 */
add_filter( 'nsl_register_external_insert_user', 'noyona_nsl_register_as_woocommerce_customer', 10, 3 );
function noyona_nsl_register_as_woocommerce_customer( $status, $nsl_user, $user_data ) {
	if ( ! function_exists( 'wc_create_new_customer' ) ) {
		return $status; // WooCommerce unavailable: let Nextend insert normally.
	}

	$email    = isset( $user_data['user_email'] ) ? (string) $user_data['user_email'] : '';
	$username = isset( $user_data['user_login'] ) ? (string) $user_data['user_login'] : '';
	$password = isset( $user_data['user_pass'] ) ? (string) $user_data['user_pass'] : '';

	if ( '' === $email ) {
		return $status; // No email from provider: defer to Nextend's own handling.
	}

	// Forward any Google-provided name into the WooCommerce customer record so the
	// profile "Full Name" and welcome email greeting can use it. Absent keys are simply
	// omitted, preserving prior behavior when Google does not share a name.
	$customer_args = array( 'role' => 'customer' );
	if ( isset( $user_data['first_name'] ) && '' !== (string) $user_data['first_name'] ) {
		$customer_args['first_name'] = (string) $user_data['first_name'];
	}
	if ( isset( $user_data['last_name'] ) && '' !== (string) $user_data['last_name'] ) {
		$customer_args['last_name'] = (string) $user_data['last_name'];
	}
	if ( isset( $user_data['display_name'] ) && '' !== (string) $user_data['display_name'] ) {
		$customer_args['display_name'] = (string) $user_data['display_name'];
	}

	$user_id = wc_create_new_customer( $email, $username, $password, $customer_args );

	if ( is_wp_error( $user_id ) ) {
		return array(
			'isExternalInsertUser' => true,
			'error'                => $user_id,
		);
	}

	// Returned defensively. Nextend's auto-login normally exits the request inside the
	// nested user_register action above, so this prevents a duplicate insert only in the
	// rare path where auto-login does not occur.
	return array(
		'isExternalInsertUser' => true,
		'error'                => false,
	);
}

/**
 * After a Google account is registered (fires inside Nextend registerComplete(), before
 * the auto-login redirect/exit): suppress the duplicate WP core notification, flag the
 * user for first-time password setup, ensure the customer role, and send the themed
 * WooCommerce welcome email exactly once.
 *
 * @param int    $user_id  New user ID.
 * @param object $provider Nextend provider instance.
 * @return void
 */
add_action( 'nsl_register_new_user', 'noyona_nsl_after_google_register', 10, 2 );
function noyona_nsl_after_google_register( $user_id, $provider ) {
	if ( is_object( $provider ) && method_exists( $provider, 'getId' ) && 'google' !== $provider->getId() ) {
		return;
	}

	$user_id = (int) $user_id;
	if ( $user_id <= 0 ) {
		return;
	}

	// Prevent the duplicate WordPress core "new user" email. The themed WooCommerce
	// customer_new_account email triggered below is the single source of truth.
	remove_action( 'register_new_user', 'wp_send_new_user_notifications' );

	// Mark the account as needing a first-time password.
	update_user_meta( $user_id, NOYONA_PASSWORD_SETUP_META, '1' );

	// Safety: guarantee the customer role even if customer creation conventions change.
	$user = get_userdata( $user_id );
	if ( $user && ! in_array( 'customer', (array) $user->roles, true ) && get_role( 'customer' ) ) {
		$user->add_role( 'customer' );
	}

	// Send the themed WooCommerce welcome email once. password_generated = false so the
	// "set password" block is omitted (the user uses the My Account Set Password flow).
	if ( function_exists( 'WC' ) ) {
		$mailer = WC()->mailer();
		if ( $mailer ) {
			$emails = $mailer->get_emails();
			if ( ! empty( $emails['WC_Email_Customer_New_Account'] ) && is_object( $emails['WC_Email_Customer_New_Account'] ) ) {
				$emails['WC_Email_Customer_New_Account']->trigger( $user_id, '', false );
			}
		}
	}

	// Hardening: the manual trigger above is the single source of truth. Normally Nextend's
	// auto-login exit prevents WooCommerce's automatic customer_new_account email from firing,
	// but on rare non-exit paths (e.g. login restrictions) wc_create_new_customer() could still
	// reach woocommerce_created_customer. Disabling the email now — AFTER the manual send (which
	// already passed is_enabled()) — guarantees no duplicate for the remainder of this request.
	add_filter( 'woocommerce_email_enabled_customer_new_account', '__return_false', 99 );
}

/**
 * Security: never auto-link a Google login to a privileged account. Customers auto-link
 * by verified email; administrators / shop_managers must link manually after logging in.
 *
 * @param bool   $allowed  Whether auto-link is allowed.
 * @param object $provider Nextend provider instance.
 * @param int    $user_id  Existing user ID matched by email.
 * @return bool
 */
add_filter( 'nsl_google_auto_link_allowed', 'noyona_nsl_block_privileged_autolink', 10, 3 );
function noyona_nsl_block_privileged_autolink( $allowed, $provider, $user_id ) {
	$user = get_userdata( (int) $user_id );
	if ( $user && array_intersect( array( 'administrator', 'shop_manager' ), (array) $user->roles ) ) {
		return false;
	}

	return $allowed;
}

/**
 * First-time "Set Password" handler for Google-created users.
 *
 * This is distinct from the existing Change Password flow: it does NOT require a
 * current password, because a Google-created user has only a random one they do not
 * know. It is strictly gated to users still flagged as requiring password setup, so it
 * can never overwrite an existing self-chosen password. Once set, the flag is cleared
 * and the user falls back to the standard Change Password flow.
 *
 * @return void
 */
add_action( 'admin_post_noyona_set_account_password', 'noyona_set_account_password_handler' );
function noyona_set_account_password_handler() {
	$account_url = function_exists( 'noyona_get_account_page_url' ) ? noyona_get_account_page_url() : home_url( '/my-account/' );

	if ( ! is_user_logged_in() ) {
		$login_url = function_exists( 'noyona_get_login_page_url' ) ? noyona_get_login_page_url() : wp_login_url();
		wp_safe_redirect( $login_url );
		exit;
	}

	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	if ( '' === $redirect_to ) {
		$redirect_to = $account_url;
	}

	$nonce = isset( $_POST['noyona_account_set_password_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['noyona_account_set_password_nonce'] ) ) : '';
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_set_account_password' ) ) {
		wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'set_password', 'noyona_account_notice' => 'invalid_nonce' ), $redirect_to ) );
		exit;
	}

	$user_id = get_current_user_id();

	// Eligibility gate: only flagged (password-less) users may set a password without the current one.
	if ( ! noyona_user_requires_password_setup( $user_id ) ) {
		wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'password' ), $redirect_to ) );
		exit;
	}

	$new_password     = isset( $_POST['new_password'] ) ? (string) wp_unslash( $_POST['new_password'] ) : '';
	$confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

	if ( '' === $new_password || '' === $confirm_password ) {
		wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'set_password', 'noyona_account_notice' => 'missing_fields' ), $redirect_to ) );
		exit;
	}

	if ( strlen( $new_password ) < 6 ) {
		wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'set_password', 'noyona_account_notice' => 'password_too_short' ), $redirect_to ) );
		exit;
	}

	if ( $new_password !== $confirm_password ) {
		wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'set_password', 'noyona_account_notice' => 'password_mismatch' ), $redirect_to ) );
		exit;
	}

	$updated_user = wp_update_user(
		array(
			'ID'        => (int) $user_id,
			'user_pass' => $new_password,
		)
	);

	if ( is_wp_error( $updated_user ) ) {
		wp_safe_redirect( add_query_arg( array( 'noyona_modal' => 'set_password', 'noyona_account_notice' => 'password_set_failed' ), $redirect_to ) );
		exit;
	}

	// Clear the flag so the UI reverts to the standard Change Password flow.
	delete_user_meta( $user_id, NOYONA_PASSWORD_SETUP_META );

	// Keep the user logged in after the password change.
	wp_set_current_user( (int) $user_id );
	wp_set_auth_cookie( (int) $user_id, true );

	wp_safe_redirect( add_query_arg( array( 'noyona_account_notice' => 'password_set' ), $redirect_to ) );
	exit;
}
