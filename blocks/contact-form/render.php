<?php
/**
 * Contact Form Block Template.
 */

$defaults = array(
    'heading' => '<span class="contact-form__accent">Connect</span> with our <span class="contact-form__accent">Community</span>',
    'subheading' => 'Stay updated on product drops, ingredient deep dives, application tips, and behind-the-scenes content from our team.',
    'leftTitle' => "Let's Connect",
    'brandImage' => 'https://noyonacosmetics.com/wp-content/uploads/2026/02/contact-logo.png',
    'brandImageId' => 0,
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
$brand_image = !empty($atts['brandImage']) ? (string) $atts['brandImage'] : '';
$brand_image_id = isset($atts['brandImageId']) ? absint($atts['brandImageId']) : 0;
if ($brand_image_id) {
    $resolved_brand_image = wp_get_attachment_image_url($brand_image_id, 'large');
    if ($resolved_brand_image) {
        $brand_image = (string) $resolved_brand_image;
    }
} elseif (!empty($brand_image)) {
    $resolved_brand_id = attachment_url_to_postid($brand_image);
    if ($resolved_brand_id) {
        $resolved_brand_image = wp_get_attachment_image_url((int) $resolved_brand_id, 'large');
        if ($resolved_brand_image) {
            $brand_image = (string) $resolved_brand_image;
        }
    }
}
$theme_uri = get_stylesheet_directory_uri();
$social_asset_map = array(
    'facebook'  => $theme_uri . '/assets/images/fb_icon.webp',
    'tiktok'    => $theme_uri . '/assets/images/titktok_icon.webp',
    'instagram' => $theme_uri . '/assets/images/instagram_icon.webp',
    'lazada'    => $theme_uri . '/assets/images/lazada_icon.webp',
    'shopee'    => $theme_uri . '/assets/images/shopee_icon.webp',
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
                                <a class="<?php echo esc_attr($social_classes); ?>" href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer"
                                    aria-label="<?php echo esc_attr($label); ?>" data-tooltip="<?php echo esc_attr($label); ?>">
                                    <?php if ($is_image): ?>
                                        <img class="contact-form__social-icon" src="<?php echo esc_url($icon); ?>" alt="<?php echo esc_attr($label); ?>" loading="lazy" decoding="async" />
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
                            <?php
                                $phone_display = $atts['phone'];
                                // Keep only digits and leading + for tel link
                                $phone_href = preg_replace('/(?!^\+)[^\d]/', '', trim($atts['phone']));
                            ?>
                            <p class="contact-form__info-item">
                                <i class="fa-solid fa-phone"></i>
                                <a href="tel:<?php echo esc_attr($phone_href); ?>" class="contact-form__info-link">
                                    <?php echo esc_html($phone_display); ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($atts['email'])): ?>
                            <?php
                                $email = sanitize_email($atts['email']);
                            ?>
                            <p class="contact-form__info-item">
                                <i class="fa-regular fa-envelope"></i>
                                <a href="mailto:<?php echo esc_attr($email); ?>" class="contact-form__info-link">
                                    <?php echo esc_html($email); ?>
                                </a>
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

                <?php if (!empty($brand_image)): ?>
                    <div class="contact-form__brand">
                        <img src="<?php echo esc_url($brand_image); ?>" alt="Noyona essentials" loading="lazy" decoding="async" />
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

        <?php $form_id = wp_unique_id('noyona-contact-form-'); ?>
        <form id="<?php echo esc_attr($form_id); ?>" class="contact-form__form" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" novalidate>
            <?php wp_nonce_field( 'noyona_contact_form', 'noyona_contact_form_nonce' ); ?>
            <input type="hidden" name="action" value="noyona_contact_form_submit">

            <!-- Honeypot (bots fill this, humans won't) -->
            <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;">
                <label>Leave this field empty</label>
                <input type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <div class="contact-form__fields">
            <label class="contact-form__field contact-form__field--full">
                <span>First Name *</span>
                <input
                    id="<?php echo esc_attr($form_id); ?>-first"
                    type="text"
                    name="first_name"
                    required
                    autocomplete="given-name"
                    maxlength="60"
                    pattern="^[A-Za-zÀ-ÿ]+(?:[ '\-][A-Za-zÀ-ÿ]+)*$"
                    title="Letters only. Spaces, hyphen (-), apostrophe (') allowed."
                    aria-describedby="<?php echo esc_attr($form_id); ?>-first-error"
                    placeholder="Juan"
                />
                <small class="contact-form__error" id="<?php echo esc_attr($form_id); ?>-first-error" aria-live="polite"></small>
                </label>

                <label class="contact-form__field contact-form__field--full">
                <span>Last Name *</span>
                <input
                    id="<?php echo esc_attr($form_id); ?>-last"
                    type="text"
                    name="last_name"
                    required
                    autocomplete="family-name"
                    maxlength="60"
                    pattern="^[A-Za-zÀ-ÿ]+(?:[ '\-][A-Za-zÀ-ÿ]+)*$"
                    title="Letters only. Spaces, hyphen (-), apostrophe (') allowed."
                    aria-describedby="<?php echo esc_attr($form_id); ?>-last-error"
                    placeholder="Dela Cruz"
                />
                <small class="contact-form__error" id="<?php echo esc_attr($form_id); ?>-last-error" aria-live="polite"></small>
                </label>

                <label class="contact-form__field contact-form__field--full">
                <span>Email *</span>
                <input
                    id="<?php echo esc_attr($form_id); ?>-email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    maxlength="120"
                    aria-describedby="<?php echo esc_attr($form_id); ?>-email-error"
                    placeholder="juan.delacruz@example.com"
                />
                <small class="contact-form__error" id="<?php echo esc_attr($form_id); ?>-email-error" aria-live="polite"></small>
                </label>

                <label class="contact-form__field contact-form__field--full">
                <span>Contact Number *</span>
                <input
                    id="<?php echo esc_attr($form_id); ?>-phone"
                    type="tel"
                    name="contact_number"
                    required
                    autocomplete="tel"
                    inputmode="tel"
                    maxlength="25"
                    pattern="^\+?[0-9\s\-\(\)]{7,25}$"
                    title="Use numbers only. You may include +, spaces, -, or parentheses."
                    aria-describedby="<?php echo esc_attr($form_id); ?>-phone-error"
                    placeholder="+63 920 510 5555"
                />
                <small class="contact-form__error" id="<?php echo esc_attr($form_id); ?>-phone-error" aria-live="polite"></small>
                </label>

                <label class="contact-form__field contact-form__field--full">
                <span>Subject *</span>
                <input
                    id="<?php echo esc_attr($form_id); ?>-subject"
                    type="text"
                    name="subject"
                    required
                    minlength="3"
                    maxlength="120"
                    aria-describedby="<?php echo esc_attr($form_id); ?>-subject-error"
                    placeholder="How can we help you?"
                />
                <small class="contact-form__error" id="<?php echo esc_attr($form_id); ?>-subject-error" aria-live="polite"></small>
                </label>

                <label class="contact-form__field contact-form__field--full">
                <span>Message *</span>
                <textarea
                    id="<?php echo esc_attr($form_id); ?>-message"
                    name="message"
                    rows="5"
                    required
                    minlength="10"
                    maxlength="2000"
                    placeholder="Please include relevant details..."
                    aria-describedby="<?php echo esc_attr($form_id); ?>-message-error"
                ></textarea>
                <small class="contact-form__error" id="<?php echo esc_attr($form_id); ?>-message-error" aria-live="polite"></small>
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

        <?php
            static $noyona_cf_script_printed = false;
            if (!$noyona_cf_script_printed) :
            $noyona_cf_script_printed = true;
            ?>
            <script>
            document.addEventListener('DOMContentLoaded', function () {

            function getMessage(el) {
                const v = el.validity;
                if (v.valueMissing) return 'This field is required.';
                if (v.typeMismatch && el.type === 'email') return 'Please enter a valid email address.';
                if (v.patternMismatch) return el.getAttribute('title') || 'Invalid format.';
                if (v.tooShort) return `Please enter at least ${el.minLength} characters.`;
                if (v.tooLong) return `Please use ${el.maxLength} characters or less.`;
                return 'Please check this field.';
            }

            function validateField(el, show) {
                const wrap = el.closest('.contact-form__field');
                if (!wrap) return true;

                const error = wrap.querySelector('.contact-form__error');
                const valid = el.checkValidity();

                // Don’t show errors on untouched empty fields
                const empty = !el.value || el.value.trim() === '';
                if (!show && empty) {
                wrap.classList.remove('is-invalid', 'is-valid');
                el.removeAttribute('aria-invalid');
                if (error) error.textContent = '';
                return true;
                }

                if (valid) {
                wrap.classList.remove('is-invalid');
                wrap.classList.add('is-valid');
                el.removeAttribute('aria-invalid');
                if (error) error.textContent = '';
                return true;
                } else {
                wrap.classList.add('is-invalid');
                wrap.classList.remove('is-valid');
                el.setAttribute('aria-invalid', 'true');
                if (error) error.textContent = getMessage(el);
                return false;
                }
            }

            // Apply to all contact forms on the page
            document.querySelectorAll('.contact-form__form').forEach(function(form) {
                const fields = form.querySelectorAll('input, textarea');

                fields.forEach(function(el){
                // show after blur (touched)
                el.addEventListener('blur', function(){
                    el.dataset.touched = '1';
                    validateField(el, true);
                });

                // live update after touched
                el.addEventListener('input', function(){
                    if (el.dataset.touched === '1') validateField(el, true);
                });
                });

                // block submit + show all errors
                form.addEventListener('submit', function(e){
                let firstInvalid = null;
                fields.forEach(function(el){
                    const ok = validateField(el, true);
                    if (!ok && !firstInvalid) firstInvalid = el;
                });

                if (firstInvalid) {
                    e.preventDefault();
                    firstInvalid.focus({ preventScroll: false });
                }
                });
            });

            });
            </script>
            <?php endif; ?>

    </div>
</section>