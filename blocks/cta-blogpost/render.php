<?php
/**
 * CTA Blogpost block template.
 *
 * @package viteseo-noyona
 */

$defaults = array(
	'text'      => 'Test',
	'url'       => '#',
	'position'  => 'left',
	'newTab'    => false,
	'showArrow' => false,
);

$atts = wp_parse_args( $attributes, $defaults );

$text      = trim( (string) $atts['text'] );
$url       = trim( (string) $atts['url'] );
$position  = sanitize_key( (string) $atts['position'] );
$new_tab   = ! empty( $atts['newTab'] );
$show_arrow= ! empty( $atts['showArrow'] );

if ( '' === $text ) {
	$text = 'Test';
}

if ( ! in_array( $position, array( 'left', 'center', 'right' ), true ) ) {
	$position = 'left';
}

if ( '' === $url ) {
	$url = '#';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'cta-blogpost cta-blogpost--' . $position,
	)
);
?>
<div <?php echo $wrapper_attributes; ?>>
	<a
		class="cta-blogpost__button"
		href="<?php echo esc_url( $url ); ?>"
		<?php echo $new_tab ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>
	>
		<span class="cta-blogpost__text"><?php echo esc_html( $text ); ?></span>
		<?php if ( $show_arrow ) : ?>
			<span class="cta-blogpost__arrow" aria-hidden="true">→</span>
		<?php endif; ?>
	</a>
</div>
