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
               placeholder="What's your looking for?"
               value="<?php echo get_search_query(); ?>"
               name="s"
               autocomplete="off" />
        <input type="hidden" name="post_type" value="product" />
        <button type="submit" class="search-inline-submit header-icon" aria-label="Search">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
        </button>
    </form>
</div>
