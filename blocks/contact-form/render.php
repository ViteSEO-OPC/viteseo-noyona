<?php
/**
 * Contact Form Block Template.
 */

$defaults = array(
    'heading' => '<span class="contact-form__accent">Connect</span> with our <span class="contact-form__accent">Community</span>',
    'subheading' => 'Stay updated on product drops, ingredient deep dives, application tips, and behind-the-scenes content from our team.',
    'leftTitle' => "Let's Connect",
    'brandImage' => '/wp-content/themes/viteseo-noyona/assets/images/contact-logo.png',
    'socials' => array(),
    'contactLabel' => 'Our Contact',
    'phone' => '0920 510 5555 ',
    'email' => 'info@noyonaessenitals.com',
    'locationLabel' => 'Our Location',
    'address' => 'Burgundy Corporate Tower 252 Sen. Gil Puyat Ave., Makati City, Metro Manila, Philippines',
    'mapEmbedUrl' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3861.682250594846!2d121.0104176759039!3d14.560154478064042!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3397c9bae484786d%3A0x9f8a3e1ea9d1add3!2sNoyona%20Cosmetics%20and%20Skin%20Care%20Products%2C%20OPC!5e0!3m2!1sen!2sph!4v1770599333042!5m2!1sen!2sph',
    'mapNote' => 'View our store location to confirm address details, operating hours, and get turn-by-turn directions in one click.',
    'mapButtonText' => 'View Store Locations',
    'mapButtonUrl' => '/store-location/',
    'formAction' => '',
    'formButtonText' => 'Submit Message',
);

$atts = wp_parse_args($attributes, $defaults);
$socials = is_array($atts['socials']) ? $atts['socials'] : array();
$heading = $atts['heading'];
$subheading = $atts['subheading'];
$form_action = $atts['formAction'];
$theme_uri = get_stylesheet_directory_uri();
$social_asset_map = array(
    'facebook'  => $theme_uri . '/blocks/contact-form/assets/fb.png',
    'tiktok'    => $theme_uri . '/blocks/contact-form/assets/tiktok.png',
    'instagram' => $theme_uri . '/blocks/contact-form/assets/instagram.png',
    'lazada'    => $theme_uri . '/blocks/contact-form/assets/lazada.png',
    'shopee'    => $theme_uri . '/blocks/contact-form/assets/shopee.png',
);
$social_icon_map = array(
    'facebook'  => 'fa-brands fa-facebook-f',
    'tiktok'    => 'fa-brands fa-tiktok',
    'instagram' => 'fa-brands fa-instagram',
    'shopee'    => 'fa-solid fa-bag-shopping',
);

$allowed_heading = array(
    'span' => array(
        'class' => array(),
    ),
    'em' => array(),
    'strong' => array(),
);
?>
<section class="wp-block-noyona-contact-form contact-form alignwide">
    <div class="contact-form__header">
        <?php if (!empty($heading)): ?>
            <h2 class="contact-form__title">
                <?php echo wp_kses($heading, $allowed_heading); ?>
            </h2>
        <?php endif; ?>
        <?php if (!empty($subheading)): ?>
            <p class="contact-form__subtitle"><?php echo esc_html($subheading); ?></p>
        <?php endif; ?>
    </div>

    <div class="contact-form__grid">
        <div class="contact-form__card">
            <div class="contact-form__card-top">
                <div class="contact-form__card-left">
                    <?php if (!empty($atts['leftTitle'])): ?>
                        <p class="contact-form__card-title"><?php echo esc_html($atts['leftTitle']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($socials)): ?>
                        <div class="contact-form__socials">
                            <?php
                            foreach ($socials as $social):
                                $icon = isset($social['icon']) ? $social['icon'] : '';
                                $url = isset($social['url']) ? $social['url'] : '#';
                                $label = isset($social['label']) ? $social['label'] : 'Social link';
                                $label_lower = strtolower((string) $label);
                                $url_lower = strtolower((string) $url);
                                $brand_key = '';
                                if (false !== strpos($label_lower, 'facebook') || false !== strpos($url_lower, 'facebook')) {
                                    $brand_key = 'facebook';
                                } elseif (false !== strpos($label_lower, 'tiktok') || false !== strpos($url_lower, 'tiktok')) {
                                    $brand_key = 'tiktok';
                                } elseif (false !== strpos($label_lower, 'instagram') || false !== strpos($url_lower, 'instagram')) {
                                    $brand_key = 'instagram';
                                } elseif (false !== strpos($label_lower, 'lazada') || false !== strpos($url_lower, 'lazada')) {
                                    $brand_key = 'lazada';
                                } elseif (false !== strpos($label_lower, 'shopee') || false !== strpos($url_lower, 'shopee')) {
                                    $brand_key = 'shopee';
                                }

                                if (empty($icon) && !empty($brand_key) && isset($social_asset_map[$brand_key])) {
                                    $icon = $social_asset_map[$brand_key];
                                }

                                if (empty($icon) && empty($brand_key)) {
                                    continue;
                                }

                                $is_image = false;
                                if (is_string($icon)) {
                                    if (preg_match('/\.(png|jpe?g|svg|webp)(\?.*)?$/i', $icon)) {
                                        $is_image = true;
                                    } elseif (0 === strpos($icon, '/') || 0 === strpos($icon, 'http')) {
                                        $is_image = true;
                                    }
                                }
                                $social_classes = 'contact-form__social-link';
                                if (!empty($brand_key)) {
                                    $social_classes .= ' contact-form__social-link--' . $brand_key;
                                }
                                ?>
                                <!-- <?php echo esc_url($url); ?> -->
                                <a class="<?php echo esc_attr($social_classes); ?>" href="coming-soon" 
                                    aria-label="<?php echo esc_attr($label); ?>">
                                    <?php if (!empty($brand_key) && isset($social_icon_map[$brand_key])): ?>
                                        <i class="<?php echo esc_attr($social_icon_map[$brand_key]); ?>"></i>
                                    <?php elseif ($is_image): ?>
                                        <img class="contact-form__social-icon" src="<?php echo esc_url($icon); ?>" alt="<?php echo esc_attr($label); ?>" />
                                    <?php else: ?>
                                        <i class="<?php echo esc_attr($icon); ?>"></i>
                                    <?php endif; ?>
                                </a>
                                <?php
                            endforeach;
                            ?>
                        </div>
                    <?php endif; ?>

                    <div class="contact-form__info">
                        <?php if (!empty($atts['contactLabel'])): ?>
                            <p class="contact-form__info-title"><?php echo esc_html($atts['contactLabel']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($atts['phone'])): ?>
                            <p class="contact-form__info-item">
                                <i class="fa-solid fa-phone"></i>
                                <span><?php echo esc_html($atts['phone']); ?></span>
                            </p>
                        <?php endif; ?>
                        <?php if (!empty($atts['email'])): ?>
                            <p class="contact-form__info-item">
                                <i class="fa-regular fa-envelope"></i>
                                <span><?php echo esc_html($atts['email']); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="contact-form__info">
                        <?php if (!empty($atts['locationLabel'])): ?>
                            <p class="contact-form__info-title"><?php echo esc_html($atts['locationLabel']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($atts['address'])): ?>
                            <p class="contact-form__info-item">
                                <i class="fa-solid fa-location-dot"></i>
                                <span><?php echo esc_html($atts['address']); ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if (!empty($atts['brandImage'])): ?>
                    <div class="contact-form__brand">
                        <img src="<?php echo esc_url($atts['brandImage']); ?>" alt="Noyona essentials" />
                    </div>
                <?php endif; ?>
            </div>

            <div class="contact-form__map">
                <?php if (!empty($atts['mapEmbedUrl'])): ?>
                    <iframe
                        src="<?php echo esc_url($atts['mapEmbedUrl']); ?>"
                        width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                <?php else: ?>
                    <div class="contact-form__map-placeholder">Map preview</div>
                <?php endif; ?>
            </div>

            <?php if (!empty($atts['mapNote']) || (!empty($atts['mapButtonText']) && !empty($atts['mapButtonUrl']))): ?>
                <div class="contact-form__map-row">
                    <?php if (!empty($atts['mapNote'])): ?>
                        <p class="contact-form__map-note"><?php echo esc_html($atts['mapNote']); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($atts['mapButtonText']) && !empty($atts['mapButtonUrl'])): ?>
                        <a class="contact-form__map-button" href="<?php echo esc_url($atts['mapButtonUrl']); ?>">
                            <?php echo esc_html($atts['mapButtonText']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <form class="contact-form__form" action="<?php echo esc_url($form_action); ?>" method="post">
            <div class="contact-form__fields">
                <label class="contact-form__field contact-form__field--full">
                    <span>First Name *</span>
                    <input type="text" name="first_name" placeholder="" required />
                </label>
                <label class="contact-form__field contact-form__field--full">
                    <span>Last Name *</span>
                    <input type="text" name="last_name" placeholder="" required />
                </label>
                <label class="contact-form__field contact-form__field--full">
                    <span>Email *</span>
                    <input type="email" name="email" placeholder="" required />
                </label>
                <label class="contact-form__field contact-form__field--full">
                    <span>Contact Number *</span>
                    <input type="tel" name="contact_number" placeholder="" required />
                </label>
                <label class="contact-form__field contact-form__field--full">
                    <span>Subject *</span>
                    <input type="text" name="subject" placeholder="Order Support (Status, Returns, Issues)" required />
                </label>
                <label class="contact-form__field contact-form__field--full">
                    <span>Message *</span>
                    <textarea name="message" rows="5"
                        placeholder="Please include relevant details (order number, product name, or platform used) so we can assist you faster."
                        required></textarea>
                </label>
            </div>

            <div class="contact-form__submit-row">
                <p class="contact-form__submit-note">Submit your message and our support team will review it and respond
                    to you via email within 1–2 business days.</p>
                <button class="contact-form__submit" type="submit">
                    <?php echo esc_html($atts['formButtonText']); ?>
                </button>
            </div>
        </form>
    </div>
</section>