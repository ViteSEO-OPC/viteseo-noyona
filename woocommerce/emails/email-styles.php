<?php
/**
 * Email Styles
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-styles.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.7.0
 */

use Automattic\WooCommerce\Internal\Email\EmailFont;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email_improvements_enabled = FeaturesUtil::feature_is_enabled( 'email_improvements' );
$block_email_editor_enabled = FeaturesUtil::feature_is_enabled( 'block_email_editor' );

// Load colors.
$bg               = get_option( 'woocommerce_email_background_color' );
$body             = get_option( 'woocommerce_email_body_background_color' );
$base             = get_option( 'woocommerce_email_base_color' );
$text             = get_option( 'woocommerce_email_text_color' );
$footer_text      = get_option( 'woocommerce_email_footer_text_color' );
$header_alignment = get_option( 'woocommerce_email_header_alignment', $email_improvements_enabled ? 'left' : false );
$logo_image_width = get_option( 'woocommerce_email_header_image_width', '120' );
$default_font     = 'Helvetica';
$font_family      = $email_improvements_enabled ? get_option( 'woocommerce_email_font_family', $default_font ) : $default_font;

/**
 * Check if we are in preview mode (WooCommerce > Settings > Emails).
 *
 * @since 9.6.0
 * @param bool $is_email_preview Whether the email is being previewed.
 */
$is_email_preview = apply_filters( 'woocommerce_is_email_preview', false );

if ( $is_email_preview ) {
	$bg_transient               = get_transient( 'woocommerce_email_background_color' );
	$body_transient             = get_transient( 'woocommerce_email_body_background_color' );
	$base_transient             = get_transient( 'woocommerce_email_base_color' );
	$text_transient             = get_transient( 'woocommerce_email_text_color' );
	$footer_text_transient      = get_transient( 'woocommerce_email_footer_text_color' );
	$header_alignment_transient = get_transient( 'woocommerce_email_header_alignment' );
	$logo_image_width_transient = get_transient( 'woocommerce_email_header_image_width' );
	$font_family_transient      = get_transient( 'woocommerce_email_font_family' );

	$bg               = $bg_transient ? $bg_transient : $bg;
	$body             = $body_transient ? $body_transient : $body;
	$base             = $base_transient ? $base_transient : $base;
	$text             = $text_transient ? $text_transient : $text;
	$footer_text      = $footer_text_transient ? $footer_text_transient : $footer_text;
	$header_alignment = $header_alignment_transient ? $header_alignment_transient : $header_alignment;
	$logo_image_width = $logo_image_width_transient ? $logo_image_width_transient : $logo_image_width;
	$font_family      = $font_family_transient ? $font_family_transient : $font_family;
}

// Only use safe fonts. They won't be escaped to preserve single quotes.
$safe_font_family = EmailFont::$font[ $font_family ] ?? EmailFont::$font[ $default_font ];

$base_text = wc_light_or_dark( $base, '#202020', '#ffffff' );

// Pick a contrasting color for links.
$link_color = wc_hex_is_light( $base ) ? $base : $base_text;

if ( wc_hex_is_light( $body ) ) {
	$link_color = wc_hex_is_light( $base ) ? $base_text : $base;
}

// If email improvements are enabled, always use the base color for links.
if ( $email_improvements_enabled ) {
	$link_color = $base;
}

$link_color = '#E199A4';

$border_color    = wc_light_or_dark( $body, 'rgba(0, 0, 0, .2)', 'rgba(255, 255, 255, .2)' );
$bg_darker_10    = wc_hex_darker( $bg, 10 );
$body_darker_10  = wc_hex_darker( $body, 10 );
$base_lighter_20 = wc_hex_lighter( $base, 20 );
$base_lighter_40 = wc_hex_lighter( $base, 40 );
$text_lighter_20 = wc_hex_lighter( $text, 20 );
$text_lighter_40 = wc_hex_lighter( $text, 40 );

// !important; is a gmail hack to prevent styles being stripped if it doesn't like something.
// body{padding: 0;} ensures proper scale/positioning of the email in the iOS native email app.
?>
body {
	background-color: <?php echo esc_attr( $bg ); ?>;
	padding: 0;
	text-align: center;
}

#outer_wrapper {
	background-color: <?php echo esc_attr( $bg ); ?>;
}

<?php if ( $email_improvements_enabled ) : ?>
#inner_wrapper {
	background-color: <?php echo esc_attr( $body ); ?>;
	border-radius: 8px;
}
<?php endif; ?>

#wrapper {
	margin: 0 auto;
	padding: <?php echo $email_improvements_enabled ? '24px 0' : '70px 0'; ?>;
	-webkit-text-size-adjust: none !important;
	width: 100%;
	max-width: 600px;
}

#template_container {
	box-shadow: <?php echo $email_improvements_enabled ? 'none' : '0 1px 4px rgba(0, 0, 0, 0.1) !important'; ?>;
	background-color: <?php echo esc_attr( $body ); ?>;
	border: <?php echo $email_improvements_enabled ? '0' : '1px solid ' . esc_attr( $bg_darker_10 ); ?>;
	border-radius: 3px !important;
}

#template_header {
	background-color: <?php echo esc_attr( $email_improvements_enabled ? $body : $base ); ?>;
	border-radius: 3px 3px 0 0 !important;
	color: <?php echo esc_attr( $email_improvements_enabled ? $text : $base_text ); ?>;
	border-bottom: 0;
	font-weight: bold;
	line-height: 100%;
	vertical-align: middle;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
}

#template_header h1,
#template_header h1 a {
	color: <?php echo esc_attr( $email_improvements_enabled ? $text : $base_text ); ?>;
	background-color: inherit;
}

<?php if ( $email_improvements_enabled ) : ?>
.hr {
	border-bottom: 1px solid #1e1e1e;
	opacity: 0.2;
	margin: 16px 0;
}

.hr-top {
	margin-top: 32px;
}

.hr-bottom {
	margin-bottom: 32px;
}

#template_header_image {
	padding: 32px 32px 0;
	text-align: center;
	background-color: #ffffff !important;
}

#template_header_image p {
	margin-bottom: 0;
	text-align: center;
	background-color: #ffffff !important;
}

#template_header_image a {
	background-color: #ffffff !important;
}

#template_header_image img {
	width: <?php echo esc_attr( $logo_image_width ); ?>px;
	background-color: #ffffff !important;
	display: block;
	margin: 0 auto;
}

.email-logo-text {
	color: #E199A4;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 18px;
	font-weight: 700;
	margin-left: auto;
	margin-right: auto;
	text-align: center;
}

.email-introduction {
	padding-bottom: 24px;
}

.email-order-item-meta {
	color: <?php echo esc_attr( $footer_text ); ?>;
	font-size: 14px;
	line-height: 140%;
}

#body_content table td td.email-additional-content {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	padding: 32px 0 0;
}

.email-additional-content p {
	text-align: center;
}

.email-additional-content-aligned p {
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

<?php else : ?>

#template_header_image img {
	margin-left: 0;
	margin-right: 0;
}
<?php endif; ?>

#template_footer td {
	padding: 0;
	border-radius: <?php echo $email_improvements_enabled ? '0' : '6px'; ?>;
}

#template_footer #credit {
	border: 0;
	<?php if ( $email_improvements_enabled ) : ?>
		border-top: 1px solid <?php echo esc_attr( $border_color ); ?>;
	<?php endif; ?>
	color: <?php echo esc_attr( $footer_text ); ?>;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 12px;
	line-height: <?php echo $email_improvements_enabled ? '140%' : '150%'; ?>;
	text-align: center;
	padding: <?php echo $email_improvements_enabled ? '32px' : '24px 0'; ?>;
}

#template_footer #credit p {
	margin: <?php echo $email_improvements_enabled ? '0' : '0 0 16px'; ?>;
}

#body_content {
	background-color: <?php echo esc_attr( $body ); ?>;
}

#body_content table td {
	padding: <?php echo $email_improvements_enabled ? '20px 32px 32px' : '48px 48px 32px'; ?>;
}

#body_content table td td {
	padding: 12px;
}

#body_content table td th {
	padding: 12px;
}

#body_content table .email-order-details td,
#body_content table .email-order-details th {
	padding: 8px 12px;
}

#body_content table .email-order-details td:first-child,
#body_content table .email-order-details th:first-child {
	padding-<?php echo is_rtl() ? 'right' : 'left'; ?>: 0;
}

#body_content table .email-order-details td:last-child,
#body_content table .email-order-details th:last-child {
	padding-<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
}

#body_content .email-order-details tbody tr:last-child td {
	border-bottom: 1px solid <?php echo esc_attr( $border_color ); ?>;
	padding-bottom: 24px;
}

#body_content .email-order-details tfoot tr:first-child td,
#body_content .email-order-details tfoot tr:first-child th {
	padding-top: 24px;
}

#body_content .order-item-data td {
	border: 0 !important;
	padding: 0 !important;
	vertical-align: top;
}

#body_content .email-order-details .order-totals td,
#body_content .email-order-details .order-totals th {
	font-weight: normal;
	padding-bottom: 5px;
	padding-top: 5px;
}

#body_content .email-order-details .order-totals .includes_tax {
	display: block;
}

#body_content .email-order-details .order-totals-total th {
	font-weight: bold;
}

#body_content .email-order-details .order-totals-total td {
	font-weight: bold;
	font-size: 20px;
}

#body_content .email-order-details .order-totals-last td,
#body_content .email-order-details .order-totals-last th {
	border-bottom: 1px solid <?php echo esc_attr( $border_color ); ?>;
	padding-bottom: 24px;
}

#body_content .email-order-details .order-customer-note td {
	border-bottom: 1px solid <?php echo esc_attr( $border_color ); ?>;
	padding-bottom: 24px;
	padding-top: 24px;
}

#body_content td ul.wc-item-meta {
	font-size: small;
	margin: 1em 0 0;
	padding: 0;
	list-style: none;
}

#body_content td ul.wc-item-meta li {
	margin: 0.5em 0 0;
	padding: 0;
}

#body_content td ul.wc-item-meta li p {
	margin: 0;
}

#body_content .email-order-details .wc-item-meta-label {
	clear: both;
	float: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	font-weight: normal;
	margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: .25em;
}

#body_content p {
	margin: 0 0 16px;
}

#body_content_inner {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: <?php echo $email_improvements_enabled ? '16px' : '14px'; ?>;
	line-height: 150%;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.td {
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	border: <?php echo $email_improvements_enabled ? '0' : '1px solid ' . esc_attr( $body_darker_10 ); ?>;
	vertical-align: middle;
}

.address {
	<?php if ( $email_improvements_enabled ) { ?>
		color: <?php echo esc_attr( $text ); ?>;
		font-style: normal;
		line-height: 120%;
		padding: 8px 0;
	<?php } else { ?>
		padding: 12px;
		color: <?php echo esc_attr( $text_lighter_20 ); ?>;
		border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
	<?php } ?>
	word-break: break-all;
}

<?php if ( $email_improvements_enabled ) : ?>
#addresses td + td {
	padding-<?php echo is_rtl() ? 'right' : 'left'; ?>: 10px !important;
}
<?php endif; ?>

.additional-fields {
	padding: 12px 12px 0;
	color: <?php echo esc_attr( $text_lighter_20 ); ?>;
	border: 1px solid <?php echo esc_attr( $body_darker_10 ); ?>;
	list-style: none outside;
}

.additional-fields li {
	margin: 0 0 12px 0;
}

.text,
.address-title,
.order-item-data {
	color: <?php echo esc_attr( $text ); ?>;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
}

.link {
	color: <?php echo esc_attr( $link_color ); ?>;
}

#header_wrapper {
	padding: <?php echo $email_improvements_enabled ? '20px 32px 0' : '36px 48px'; ?>;
	display: block;
	text-align: center;
}

<?php if ( $header_alignment ) : ?>
#header_wrapper h1 {
	text-align: center;
}
<?php endif; ?>

#template_footer #credit,
#template_footer #credit a {
	color: #E199A4;
}

h1 {
	color: <?php echo esc_attr( $email_improvements_enabled ? $text : $base ); ?>;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: <?php echo $email_improvements_enabled ? '32px' : '30px'; ?>;
	font-weight: <?php echo $email_improvements_enabled ? 700 : 300; ?>;
	<?php if ( $email_improvements_enabled ) : ?>
		letter-spacing: -1px;
	<?php endif; ?>
	line-height: <?php echo $email_improvements_enabled ? '120%' : '150%'; ?>;
	margin: 0;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
	<?php if ( ! $email_improvements_enabled ) : ?>
		text-shadow: 0 1px 0 <?php echo esc_attr( $base_lighter_20 ); ?>;
	<?php endif; ?>
}

h2 {
	color: <?php echo esc_attr( $email_improvements_enabled ? $text : $base ); ?>;
	display: block;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: <?php echo $email_improvements_enabled ? '20px' : '18px'; ?>;
	font-weight: bold;
	line-height: <?php echo $email_improvements_enabled ? '160%' : '130%'; ?>;
	margin: 0 0 18px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

h3 {
	color: <?php echo esc_attr( $email_improvements_enabled ? $text : $base ); ?>;
	display: block;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 16px;
	font-weight: bold;
	line-height: <?php echo $email_improvements_enabled ? '160%' : '130%'; ?>;
	margin: 16px 0 8px;
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

a {
	color: <?php echo esc_attr( $link_color ); ?>;
	font-weight: normal;
	text-decoration: underline;
}

img {
	border: none;
	display: inline-block;
	font-size: 14px;
	font-weight: bold;
	height: auto;
	outline: none;
	text-decoration: none;
	text-transform: capitalize;
	vertical-align: <?php echo $block_email_editor_enabled ? 'top' : 'middle'; ?>;
	margin-<?php echo is_rtl() ? 'left' : 'right'; ?>: <?php echo $email_improvements_enabled ? '24px' : '10px'; ?>;
	max-width: 100%;
}

h2.email-order-detail-heading span {
	color: <?php echo esc_attr( $footer_text ); ?>;
	display: block;
	font-size: 14px;
	font-weight: normal;
}

h2.email-order-detail-heading span a {
	text-decoration: none;
}

.font-family {
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
}

.noyona-email-tagline {
	color: #b6a6a8;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 9px;
	font-weight: 700;
	letter-spacing: 3px;
	line-height: 140%;
	margin: 4px 0 0;
	text-align: center;
}

.noyona-email-card {
	margin: 0 auto;
	text-align: center;
}

.noyona-email-card td {
	padding: 0 !important;
}

.noyona-email-title {
	color: #2d2d2d;
	font-family: <?php echo $safe_font_family; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
	font-size: 30px;
	font-weight: 800;
	letter-spacing: -0.6px;
	line-height: 115%;
	margin: 8px 0 14px;
	text-align: center;
	text-shadow: none;
}

.noyona-email-greeting {
	color: #5a5355;
	font-size: 16px;
	font-weight: 700;
	line-height: 150%;
	margin: 0 0 8px;
	text-align: center;
}

.noyona-email-lede,
.noyona-email-copy {
	color: #8a7f82;
	font-size: 15px;
	line-height: 150%;
	margin: 0 auto 16px;
	max-width: 480px;
	text-align: center;
}

.noyona-email-benefits {
	color: #8a7f82;
	font-size: 15px;
	line-height: 170%;
	list-style: disc;
	margin: 0 auto 18px;
	max-width: 320px;
	padding: 0 0 0 22px;
	text-align: left;
}

.noyona-email-benefits li {
	margin: 0 0 4px;
}

.noyona-email-button-wrap {
	margin: 20px auto 22px;
}

.noyona-email-button {
	background: #E199A4;
	border-radius: 999px;
	color: #ffffff !important;
	display: inline-block;
	font-size: 15px;
	font-weight: 700;
	line-height: 1;
	padding: 16px 42px;
	text-align: center;
	text-decoration: none;
}

.noyona-email-pill {
	background: #fbf3f5;
	border-radius: 9px;
	color: #8f8588;
	display: inline-block;
	font-size: 10px;
	font-weight: 700;
	letter-spacing: 1.4px;
	line-height: 1;
	margin: 0 auto;
	padding: 15px 34px;
	text-align: center;
}

.noyona-email-small {
	font-size: 12px;
	margin: 16px 0 0;
	text-align: center;
}

.noyona-email-store-link {
	font-size: 13px;
	font-weight: 700;
	margin: -6px 0 30px;
	text-align: center;
}

.noyona-email-store-link a {
	color: #E199A4 !important;
	text-decoration: none;
}

.noyona-email-security-note {
	color: #a79b9e;
	font-size: 13px;
	line-height: 150%;
	margin: 0 auto 4px;
	max-width: 470px;
	text-align: center;
}

.noyona-order-email {
	text-align: left;
}

.noyona-order-kicker {
	color: #b6a6a8;
	font-size: 10px;
	font-weight: 800;
	letter-spacing: 1.8px;
	line-height: 140%;
	margin: 0 0 6px;
	text-align: left;
	text-transform: uppercase;
}

.noyona-order-title {
	margin-bottom: 12px;
	text-align: left;
}

.noyona-order-copy {
	margin-left: 0;
	margin-right: 0;
	max-width: 520px;
	text-align: left;
}

.noyona-order-actions {
	margin: 20px 0 30px;
}

.noyona-order-actions td {
	padding: 0 14px 0 0 !important;
	vertical-align: middle;
}

.noyona-order-store-link {
	color: #E199A4 !important;
	font-size: 12px;
	font-weight: 700;
	text-decoration: none;
	white-space: nowrap;
}

.noyona-email-section-title {
	color: #2d2d2d;
	font-size: 10px;
	font-weight: 800;
	letter-spacing: 1.8px;
	line-height: 140%;
	margin: 0 0 14px;
	text-align: left;
}

.noyona-delivery-card,
.noyona-order-help {
	background: #fbf0f3;
	border-radius: 7px;
	margin: 0 0 24px;
	padding: 18px !important;
}

.noyona-delivery-card table {
	border-collapse: collapse;
	width: 100%;
}

.noyona-delivery-card th,
.noyona-delivery-card td {
	color: #7f7477;
	font-size: 11px;
	line-height: 150%;
	padding: 4px 0 !important;
	text-align: left;
	vertical-align: top;
}

.noyona-delivery-card th {
	color: #2d2d2d;
	font-weight: 800;
	width: 82px;
}

.noyona-delivery-estimate {
	color: #2d2d2d;
	font-size: 11px;
	font-weight: 700;
	margin: -8px 0 12px;
	text-align: left;
}

.noyona-order-summary {
	border-collapse: collapse;
	margin: 0 0 18px;
	width: 100%;
}

.noyona-order-summary td {
	border-bottom: 1px solid #f0e5e8;
	padding: 12px 0 !important;
	vertical-align: middle;
}

.noyona-order-item-icon {
	background: #fbf0f3;
	border-radius: 6px;
	color: #d45f7f;
	font-size: 12px;
	font-weight: 800;
	height: 36px;
	text-align: center;
	width: 36px;
}

.noyona-order-item-name {
	color: #50474a;
	font-size: 13px;
	font-weight: 700;
	line-height: 140%;
	padding-left: 14px !important;
	text-align: left;
}

.noyona-order-item-name span {
	color: #d45f7f;
	font-weight: 700;
}

.noyona-order-item-total {
	color: #2d2d2d;
	font-size: 12px;
	font-weight: 800;
	text-align: right;
	white-space: nowrap;
}

.noyona-order-totals {
	border-collapse: collapse;
	margin: 0 0 28px;
	width: 100%;
}

.noyona-order-totals th,
.noyona-order-totals td {
	color: #8a7f82;
	font-size: 12px;
	font-weight: 600;
	line-height: 150%;
	padding: 4px 0 !important;
}

.noyona-order-totals th {
	text-align: left;
}

.noyona-order-totals td {
	text-align: right;
}

.noyona-order-total-row th,
.noyona-order-total-row td {
	color: #2d2d2d;
	font-weight: 800;
}

.noyona-order-total-row td {
	color: #a9185f;
}

.noyona-customer-info-title {
	margin-top: 12px;
}

.noyona-customer-info {
	border-collapse: collapse;
	width: 100%;
}

.noyona-customer-info td {
	color: #8a7f82;
	font-size: 12px;
	line-height: 145%;
	padding: 0 12px 18px 0 !important;
	text-align: left;
	vertical-align: top;
	width: 50%;
}

.noyona-customer-info h3 {
	color: #2d2d2d;
	font-size: 12px;
	font-weight: 800;
	line-height: 140%;
	margin: 0 0 6px;
	text-align: left;
}

.noyona-customer-info p {
	margin: 0 0 4px !important;
	text-align: left;
}

.noyona-shipping-method {
	padding-top: 4px !important;
	width: 100% !important;
}

.noyona-order-help {
	margin-top: 26px;
}

.noyona-order-help p {
	color: #a9185f;
	font-size: 12px;
	line-height: 150%;
	margin: 0 0 7px !important;
	text-align: left;
}

.noyona-delivered-email {
	text-align: center;
}

.noyona-delivered-check {
	background: #9a9b7d;
	border-radius: 999px;
	color: #ffffff;
	display: inline-block;
	font-size: 28px;
	font-weight: 800;
	height: 58px;
	line-height: 58px;
	margin: 2px auto 14px;
	text-align: center;
	width: 58px;
}

.noyona-delivered-status {
	background: #ecece7;
	border-radius: 999px;
	color: #8d8d7a;
	display: inline-block;
	font-size: 10px;
	font-weight: 800;
	letter-spacing: 1.8px;
	line-height: 1;
	margin: 0 auto 18px;
	padding: 9px 18px;
	text-align: center;
}

.noyona-delivered-title,
.noyona-delivered-copy {
	text-align: center;
}

.noyona-delivered-copy {
	margin-left: auto;
	margin-right: auto;
	max-width: 520px;
}

.noyona-delivered-actions {
	margin-left: auto;
	margin-right: auto;
}

.noyona-delivered-actions td {
	padding: 0 8px !important;
	text-align: center;
}

.noyona-delivered-details {
	border-collapse: collapse;
	margin: 0 0 22px;
	width: 100%;
}

.noyona-delivered-details th,
.noyona-delivered-details td {
	color: #8a7f82;
	font-size: 13px;
	line-height: 150%;
	padding: 5px 0 !important;
	text-align: left;
}

.noyona-delivered-details th {
	color: #4b4346;
	font-weight: 800;
	width: 120px;
}

.noyona-whats-next {
	background: #fbf0f3;
	border-radius: 7px;
	margin: 28px 0 28px;
	padding: 22px !important;
	text-align: left;
}

.noyona-whats-next p {
	color: #8a7f82;
	font-size: 13px;
	line-height: 160%;
	margin: 0 0 14px !important;
	text-align: left;
}

.noyona-whats-next-link a {
	color: #E199A4 !important;
	font-weight: 800;
	text-decoration: none;
}

.noyona-payment-failed-email {
	text-align: center;
}

.noyona-failed-icon {
	background: #bd6254;
	border-radius: 999px;
	color: #ffffff;
	display: inline-block;
	font-size: 34px;
	font-weight: 900;
	height: 70px;
	line-height: 70px;
	margin: 8px auto 24px;
	text-align: center;
	width: 70px;
}

.noyona-failed-title {
	font-size: 34px;
	margin-bottom: 20px;
	text-align: center;
}

.noyona-failed-copy {
	font-size: 17px;
	line-height: 155%;
	margin-left: auto;
	margin-right: auto;
	max-width: 560px;
	text-align: center;
}

.noyona-failed-actions {
	margin: 30px auto 28px;
}

.noyona-failed-support {
	color: #8a7f82;
	font-size: 14px;
	font-weight: 700;
	line-height: 150%;
	margin: 0 0 34px;
	text-align: center;
}

.noyona-failed-support a {
	color: #E199A4 !important;
	font-weight: 800;
	text-decoration: none;
}

.noyona-failed-ref {
	color: #b0a4a7;
	font-size: 12px;
	line-height: 150%;
	margin: 0 0 12px;
	text-align: center;
}

.noyona-cancelled-email {
	text-align: center;
}

.noyona-cancelled-status {
	background: #f1e8e5;
	border-radius: 999px;
	color: #8f8588;
	display: inline-block;
	font-size: 10px;
	font-weight: 800;
	letter-spacing: 1.8px;
	line-height: 1;
	margin: 0 auto 18px;
	padding: 10px 20px;
	text-align: center;
}

.noyona-cancelled-title,
.noyona-cancelled-copy {
	text-align: center;
}

.noyona-cancelled-title {
	margin-bottom: 18px;
}

.noyona-cancelled-copy {
	margin-left: auto;
	margin-right: auto;
	max-width: 560px;
}

.noyona-cancelled-refund-note {
	color: #4b4346;
	font-size: 14px;
	font-weight: 700;
	line-height: 150%;
	margin: 12px auto 36px;
	max-width: 560px;
	text-align: center;
}

.noyona-cancelled-actions {
	margin-left: auto;
	margin-right: auto;
}

.noyona-cancelled-actions td {
	padding: 0 8px !important;
	text-align: center;
}

.noyona-cancelled-help {
	margin-top: 28px;
}

.noyona-email-footer {
	border-top: 1px solid #eee4e7;
	padding-top: 24px;
}

.noyona-email-footer-brand {
	color: #d45f7f;
	font-size: 18px;
	font-weight: 800;
	letter-spacing: 1px;
	line-height: 1;
	margin-bottom: 7px;
}

.noyona-email-footer-tagline {
	color: #b6a6a8;
	font-size: 9px;
	font-weight: 700;
	letter-spacing: 3px;
	line-height: 140%;
	margin-bottom: 18px;
}

.noyona-email-footer p {
	color: #b0a4a7;
	font-size: 11px;
	line-height: 150%;
	margin: 0 0 10px !important;
	text-align: center;
}

.noyona-email-footer a {
	color: #E199A4 !important;
	text-decoration: none;
}

.noyona-email-footer-links {
	color: #E199A4 !important;
	font-size: 12px !important;
	font-weight: 600;
	margin-bottom: 24px !important;
}

.noyona-email-footer-links span {
	color: #cfc3c6;
	padding: 0 8px;
}

.noyona-email-footer-links--social {
	line-height: 170%;
	max-width: 500px;
}

.text-align-left {
	text-align: <?php echo is_rtl() ? 'right' : 'left'; ?>;
}

.text-align-right {
	text-align: <?php echo is_rtl() ? 'left' : 'right'; ?>;
}

/**
 * Noyona readability fix for the stock WooCommerce email templates.
 *
 * The default WooCommerce templates (order details, order items, addresses,
 * customer details, customer note, the admin order emails and the invoice /
 * "pay for order" email) colour their text from the admin-configurable e-mail
 * colour options ($base / $text / $text_lighter_20). When those options resolve
 * to a light/near-white value the text becomes invisible on the white e-mail
 * body. The custom noyona-* emails are unaffected because they hardcode their
 * colours, which left only these stock-markup emails broken.
 *
 * These rules pin that stock markup to the brand dark palette so it stays
 * readable regardless of the WooCommerce colour settings. They are declared
 * after the defaults (so they win on source order) and only use element/.class
 * selectors that the higher-specificity noyona-* classes still override, so the
 * custom email designs are not touched.
 */
#template_header {
	background-color: #ffffff;
}

#template_header h1,
#template_header h1 a {
	color: #2d2d2d;
}

#body_content_inner {
	color: #50474a;
}

h1,
h2,
h3 {
	color: #2d2d2d;
}

blockquote {
	color: #50474a;
}

.td,
.address,
.text,
.address-title,
.order-item-data,
.additional-fields {
	color: #50474a;
}

.email-order-item-meta,
h2.email-order-detail-heading span {
	color: #8a7f82;
}

#body_content table td td.email-additional-content,
.email-additional-content {
	color: #50474a;
}

/**
 * Media queries are not supported by all email clients, however they do work on modern mobile
 * Gmail clients and can help us achieve better consistency there.
 */
@media screen and (max-width: 600px) {
	<?php if ( $email_improvements_enabled ) : ?>
		#template_header_image {
			padding: 16px 10px 0 !important;
		}

		#header_wrapper {
			padding: 16px 10px 0 !important;
		}

		#header_wrapper h1 {
			font-size: 24px !important;
		}

		#body_content_inner_cell {
			padding: 10px !important;
		}

		#body_content_inner {
			font-size: 12px !important;
		}

		.email-order-item-meta {
			font-size: 12px !important;
		}

		#body_content .email-order-details .order-totals-total td {
			font-size: 14px !important;
		}

		.email-order-detail-heading {
			font-size: 16px !important;
			line-height: 130% !important;
		}

		.email-additional-content {
			padding-top: 16px !important;
		}
	<?php else : ?>
		#header_wrapper {
			padding: 27px 36px !important;
			font-size: 24px;
		}

		#body_content table > tbody > tr > td {
			padding: 10px !important;
		}

		#body_content_inner {
			font-size: 10px !important;
		}
	<?php endif; ?>
}
<?php
