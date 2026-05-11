<?php
/**
 * FAQ Blogpost block template.
 *
 * @package viteseo-noyona
 */

$defaults = array(
	'heading'   => 'Frequently Asked Questions',
	'subheading'=> 'Lorem ipsum dolor sit amet, consectetur adipiscing elit',
	'items'     => array(),
	'openFirst' => true,
);

$atts = wp_parse_args( $attributes, $defaults );

$heading   = is_string( $atts['heading'] ) ? $atts['heading'] : '';
$subheading= is_string( $atts['subheading'] ) ? $atts['subheading'] : '';
$open_first= ! empty( $atts['openFirst'] );
$items_raw = isset( $atts['items'] ) && is_array( $atts['items'] ) ? $atts['items'] : array();

$items = array_values(
	array_filter(
		array_map(
			static function ( $item ) {
				if ( ! is_array( $item ) ) {
					return null;
				}

				$question = isset( $item['question'] ) ? trim( (string) $item['question'] ) : '';
				$answer   = isset( $item['answer'] ) ? trim( (string) $item['answer'] ) : '';

				if ( '' === $question ) {
					return null;
				}

				return array(
					'question' => $question,
					'answer'   => $answer,
				);
			},
			$items_raw
		)
	)
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'faq-blogpost',
	)
);
?>
<section <?php echo $wrapper_attributes; ?> data-open-first="<?php echo $open_first ? '1' : '0'; ?>">
	<div class="faq-blogpost__inner">
		<?php if ( '' !== $heading ) : ?>
			<h2 class="faq-blogpost__heading"><?php echo esc_html( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( '' !== $subheading ) : ?>
			<p class="faq-blogpost__subheading"><?php echo esc_html( $subheading ); ?></p>
		<?php endif; ?>

		<?php if ( empty( $items ) ) : ?>
			<?php if ( is_admin() ) : ?>
				<div class="faq-blogpost__placeholder"><?php esc_html_e( 'Add FAQ items from the block settings.', 'noyona-childtheme' ); ?></div>
			<?php endif; ?>
		<?php else : ?>
			<div class="faq-blogpost__items">
				<?php foreach ( $items as $index => $item ) : ?>
					<details class="faq-blogpost__item"<?php echo ( 0 === $index && $open_first ) ? ' open' : ''; ?>>
						<summary class="faq-blogpost__summary">
							<span class="faq-blogpost__question"><?php echo esc_html( $item['question'] ); ?></span>
							<span class="faq-blogpost__toggle" aria-hidden="true"></span>
						</summary>
						<?php if ( '' !== $item['answer'] ) : ?>
							<div class="faq-blogpost__answer">
								<?php echo wp_kses_post( wpautop( $item['answer'] ) ); ?>
							</div>
						<?php endif; ?>
					</details>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</section>
