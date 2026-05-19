<?php
/**
 * Search Expand Block Template.
 */
?>
<div class="wp-block-noyona-search-expand search-expand-block">
    <form role="search" method="get" class="search-inline-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <label class="screen-reader-text" for="header-search-inline">Search products</label>
        <input type="search"
               id="header-search-inline"
               class="search-inline-input"
               placeholder="What are you looking for?"
               value="<?php echo get_search_query(); ?>"
               name="s"
               autocomplete="off"
               aria-autocomplete="list"
               aria-expanded="false"
               aria-controls="header-search-inline-suggestions" />
        <input type="hidden" name="post_type" value="product" />
        <button type="submit" class="search-inline-submit header-icon" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </button>
        <div class="search-inline-suggestions" id="header-search-inline-suggestions" data-search-suggestions hidden>
            <div class="search-inline-suggestions__status" data-search-status aria-live="polite"></div>
            <ul class="search-inline-suggestions__list" data-search-results role="listbox"></ul>
            <a class="search-inline-suggestions__view-all" href="/?post_type=product" data-search-view-all>
                <?php esc_html_e( 'View all results', 'noyona-childtheme' ); ?>
            </a>
        </div>
    </form>
</div>
