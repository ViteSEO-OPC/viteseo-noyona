<?php
/**
 * Admin-area customisations.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ----- Rank Math admin column styles ----- */
add_action( 'admin_head', 'noyona_admin_rank_math_columns_css' );
function noyona_admin_rank_math_columns_css() {
    echo '<style>
        .wp-list-table .column-rank_math_seo_details,
        .wp-list-table .column-rank_math_title,
        .wp-list-table .column-rank_math_description,
        .wp-list-table .column-rank_math_focus_keyword {
            width: 180px !important;
            min-width: 180px !important;
            white-space: normal !important;
            word-break: normal !important;
        }
    </style>';
}

