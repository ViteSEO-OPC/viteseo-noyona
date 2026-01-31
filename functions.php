<?php
// Load parent + child styles and our custom assets
add_action( 'wp_enqueue_scripts', 'woocom_ct_enqueue_assets' );
function woocom_ct_enqueue_assets() {
    // Local font faces (self-hosted Proxima Nova).
    wp_enqueue_style(
        'noyona-fonts-local',
        get_stylesheet_directory_uri() . '/assets/css/fonts.css',
        array(),
        wp_get_theme()->get( 'Version' )
    );

    // Web fonts (free Google fonts)
    wp_enqueue_style(
        'noyona-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Noto+Serif+SemiCondensed:ital,wght@0,400;0,600;0,700;1,400;1,600&display=swap',
        array(),
        null
    );

    // Parent theme CSS
    wp_enqueue_style(
        'twentytwentyfive-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    // Child theme CSS
    wp_enqueue_style(
        'woocom-ct-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'twentytwentyfive-parent-style' ),
        wp_get_theme()->get( 'Version' )
    );

    // Header CSS (assets/css/header.css)
    wp_enqueue_style(
        'woocom-ct-header',
        get_stylesheet_directory_uri() . '/assets/css/header.css',
        array( 'woocom-ct-style' ),
        wp_get_theme()->get( 'Version' )
    );

    // Product-gatherer CSS
    wp_enqueue_style(
        'woocom-ct-product-gatherer',
        get_stylesheet_directory_uri() . '/assets/css/product-gatherer.css',
        array( 'woocom-ct-style', 'woocom-ct-header' ),
        wp_get_theme()->get( 'Version' )
    );

    // Font Awesome (for icons, hearts, cart, etc.)
    wp_enqueue_style(
        'font-awesome-6',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        array(),
        '6.5.2'
    );

    // Leaflet CSS
    wp_enqueue_style(
        'leaflet-css',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
        array(),
        '1.9.4'
    );

    // Leaflet JS
    wp_enqueue_script(
        'leaflet-js',
        'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
        array(),
        '1.9.4',
        true
    );

    // Header behavior (sticky / color change / wishlist toggle)
    wp_enqueue_script(
        'woocom-ct-header',
        get_stylesheet_directory_uri() . '/assets/js/header.js',
        array(),
        wp_get_theme()->get( 'Version' ),
        true
    );

    $logout_url = function_exists( 'wc_logout_url' ) && function_exists( 'wc_get_page_permalink' )
        ? wc_logout_url( wc_get_page_permalink( 'myaccount' ) )
        : wp_logout_url( home_url( '/' ) );
    wp_localize_script(
        'woocom-ct-header',
        'noyonaHeader',
        array(
            'logoutUrl' => $logout_url,
        )
    );

    // Product-gatherer JS
    wp_enqueue_script(
        'woocom-ct-product-gatherer',
        get_stylesheet_directory_uri() . '/assets/js/product-gatherer.js',
        array(),
        wp_get_theme()->get( 'Version' ),
        true
    );
}



// Make sure theme declares WooCommerce support
add_action( 'after_setup_theme', function() {
    add_theme_support( 'woocommerce' );
} );

add_action( 'woocommerce_login_form_end', 'woocom_ct_add_register_link_to_login' );
function woocom_ct_add_register_link_to_login() {
    echo '<p class="noyona-login-register-link"><a href="' . esc_url( home_url( '/register/' ) ) . '">Registration</a></p>';
}

// Shortcode: [product_gatherer]
add_shortcode( 'product_gatherer', 'woocom_ct_product_gatherer_shortcode' );
function woocom_ct_product_gatherer_shortcode( $atts ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return '<p>WooCommerce is not active.</p>';
    }

    // Read filters from query string
    $search = isset( $_GET['pg_search'] ) ? sanitize_text_field( wp_unslash( $_GET['pg_search'] ) ) : '';
    $sort   = isset( $_GET['pg_sort'] ) ? sanitize_text_field( wp_unslash( $_GET['pg_sort'] ) ) : 'default';
    $cat    = isset( $_GET['pg_cat'] )  ? sanitize_text_field( wp_unslash( $_GET['pg_cat'] ) )  : '';

    // Sorting logic
    $orderby  = 'title';
    $order    = 'ASC';
    $meta_key = '';

    switch ( $sort ) {
        case 'price_asc':
            $orderby  = 'meta_value_num';
            $order    = 'ASC';
            $meta_key = '_price';
            break;
        case 'price_desc':
            $orderby  = 'meta_value_num';
            $order    = 'DESC';
            $meta_key = '_price';
            break;
        case 'latest':
            $orderby  = 'date';
            $order    = 'DESC';
            break;
        default:
            // title ASC
            break;
    }

    $paged = isset( $_GET['pg_page'] ) ? max( 1, (int) $_GET['pg_page'] ) : 1;

    $args = array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 12, // 4 x 3 cards per page
        'paged'          => $paged,
        'orderby'        => $orderby,
        'order'          => $order,
    );

    if ( $meta_key ) {
        $args['meta_key'] = $meta_key;
    }

    if ( $search ) {
        $args['s'] = $search;
    }

    if ( $cat ) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => $cat,
            ),
        );
    }

    $query = new WP_Query( $args );

    // Get categories for dropdown
    $categories = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        )
    );

    ob_start();
    ?>

    <div class="pg-wrapper">
        <div class="pg-header">
            <div>
                <h2 class="pg-title">Products</h2>
                <p class="pg-subtitle">Discover our curated beauty products.</p>
            </div>

            <form class="pg-toolbar-form" method="get">
                <div class="pg-toolbar">

                    <div class="pg-search">
                        <input
                            type="text"
                            name="pg_search"
                            value="<?php echo esc_attr( $search ); ?>"
                            placeholder="Search products (e.g. lipstick)"
                        />
                        <button type="submit" class="pg-search-btn">
                            <i class="fa fa-search"></i>
                        </button>
                    </div>

                    <div class="pg-filters">
                        <select name="pg_sort" class="pg-select">
                            <option value="default" <?php selected( $sort, 'default' ); ?>>Sort: Default</option>
                            <option value="latest" <?php selected( $sort, 'latest' ); ?>>Newest</option>
                            <option value="price_asc" <?php selected( $sort, 'price_asc' ); ?>>Price: Low to High</option>
                            <option value="price_desc" <?php selected( $sort, 'price_desc' ); ?>>Price: High to Low</option>
                        </select>

                        <select name="pg_cat" class="pg-select">
                            <option value="">All categories</option>
                            <?php foreach ( $categories as $category ) : ?>
                                <option value="<?php echo esc_attr( $category->slug ); ?>" <?php selected( $cat, $category->slug ); ?>>
                                    <?php echo esc_html( $category->name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="pg-grid">
            <?php
            if ( $query->have_posts() ) :
                while ( $query->have_posts() ) :
                    $query->the_post();
                    $product = wc_get_product( get_the_ID() );
                    if ( ! $product ) {
                        continue;
                    }

                    $image_url    = get_the_post_thumbnail_url( get_the_ID(), 'medium' );
                    $price_html   = $product->get_price_html();
                    $rating_html  = wc_get_rating_html( $product->get_average_rating() );
                    $add_url      = esc_url( $product->add_to_cart_url() );
                    $add_text     = esc_html( $product->add_to_cart_text() );
                    $is_on_sale   = $product->is_on_sale();
                    ?>
                    <article class="pg-card">
                        <div class="pg-card-media">
                            <?php if ( $image_url ) : ?>
                                <a href="<?php the_permalink(); ?>">
                                    <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php the_title_attribute(); ?>" />
                                </a>
                            <?php endif; ?>

                            <?php if ( $is_on_sale ) : ?>
                                <span class="pg-badge-sale">SALE</span>
                            <?php endif; ?>

                            <button class="pg-wishlist-btn" type="button">
                                <i class="fa-regular fa-heart"></i>
                            </button>
                        </div>

                        <div class="pg-card-body">
                            <h3 class="pg-product-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                            </h3>

                            <div class="pg-rating">
                                <?php echo $rating_html ? $rating_html : '<span class="pg-rating-placeholder">No reviews yet</span>'; ?>
                            </div>

                            <p class="pg-excerpt">
                                <?php echo wp_kses_post( wp_trim_words( get_the_excerpt(), 16 ) ); ?>
                            </p>

                            <div class="pg-card-footer">
                                <div class="pg-price">
                                    <?php echo wp_kses_post( $price_html ); ?>
                                </div>
                                <a
                                    href="<?php echo $add_url; ?>"
                                    data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                                    class="pg-add-to-cart-button add_to_cart_button ajax_add_to_cart"
                                >
                                    <i class="fa fa-shopping-cart"></i>
                                    <?php echo $add_text; ?>
                                </a>
                            </div>
                        </div>
                    </article>
                    <?php
                endwhile;
            else :
                ?>
                <p class="pg-no-results">No products found for your filters.</p>
                <?php
            endif;
            ?>
        </div>

        <?php if ( $query->max_num_pages > 1 ) : ?>
            <div class="pg-pagination">
                <?php
                echo paginate_links(
                    array(
                        'current' => $paged,
                        'total'   => $query->max_num_pages,
                        'format'  => '&pg_page=%#%',
                        'type'    => 'list',
                    )
                );
                ?>
            </div>
        <?php endif; ?>

    </div><!-- .pg-wrapper -->

    <?php
    wp_reset_postdata();

    return ob_get_clean();
}

// Register custom blocks
add_action( 'init', 'woocom_ct_register_blocks' );
function woocom_ct_register_blocks() {
    register_block_type( get_stylesheet_directory() . '/blocks/search-expand' );
    register_block_type( get_stylesheet_directory() . '/blocks/hero-banner' );
    register_block_type( get_stylesheet_directory() . '/blocks/brand-carousel' );
    register_block_type( get_stylesheet_directory() . '/blocks/color-swatches' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-highlight' );
    register_block_type( get_stylesheet_directory() . '/blocks/product-slide' );
    register_block_type( get_stylesheet_directory() . '/blocks/collection-grid' );
    register_block_type( get_stylesheet_directory() . '/blocks/phone-video-reviews' );
    register_block_type( get_stylesheet_directory() . '/blocks/discover-posts-carousel' );
    register_block_type( get_stylesheet_directory() . '/blocks/founder-section' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq' );
    register_block_type( get_stylesheet_directory() . '/blocks/faq-list' );
    register_block_type( get_stylesheet_directory() . '/blocks/contact' );
    register_block_type( get_stylesheet_directory() . '/blocks/location' );
    register_block_type( get_stylesheet_directory() . '/blocks/not-found' );
    register_block_type( get_stylesheet_directory() . '/blocks/smoke' );
    register_block_type( get_stylesheet_directory() . '/blocks/contact-form' );
    register_block_type( get_stylesheet_directory() . '/blocks/blog-list' );
    register_block_type( get_stylesheet_directory() . '/blocks/blog-slide' );
    register_block_type( get_stylesheet_directory() . '/blocks/blogs-view' );
    register_block_type( get_stylesheet_directory() . '/blocks/terms' );
    register_block_type( get_stylesheet_directory() . '/blocks/thank-you' );
    register_block_type( get_stylesheet_directory() . '/blocks/coming-soon' );
}

// Force 404 when WP falls back to the homepage for unknown paths.
add_action( 'template_redirect', 'noyona_force_404_for_unknown_routes', 1 );
function noyona_force_404_for_unknown_routes() {
    if ( is_admin() || wp_doing_ajax() ) {
        return;
    }

    if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
        return;
    }

    if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
        return;
    }

    if ( is_404() ) {
        return;
    }

    global $wp, $wp_query;
    if ( ! isset( $wp ) ) {
        return;
    }

    $request = trim( $wp->request );
    if ( '' === $request ) {
        return;
    }

    $ignore_prefixes = array(
        'wp-admin',
        'wp-login.php',
        'wp-login',
        'wp-cron.php',
        'wp-json',
        'xmlrpc.php',
        'robots.txt',
        'favicon.ico',
        'sitemap.xml',
    );

    foreach ( $ignore_prefixes as $prefix ) {
        if ( 0 === strpos( $request, $prefix ) ) {
            return;
        }
    }

    if ( is_home() || is_front_page() ) {
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
    }
}

add_shortcode( 'noyona_register_form', 'woocom_ct_register_form_shortcode' );
function woocom_ct_register_form_shortcode() {
    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );

    if ( is_user_logged_in() ) {
        return '<div class="noyona-register-message">You are already logged in. <a href="' . esc_url( $account_url ) . '">Go to My Account</a>.</div>';
    }

    $error_code = isset( $_GET['register_error'] )
        ? sanitize_text_field( wp_unslash( $_GET['register_error'] ) )
        : '';
    $success = isset( $_GET['register_success'] );
    $message = '';
    $message_class = '';

    if ( $success ) {
        $message = 'Account created successfully. You can now log in.';
        $message_class = ' is-success';
    } elseif ( $error_code ) {
        switch ( $error_code ) {
            case 'missing_fields':
                $message = 'Please fill in all required fields.';
                break;
            case 'invalid_email':
                $message = 'Please enter a valid email address.';
                break;
            case 'email_exists':
                $message = 'This email is already registered.';
                break;
            case 'password_mismatch':
                $message = 'Passwords do not match.';
                break;
            case 'invalid_request':
                $message = 'Invalid registration request. Please try again.';
                break;
            default:
                $message = 'Registration failed. Please try again.';
                break;
        }
        $message_class = ' is-error';
    }

    ob_start();
    ?>
    <div class="noyona-register">
        <div class="noyona-register-card">
            <h2 class="noyona-register-title">Create Account</h2>

            <?php if ( $message ) : ?>
                <div class="noyona-register-message<?php echo esc_attr( $message_class ); ?>">
                    <?php echo esc_html( $message ); ?>
                </div>
            <?php endif; ?>

            <form class="noyona-register-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'noyona_register_action', 'noyona_register_nonce' ); ?>
                <input type="hidden" name="action" value="noyona_register">

                <div class="noyona-register-row">
                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-first-name">First Name</label>
                        <input id="noyona-register-first-name" type="text" name="first_name" autocomplete="given-name" placeholder="First name" required>
                    </div>

                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-middle-name">Middle Name</label>
                        <input id="noyona-register-middle-name" type="text" name="middle_name" autocomplete="additional-name" placeholder="Middle name" required>
                    </div>
                </div>

                <div class="noyona-register-row">
                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-last-name">Last Name</label>
                        <input id="noyona-register-last-name" type="text" name="last_name" autocomplete="family-name" placeholder="Last name" required>
                    </div>

                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-email">Email</label>
                        <input id="noyona-register-email" type="email" name="email" autocomplete="email" placeholder="Your email" required>
                    </div>
                </div>

                <div class="noyona-register-row">
                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-password">Password</label>
                        <input id="noyona-register-password" type="password" name="password" autocomplete="new-password" placeholder="Password" required>
                    </div>

                    <div class="noyona-register-field">
                        <label class="screen-reader-text" for="noyona-register-confirm">Confirm Password</label>
                        <input id="noyona-register-confirm" type="password" name="confirm_password" autocomplete="new-password" placeholder="Repeat your password" required>
                    </div>
                </div>

                <label class="noyona-register-terms">
                    <input type="checkbox" name="terms" value="1">
                    <span>I agree all statements in <a href="/terms-of-service/">Terms of service</a></span>
                </label>

                <button class="noyona-register-submit" type="submit">Sign Up</button>
            </form>

            <p class="noyona-register-login">
                Have already an account ? <a href="<?php echo esc_url( $account_url ); ?>">Login here</a>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_action( 'admin_post_nopriv_noyona_register', 'woocom_ct_handle_register_form' );
add_action( 'admin_post_noyona_register', 'woocom_ct_handle_register_form' );
function woocom_ct_handle_register_form() {
    $redirect = wp_get_referer();
    if ( ! $redirect ) {
        $redirect = home_url( '/register/' );
    }
    $redirect = remove_query_arg( array( 'register_error', 'register_success' ), $redirect );

    if ( ! isset( $_POST['noyona_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['noyona_register_nonce'] ) ), 'noyona_register_action' ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_request', $redirect ) );
        exit;
    }

    if ( is_user_logged_in() ) {
        wp_safe_redirect( $redirect );
        exit;
    }

    $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['first_name'] ) ) : '';
    $middle_name = isset( $_POST['middle_name'] ) ? sanitize_text_field( wp_unslash( $_POST['middle_name'] ) ) : '';
    $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['last_name'] ) ) : '';
    $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
    $password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '';
    $confirm_password = isset( $_POST['confirm_password'] ) ? (string) wp_unslash( $_POST['confirm_password'] ) : '';

    if ( '' === $first_name || '' === $middle_name || '' === $last_name || '' === $email || '' === $password || '' === $confirm_password ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'missing_fields', $redirect ) );
        exit;
    }

    if ( ! is_email( $email ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_email', $redirect ) );
        exit;
    }

    if ( email_exists( $email ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'email_exists', $redirect ) );
        exit;
    }

    if ( $password !== $confirm_password ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'password_mismatch', $redirect ) );
        exit;
    }

    $email_parts = explode( '@', $email );
    $base_username = sanitize_user( $email_parts[0], true );
    if ( '' === $base_username ) {
        $base_username = 'customer';
    }
    $username = woocom_ct_unique_username( $base_username );
    if ( ! validate_username( $username ) ) {
        $username = woocom_ct_unique_username( 'customer' );
    }

    $role = get_role( 'customer' ) ? 'customer' : 'subscriber';
    $display_name = trim( $first_name . ' ' . $last_name );

    if ( function_exists( 'wc_create_new_customer' ) ) {
        $user_id = wc_create_new_customer(
            $email,
            $username,
            $password,
            array(
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'role'         => $role,
            )
        );
    } else {
        $user_id = wp_insert_user(
            array(
                'user_login'   => $username,
                'user_email'   => $email,
                'user_pass'    => $password,
                'first_name'   => $first_name,
                'last_name'    => $last_name,
                'display_name' => $display_name,
                'role'         => $role,
            )
        );
    }

    if ( is_wp_error( $user_id ) ) {
        wp_safe_redirect( add_query_arg( 'register_error', 'invalid_request', $redirect ) );
        exit;
    }

    update_user_meta( $user_id, 'middle_name', $middle_name );
    if ( function_exists( 'wc_create_new_customer' ) ) {
        update_user_meta( $user_id, 'billing_first_name', $first_name );
        update_user_meta( $user_id, 'billing_last_name', $last_name );
    }

    wp_set_current_user( $user_id );
    wp_set_auth_cookie( $user_id, true );
    $user = get_user_by( 'id', $user_id );
    if ( $user ) {
        do_action( 'wp_login', $user->user_login, $user );
    }

    $account_url = function_exists( 'wc_get_page_permalink' )
        ? wc_get_page_permalink( 'myaccount' )
        : home_url( '/my-account/' );
    if ( ! $account_url ) {
        $account_url = home_url( '/' );
    }

    wp_safe_redirect( $account_url );
    exit;
}

function woocom_ct_unique_username( $base_username ) {
    $base_username = sanitize_user( $base_username, true );
    if ( '' === $base_username ) {
        $base_username = 'customer';
    }
    $username = $base_username;
    $suffix = 1;

    while ( username_exists( $username ) ) {
        $username = $base_username . $suffix;
        $suffix++;
    }

    return $username;
}

function woocom_ct_get_favorite_store_ids( $user_id ) {
    $favorites = get_user_meta( $user_id, 'noyona_store_favorites', true );
    if ( ! is_array( $favorites ) ) {
        $favorites = [];
    }
    $favorites = array_values( array_unique( array_filter( array_map( 'absint', $favorites ) ) ) );
    return $favorites;
}

add_action( 'wp_ajax_noyona_toggle_favorite', 'woocom_ct_handle_toggle_favorite' );
function woocom_ct_handle_toggle_favorite() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( array( 'message' => 'not_logged_in' ), 401 );
    }

    check_ajax_referer( 'noyona_favorites', 'nonce' );

    $store_id = isset( $_POST['store_id'] ) ? absint( wp_unslash( $_POST['store_id'] ) ) : 0;
    $mode = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'toggle';

    if ( ! $store_id ) {
        wp_send_json_error( array( 'message' => 'invalid_store' ), 400 );
    }

    $store = get_post( $store_id );
    if ( ! $store || 'store' !== $store->post_type ) {
        wp_send_json_error( array( 'message' => 'store_not_found' ), 404 );
    }

    $user_id = get_current_user_id();
    $favorites = woocom_ct_get_favorite_store_ids( $user_id );

    if ( 'add' === $mode ) {
        if ( ! in_array( $store_id, $favorites, true ) ) {
            $favorites[] = $store_id;
        }
    } elseif ( 'remove' === $mode ) {
        $favorites = array_values( array_diff( $favorites, array( $store_id ) ) );
    } else {
        if ( in_array( $store_id, $favorites, true ) ) {
            $favorites = array_values( array_diff( $favorites, array( $store_id ) ) );
        } else {
            $favorites[] = $store_id;
        }
    }

    update_user_meta( $user_id, 'noyona_store_favorites', $favorites );

    wp_send_json_success( array( 'favorites' => $favorites ) );
}

add_action( 'woocommerce_account_dashboard', 'woocom_ct_render_account_favorites', 15 );
function woocom_ct_render_account_favorites() {
    if ( ! is_user_logged_in() ) {
        return;
    }

    $favorites = woocom_ct_get_favorite_store_ids( get_current_user_id() );

    echo '<div class="noyona-account-favorites">';
    echo '<h3>Favorite Stores</h3>';

    if ( empty( $favorites ) ) {
        echo '<p class="noyona-account-favorites-empty">No favorite stores yet.</p>';
        echo '</div>';
        return;
    }

    $stores = get_posts(
        array(
            'post_type' => 'store',
            'post__in' => $favorites,
            'orderby' => 'post__in',
            'posts_per_page' => -1,
        )
    );

    if ( empty( $stores ) ) {
        echo '<p class="noyona-account-favorites-empty">No favorite stores yet.</p>';
        echo '</div>';
        return;
    }

    echo '<ul class="noyona-account-favorites-list">';
    foreach ( $stores as $store ) {
        echo '<li><a href="' . esc_url( get_permalink( $store ) ) . '">' . esc_html( get_the_title( $store ) ) . '</a></li>';
    }
    echo '</ul>';
    echo '</div>';
}

add_filter( 'render_block', 'woocom_ct_remove_customer_account_block', 10, 2 );
function woocom_ct_remove_customer_account_block( $block_content, $block ) {
    if ( ! empty( $block['blockName'] ) && 'woocommerce/customer-account' === $block['blockName'] ) {
        return '';
    }

    return $block_content;
}
