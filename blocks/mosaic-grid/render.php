<?php
/**
 * Mosaic Grid block render.
 *
 * @param array $attributes Block attributes.
 */

$tile_order = array( 'tl', 'tb', 'tg', 'tr', 'll', 'cc', 'rr', 'bb', 'bg' );
$tiles_by_id = array();

if ( isset( $attributes['tiles'] ) && is_array( $attributes['tiles'] ) ) {
    foreach ( $attributes['tiles'] as $tile ) {
        if ( empty( $tile['id'] ) ) {
            continue;
        }
        $tiles_by_id[ $tile['id'] ] = array(
            'heading' => isset( $tile['heading'] ) ? $tile['heading'] : '',
            'body'    => isset( $tile['body'] ) ? $tile['body'] : '',
        );
    }
}
?>
<section class="wp-block-noyona-mosaic-grid mosaic-grid alignwide">
    <div class="mosaic-grid__grid">
        <?php foreach ( $tile_order as $tile_id ) : ?>
            <?php
            $content = isset( $tiles_by_id[ $tile_id ] ) ? $tiles_by_id[ $tile_id ] : array(
                'heading' => '',
                'body'    => '',
            );
            ?>
            <div class="mosaic-grid__tile mosaic-grid__tile--<?php echo esc_attr( $tile_id ); ?>">
                <?php if ( '' !== $content['heading'] ) : ?>
                    <h3 class="mosaic-grid__heading">
                        <?php echo wp_kses_post( $content['heading'] ); ?>
                    </h3>
                <?php endif; ?>
                <?php if ( '' !== $content['body'] ) : ?>
                    <p class="mosaic-grid__body">
                        <?php echo wp_kses_post( $content['body'] ); ?>
                    </p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>
