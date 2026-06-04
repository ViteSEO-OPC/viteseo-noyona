<?php
/**
 * Theme setup: WooCommerce support, template-part safeguards, block registration, login UX hooks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Shop archive title rewrite ('All Products') ----- */
add_filter( 'get_the_archive_title', 'noyona_replace_shop_archive_title' );
function noyona_replace_shop_archive_title( $title ) {
	if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
		return $title;
	}

	$normalized_title = trim( wp_strip_all_tags( (string) $title ) );
	if ( 'Shop' === $normalized_title ) {
		return 'All Products';
	}

	return $title;
}

/* ----- Declare WooCommerce + product gallery support ----- */


// Make sure theme declares WooCommerce support, including the three flags
// that opt the classic single-product gallery into FlexSlider / PhotoSwipe /
// zoom. Without these, WC may skip enqueuing wc-single-product on a block
// theme, leaving the gallery markup in the page but never initialised — which
// is what was happening on production (images stacked vertically because
// FlexSlider never wrapped them in .flex-viewport).
add_action( 'after_setup_theme', 'noyona_add_woocommerce_theme_support' );
function noyona_add_woocommerce_theme_support() {
    add_theme_support( 'woocommerce' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
}

/* ----- One-time stale header template-part reset ----- */
/**
 * One-time safeguard:
 * Remove stale DB-saved block-theme header template parts so the theme file
 * parts/header.html is used consistently (prevents production/local mismatch).
 */
add_action( 'init', 'noyona_one_time_reset_header_template_part_override', 30 );
function noyona_one_time_reset_header_template_part_override() {
    $safeguard_version = '2';
    $option_key        = 'noyona_header_template_part_safeguard_version';
    if ( $safeguard_version === get_option( $option_key, '' ) ) {
        return;
    }

    if ( ! post_type_exists( 'wp_template_part' ) ) {
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
        'post_type'      => 'wp_template_part',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'name'           => 'header',
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

    $header_template_part_ids = get_posts( $query_args );
    if ( ! empty( $header_template_part_ids ) ) {
        foreach ( $header_template_part_ids as $template_part_id ) {
            wp_delete_post( (int) $template_part_id, true );
        }
    }

    update_option( $option_key, $safeguard_version, false );
}

/* ----- Force Woo login redirect to /my-account/ ----- */
add_filter( 'woocommerce_login_redirect', 'noyona_force_woo_login_redirect_to_account', 20, 2 );
function noyona_force_woo_login_redirect_to_account( $redirect, $user ) {
    if ( is_wp_error( $user ) ) {
        return $redirect;
    }

    if ( $user instanceof WP_User && user_can( $user, 'manage_options' ) ) {
        return admin_url();
    }

    return noyona_get_account_page_url();
}

/* ----- Virtual /login/ page renderer ----- */
add_action( 'template_redirect', 'noyona_render_virtual_login_page', 3 );
function noyona_render_virtual_login_page() {
    if ( is_admin() || is_user_logged_in() || is_page( 'login' ) ) {
        return;
    }

    // Only virtual-render the dedicated /login/ route; never hijack /my-account/ endpoints.
    $request_path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
    $home_path    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    $relative     = trim( preg_replace( '#^' . preg_quote( $home_path, '#' ) . '#', '', $request_path ), '/' );

    if ( 'login' !== strtolower( (string) $relative ) ) {
        return;
    }

    // Render login page markup even when a dedicated WP page with slug "login" does not exist.
    status_header( 200 );
    nocache_headers();

    echo do_blocks(
        '<!-- wp:template-part {"slug":"header"} /-->' .
        '<!-- wp:group {"tagName":"main","style":{"spacing":{"margin":{"top":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->' .
        '<main class="wp-block-group" style="margin-top:var(--wp--preset--spacing--60)">' .
        '<!-- wp:group {"style":{"spacing":{"padding":{"bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->' .
        '<div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--60)">' .
        '<!-- wp:shortcode -->[woocommerce_my_account]<!-- /wp:shortcode -->' .
        '</div>' .
        '<!-- /wp:group -->' .
        '</main>' .
        '<!-- /wp:group -->' .
        '<!-- wp:template-part {"slug":"footer"} /-->'
    );
    exit;
}

/* ----- Body class for login context ----- */
add_filter( 'body_class', 'noyona_add_login_page_account_body_class' );
function noyona_add_login_page_account_body_class( $classes ) {
    if ( noyona_is_login_page_context() && ! in_array( 'woocommerce-account', $classes, true ) ) {
        $classes[] = 'woocommerce-account';
    }

    return $classes;
}

/* ----- Render login content via shortcode ----- */
add_filter( 'the_content', 'noyona_render_login_page_content', 25 );
function noyona_render_login_page_content( $content ) {
    if ( is_admin() || ! is_page( 'login' ) || ! in_the_loop() || ! is_main_query() ) {
        return $content;
    }

    return do_shortcode( '[woocommerce_my_account]' );
}

/* ----- Login form intro (brand + headings) ----- */
add_action( 'woocommerce_login_form_start', 'noyona_account_login_form_intro' );
function noyona_account_login_form_intro() {
    if ( is_user_logged_in() || ! noyona_is_login_page_context() ) {
        return;
    }

    $logo_url = trailingslashit( get_stylesheet_directory_uri() ) . 'assets/images/noyona-mobile-logo.webp';
    $logo_markup = '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr__( 'Noyona logo', 'noyona-childtheme' ) . '" loading="lazy" decoding="async" />';

    echo '<a class="noyona-account-login-back" href="' . esc_url( home_url( '/' ) ) . '">';
    echo '<i class="fa-solid fa-arrow-left" aria-hidden="true"></i>';
    echo '<span>' . esc_html__( 'Back', 'noyona-childtheme' ) . '</span>';
    echo '</a>';

    echo '<div class="noyona-account-login-brand-wrap">';
    echo '<span class="noyona-account-login-brand">' . wp_kses_post( $logo_markup ) . '</span>';
    echo '</div>';
    echo '<h3 class="noyona-account-login-title">' . esc_html__( 'Login', 'noyona-childtheme' ) . '</h3>';
    echo '<p class="noyona-account-login-intro">' . esc_html__( 'Enter your Email Address and Password to access your account', 'noyona-childtheme' ) . '</p>';
}

/* ----- Register link + Google CTA at login form end ----- */
add_action( 'woocommerce_login_form_end', 'woocom_ct_add_register_link_to_login' );
function woocom_ct_add_register_link_to_login() {
    if ( ! noyona_is_login_page_context() ) {
        return;
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    $google_login_url = noyona_get_google_login_url( $account_url );
    $register_url = home_url( '/register/' );

    echo '<div class="noyona-login-form-footer">';
    echo '<a class="noyona-login-google-btn" href="' . esc_url( $google_login_url ) . '"><i class="fa-brands fa-google" aria-hidden="true"></i><span>' . esc_html__( 'Log In with Google', 'noyona-childtheme' ) . '</span></a>';

    if ( function_exists( 'noyona_recaptcha_form_enabled' ) && noyona_recaptcha_form_enabled( 'login' ) ) {
        $captcha_markup = function_exists( 'noyona_recaptcha_form_widget_html' )
            ? noyona_recaptcha_form_widget_html(
                'login',
                'noyona-login-recaptcha',
                array(
                    'data-callback' => 'noyonaLoginRecaptchaVerified',
                )
            )
            : '';

        if ( '' !== trim( (string) $captcha_markup ) ) {
            echo '<div class="noyona-login-recaptcha-wrap">';
            echo wp_kses( $captcha_markup, noyona_recaptcha_form_allowed_html() );
            echo '</div>';
            ?>
            <script>
            (function() {
              var AUTO_HIDE_DELAY_MS = 10000;

              function findCaptchaErrorNoticeContext() {
                var wrappers = document.querySelectorAll('.woocommerce-notices-wrapper');

                for (var w = 0; w < wrappers.length; w++) {
                  var wrapper = wrappers[w];
                  var notices = wrapper.querySelectorAll('.woocommerce-error, .woocommerce-message, .woocommerce-info, li');

                  for (var i = 0; i < notices.length; i++) {
                    var text = String(notices[i].textContent || '').toLowerCase();
                    if (text.indexOf('captcha verification failed') !== -1) {
                      return { wrapper: wrapper, notice: notices[i] };
                    }
                  }

                  var wrapperText = String(wrapper.textContent || '').toLowerCase();
                  if (wrapperText.indexOf('captcha verification failed') !== -1) {
                    return { wrapper: wrapper, notice: null };
                  }
                }

                return null;
              }

              function hideCaptchaErrorNotice() {
                var context = findCaptchaErrorNoticeContext();
                if (!context || !context.wrapper) return;

                if (context.notice) {
                  context.notice.style.display = 'none';
                }

                context.wrapper.style.setProperty('display', 'none', 'important');
                context.wrapper.classList.add('noyona-captcha-notice-hidden');
                context.wrapper.setAttribute('aria-hidden', 'true');
              }

              window.noyonaLoginRecaptchaVerified = function() {
                var wrappers = document.querySelectorAll('.noyona-login-recaptcha-wrap');
                wrappers.forEach(function(wrapper) {
                  wrapper.classList.add('is-verified');
                });
                hideCaptchaErrorNotice();
              };

              var tokenInput = document.querySelector('form.woocommerce-form-login input[name="g-recaptcha-response"]');
              if (tokenInput) {
                tokenInput.addEventListener('change', function() {
                  var hasToken = String(tokenInput.value || '').trim().length > 0;
                  if (hasToken) {
                    hideCaptchaErrorNotice();
                  }
                });

                var hasInitialToken = String(tokenInput.value || '').trim().length > 0;
                if (hasInitialToken) {
                  hideCaptchaErrorNotice();
                }
              }

              var captchaNoticeContext = findCaptchaErrorNoticeContext();
              if (captchaNoticeContext) {
                setTimeout(function() {
                  hideCaptchaErrorNotice();
                }, AUTO_HIDE_DELAY_MS);
              }
            })();
            </script>
            <?php
        }
    }

    echo '<p class="noyona-login-register-link">' . esc_html__( "Don't have an account?", 'noyona-childtheme' ) . ' <a href="' . esc_url( $register_url ) . '">' . esc_html__( 'Create an Account', 'noyona-childtheme' ) . '</a></p>';
    echo '</div>';
}

add_filter( 'woocommerce_process_login_errors', 'noyona_validate_login_recaptcha', 10, 3 );
function noyona_validate_login_recaptcha( $validation_error, $username, $password ) {
    if ( is_user_logged_in() || ! noyona_is_login_page_context() ) {
        return $validation_error;
    }

    if ( ! function_exists( 'noyona_recaptcha_form_verify_post' ) ) {
        return $validation_error;
    }

    $captcha_result = noyona_recaptcha_form_verify_post( 'login' );
    if ( is_wp_error( $captcha_result ) ) {
        $validation_error->add( 'recaptcha_failed', __( 'Captcha verification failed. Please try again.', 'noyona-childtheme' ) );
    }

    return $validation_error;
}

/* ----- Replace Woo login form labels ----- */
add_filter( 'gettext', 'noyona_account_replace_lost_password_text', 20, 3 );
function noyona_account_replace_lost_password_text( $translated_text, $text, $domain ) {
    if ( is_admin() ) {
        return $translated_text;
    }

    if ( ! noyona_is_login_page_context() || is_user_logged_in() ) {
        return $translated_text;
    }

    if ( 'Lost your password?' === $text && 'woocommerce' === $domain ) {
        return __( 'Forgot password?', 'noyona-childtheme' );
    }

    if ( 'Username or email address' === $text && 'woocommerce' === $domain ) {
        return __( 'Email Address', 'noyona-childtheme' );
    }

    return $translated_text;
}

/* ----- Lost-password validation (email format + registered account) ----- */
add_filter( 'lostpassword_post', 'noyona_validate_lost_password_email', 10, 2 );
function noyona_validate_lost_password_email( $errors, $user_data ) {
    if ( ! ( $errors instanceof WP_Error ) ) {
        return $errors;
    }

    $user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
    $email      = sanitize_email( $user_login );

    if ( '' === $email || ! is_email( $email ) ) {
        $errors->add(
            'invalid_email',
            __( 'Please enter a valid email address.', 'noyona-childtheme' )
        );
        return $errors;
    }

    if ( ! email_exists( $email ) ) {
        $errors->add(
            'invalidcombo',
            __( 'No account is registered with that email address.', 'noyona-childtheme' )
        );
    }

    return $errors;
}

/* ----- Hide header/footer template parts on the lost-password / reset-password endpoint only ----- */
add_filter( 'render_block_core/template-part', 'noyona_hide_header_footer_on_lost_password', 10, 2 );
function noyona_hide_header_footer_on_lost_password( $block_content, $parsed_block ) {
	if ( is_admin() ) {
		return $block_content;
	}
	if ( ! function_exists( 'noyona_is_account_recovery_context' ) || ! noyona_is_account_recovery_context() ) {
		return $block_content;
	}
	$slug = isset( $parsed_block['attrs']['slug'] ) ? (string) $parsed_block['attrs']['slug'] : '';
	if ( 'header' === $slug || 'footer' === $slug ) {
		return '';
	}
	return $block_content;
}

/* ----- Normalize login form controls (footer script) ----- */
add_action( 'wp_footer', 'noyona_normalize_login_form_controls', 90 );
function noyona_normalize_login_form_controls() {
    if ( is_admin() || is_user_logged_in() || ! noyona_is_login_page_context() ) {
        return;
    }
    ?>
    <script>
    (function () {
      function normalizeLoginForm() {
        var form = document.querySelector('form.woocommerce-form-login.login');
        if (!form) return;

        var username = form.querySelector('#username');
        if (username && !username.getAttribute('placeholder')) {
          username.setAttribute('placeholder', 'Enter your email');
        }

        var password = form.querySelector('#password');
        if (password && !password.getAttribute('placeholder')) {
          password.setAttribute('placeholder', 'Enter your password');
        }

        if (!password) return;

        var usernameRow = username ? username.closest('.woocommerce-form-row') : null;
        var passwordRow = password.closest('.woocommerce-form-row');

        if (usernameRow) {
          usernameRow.classList.add('noyona-login-row--username');
        }
        if (passwordRow) {
          passwordRow.classList.add('noyona-login-row--password');
        }

        var wrapper = password.closest('.password-input');
        if (!wrapper) {
          wrapper = document.createElement('span');
          wrapper.className = 'password-input';
          password.parentNode.insertBefore(wrapper, password);
          wrapper.appendChild(password);
        }

        // Hide/remove third-party injected icon roots and Woo's default toggle in username row.
        if (usernameRow) {
          usernameRow.querySelectorAll('.show-password-input, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (el) {
            el.remove();
          });
        }

        // Remove Woo default toggle and extension icon roots in password row, then use our own stable toggle.
        if (passwordRow) {
          passwordRow.querySelectorAll('.show-password-input, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (el) {
            el.remove();
          });
        }

        // Purge any stale toggles outside the active wrapper (caused by Woo's re-wrap of #password).
        if (passwordRow) {
          passwordRow.querySelectorAll('.noyona-password-toggle').forEach(function (el) {
            if (!wrapper.contains(el)) {
              el.remove();
            }
          });
        }

        // De-duplicate any extra toggles inside the active wrapper, keep the first.
        var wrapperToggles = wrapper.querySelectorAll('.noyona-password-toggle');
        for (var i = 1; i < wrapperToggles.length; i++) {
          wrapperToggles[i].remove();
        }

        var customToggle = wrapper.querySelector('.noyona-password-toggle');
        if (!customToggle) {
          customToggle = document.createElement('button');
          customToggle.type = 'button';
          customToggle.className = 'noyona-password-toggle';
          customToggle.setAttribute('aria-label', 'Show password');
          customToggle.innerHTML = '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
          wrapper.appendChild(customToggle);

          customToggle.addEventListener('click', function () {
            var isHidden = password.getAttribute('type') === 'password';
            password.setAttribute('type', isHidden ? 'text' : 'password');
            customToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            customToggle.innerHTML = isHidden
              ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
              : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
          });
        }
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', normalizeLoginForm);
      } else {
        normalizeLoginForm();
      }
      setTimeout(normalizeLoginForm, 250);
      setTimeout(normalizeLoginForm, 850);

      if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
          normalizeLoginForm();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
      }
    })();
    </script>
    <?php
}

/* ----- Normalize register form password toggles (footer script) ----- */
add_action( 'wp_footer', 'noyona_normalize_register_form_controls', 90 );
function noyona_normalize_register_form_controls() {
    if ( is_admin() || is_user_logged_in() || ! is_page( 'register' ) ) {
        return;
    }
    ?>
    <script>
    (function () {
      function normalizeRegisterPasswords() {
        var form = document.querySelector('.noyona-register-form');
        if (!form) return;

        var fields = [
          form.querySelector('#noyona-register-password'),
          form.querySelector('#noyona-register-confirm-password')
        ].filter(Boolean);

        fields.forEach(function (password) {
          var fieldRow = password.closest('.noyona-register-field');

          var wrapper = password.closest('.password-input');
          if (!wrapper) {
            wrapper = document.createElement('span');
            wrapper.className = 'password-input';
            password.parentNode.insertBefore(wrapper, password);
            wrapper.appendChild(password);
          }

          // Remove Woo's auto-injected toggle and password-manager icon roots in this field.
          if (fieldRow) {
            fieldRow.querySelectorAll('.show-password-input, [data-lastpass-icon-root], [data-lastpass-root]').forEach(function (el) {
              el.remove();
            });
          }

          // Purge any stale toggles outside the active wrapper.
          if (fieldRow) {
            fieldRow.querySelectorAll('.noyona-password-toggle').forEach(function (el) {
              if (!wrapper.contains(el)) {
                el.remove();
              }
            });
          }

          // De-duplicate any extra toggles inside the active wrapper, keep the first.
          var wrapperToggles = wrapper.querySelectorAll('.noyona-password-toggle');
          for (var i = 1; i < wrapperToggles.length; i++) {
            wrapperToggles[i].remove();
          }

          var customToggle = wrapper.querySelector('.noyona-password-toggle');
          if (!customToggle) {
            customToggle = document.createElement('button');
            customToggle.type = 'button';
            customToggle.className = 'noyona-password-toggle';
            customToggle.setAttribute('aria-label', 'Show password');
            customToggle.innerHTML = '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
            wrapper.appendChild(customToggle);

            customToggle.addEventListener('click', function () {
              var isHidden = password.getAttribute('type') === 'password';
              password.setAttribute('type', isHidden ? 'text' : 'password');
              customToggle.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
              customToggle.innerHTML = isHidden
                ? '<i class="fa-regular fa-eye" aria-hidden="true"></i>'
                : '<i class="fa-regular fa-eye-slash" aria-hidden="true"></i>';
            });
          }
        });
      }

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', normalizeRegisterPasswords);
      } else {
        normalizeRegisterPasswords();
      }
      setTimeout(normalizeRegisterPasswords, 250);
      setTimeout(normalizeRegisterPasswords, 850);

      if (typeof MutationObserver !== 'undefined') {
        var observer = new MutationObserver(function () {
          normalizeRegisterPasswords();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
      }
    })();
    </script>
    <?php
}

/* ----- Register custom blocks ----- */
// Register custom blocks
add_action( 'init', 'woocom_ct_register_blocks' );
function woocom_ct_register_blocks() {
    register_block_type( get_stylesheet_directory() . '/blocks/search-expand' );
    register_block_type( get_stylesheet_directory() . '/blocks/hero-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/guides-pillars' );
    register_block_type( get_stylesheet_directory() . '/blocks/different-cards' );
    register_block_type( get_stylesheet_directory() . '/blocks/brand-carousel' );
    register_block_type( get_stylesheet_directory() . '/blocks/color-swatches' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-highlight' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-slide' );
    register_block_type( get_stylesheet_directory() . '/blocks/collection-grid' );
    register_block_type( get_stylesheet_directory() . '/blocks/phone-video-reviews' );
    register_block_type( get_stylesheet_directory() . '/blocks/discover-posts-carousel' );
    register_block_type( get_stylesheet_directory() . '/blocks/founder-section' );
    register_block_type( get_stylesheet_directory() . '/blocks/landing-feature-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/types-cards' );
    register_block_type( get_stylesheet_directory() . '/blocks/benefits-strip' );
    register_block_type( get_stylesheet_directory() . '/blocks/values-strip' );
    register_block_type( get_stylesheet_directory() . '/blocks/store-locator-section' );
    register_block_type( get_stylesheet_directory() . '/blocks/newsletter-strip' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq-list' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq-blogpost' );
    register_block_type( get_stylesheet_directory() . '/blocks/cta-blogpost' );
    register_block_type( get_stylesheet_directory() . '/blocks/contact' );
    register_block_type( get_stylesheet_directory() . '/blocks/location' );
    register_block_type( get_stylesheet_directory() . '/blocks/not-found' );
    register_block_type( get_stylesheet_directory() . '/blocks/contact-form' );
    register_block_type( get_stylesheet_directory() . '/blocks/blog-list' );
    register_block_type( get_stylesheet_directory() . '/blocks/blog-slide' );
    register_block_type( get_stylesheet_directory() . '/blocks/blogs-view' );
    register_block_type( get_stylesheet_directory() . '/blocks/term' );
    register_block_type( get_stylesheet_directory() . '/blocks/terms' );
    register_block_type( get_stylesheet_directory() . '/blocks/thank-you' );
    register_block_type( get_stylesheet_directory() . '/blocks/coming-soon' );
    register_block_type( get_stylesheet_directory() . '/blocks/find-us' );
    register_block_type( get_stylesheet_directory() . '/blocks/customer-reviews' );
    register_block_type( get_stylesheet_directory() . '/blocks/discover-face-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-breadcrumbs' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-social-proof' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-essentials' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-stock-shipping' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-trust-badges' );
    register_block_type( get_stylesheet_directory() . '/blocks/pdp-related-products' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-tabs' );
}

/* ----- Normalize header logo markup (force theme assets) ----- */
add_filter( 'render_block', 'noyona_normalize_header_rendered_markup', 20, 2 );
function noyona_normalize_header_rendered_markup( $block_content, $block ) {
    if ( is_admin() || '' === trim( (string) $block_content ) ) {
        return $block_content;
    }

    if (
        false === strpos( $block_content, 'site-logo-img--desktop' ) &&
        false === strpos( $block_content, 'site-logo-img--mobile' )
    ) {
        return $block_content;
    }

    $theme_uri     = untrailingslashit( get_stylesheet_directory_uri() );
    $desktop_logo  = esc_url( $theme_uri . '/assets/images/noyona-logo.webp' );
    $mobile_logo   = esc_url( $theme_uri . '/assets/images/noyona-mobile-logo.webp' );

    $block_content = preg_replace_callback(
        '#<img\b[^>]*>#i',
        function ( $matches ) use ( $desktop_logo, $mobile_logo ) {
            $img_html = $matches[0];

            if ( false === stripos( $img_html, 'site-logo-img--desktop' ) && false === stripos( $img_html, 'site-logo-img--mobile' ) ) {
                return $img_html;
            }

            $target_src = false !== stripos( $img_html, 'site-logo-img--desktop' ) ? $desktop_logo : $mobile_logo;

            // Remove any srcset that could reselect old media-library images.
            $img_html = preg_replace( '#\s+srcset=(["\']).*?\1#i', '', $img_html );

            // Replace existing src, or inject one if missing.
            $src_replaced = 0;
            $img_html     = preg_replace( '#\s+src=(["\']).*?\1#i', ' src="' . $target_src . '"', $img_html, 1, $src_replaced );
            if ( 0 === (int) $src_replaced ) {
                $img_html = preg_replace( '#<img\b#i', '<img src="' . $target_src . '"', $img_html, 1 );
            }

            return $img_html;
        },
        $block_content
    );

    return $block_content;
}

/* ----- Disable admin bar on the frontend ----- */
/* =================================================
 * UX / MISC
 * ================================================= */
add_filter('show_admin_bar', '__return_false');

/* ----- Location review submit: preserve admin-entered author ----- */
add_filter( 'preprocess_comment', 'noyona_location_review_use_submitted_author', 20 );
function noyona_location_review_use_submitted_author( $commentdata ) {
    $is_location_store_review = isset( $_POST['nsl_v2_store_review'] ) && '1' === (string) wp_unslash( $_POST['nsl_v2_store_review'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! $is_location_store_review ) {
        return $commentdata;
    }
    $nonce = isset( $_POST['noyona_location_review_nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['noyona_location_review_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_location_review_submit' ) ) {
        return $commentdata;
    }

    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    if ( $post_id <= 0 || 'store' !== get_post_type( $post_id ) ) {
        return $commentdata;
    }

    $posted_author = isset( $_POST['author'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['author'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' !== $posted_author ) {
        $commentdata['comment_author'] = $posted_author;
    }

    return $commentdata;
}

add_action( 'comment_post', 'noyona_location_review_force_submitted_author', 30, 3 );
function noyona_location_review_force_submitted_author( $comment_id, $comment_approved, $commentdata ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
    $is_location_store_review = isset( $_POST['nsl_v2_store_review'] ) && '1' === (string) wp_unslash( $_POST['nsl_v2_store_review'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! $is_location_store_review ) {
        return;
    }
    $nonce = isset( $_POST['noyona_location_review_nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['noyona_location_review_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_location_review_submit' ) ) {
        return;
    }

    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    if ( $post_id <= 0 || 'store' !== get_post_type( $post_id ) ) {
        return;
    }

    $posted_author = isset( $_POST['author'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['author'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $posted_author ) {
        return;
    }

    wp_update_comment(
        array(
            'comment_ID'     => (int) $comment_id,
            'comment_author' => $posted_author,
        )
    );
}

/* ----- Location review submit: allow optional email/comment (scoped) ----- */
add_filter( 'pre_option_require_name_email', 'noyona_location_review_optional_email' );
function noyona_location_review_optional_email( $pre_option ) {
    $is_location_store_review = isset( $_POST['nsl_v2_store_review'] ) && '1' === (string) wp_unslash( $_POST['nsl_v2_store_review'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! $is_location_store_review ) {
        return $pre_option;
    }
    $nonce = isset( $_POST['noyona_location_review_nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['noyona_location_review_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_location_review_submit' ) ) {
        return $pre_option;
    }

    $post_id = isset( $_POST['comment_post_ID'] ) ? (int) wp_unslash( $_POST['comment_post_ID'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( $post_id <= 0 || 'store' !== get_post_type( $post_id ) ) {
        return $pre_option;
    }

    return 0;
}

add_filter( 'allow_empty_comment', 'noyona_location_review_allow_empty_comment', 10, 2 );
function noyona_location_review_allow_empty_comment( $allow_empty_comment, $commentdata ) {
    $is_location_store_review = isset( $_POST['nsl_v2_store_review'] ) && '1' === (string) wp_unslash( $_POST['nsl_v2_store_review'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( ! $is_location_store_review ) {
        return $allow_empty_comment;
    }
    $nonce = isset( $_POST['noyona_location_review_nonce'] ) ? sanitize_text_field( (string) wp_unslash( $_POST['noyona_location_review_nonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'noyona_location_review_submit' ) ) {
        return $allow_empty_comment;
    }

    $post_id = isset( $commentdata['comment_post_ID'] ) ? (int) $commentdata['comment_post_ID'] : 0;
    if ( $post_id <= 0 || 'store' !== get_post_type( $post_id ) ) {
        return $allow_empty_comment;
    }

    return true;
}

