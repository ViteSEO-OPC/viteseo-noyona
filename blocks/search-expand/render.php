<?php
/**
 * Search Expand Block Template.
 */
?>
<div class="wp-block-noyona-search-expand search-expand-block">
    <!-- Trigger Icon -->
    <button class="search-expand-trigger header-icon" type="button" aria-label="Open Search">
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>

    <!-- Expanded Overlay -->
    <div class="search-expand-overlay" aria-hidden="true">
        <div class="search-expand-container">
            <form role="search" method="get" class="search-expand-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                <div class="search-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass search-icon-input"></i>
                    <input type="search" 
                           class="search-expand-input" 
                           placeholder="Search..." 
                           value="<?php echo get_search_query(); ?>" 
                           name="s" 
                           autocomplete="off" />
                    <button type="button" class="search-expand-close" aria-label="Close Search">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </form>

            <!-- Live Suggestions -->
            <div class="search-expand-results">
                <div class="search-suggestions-group hidden">
                    <div class="suggestions-column suggestions-terms">
                        <h4>Suggestions</h4>
                        <ul class="suggestions-list"></ul>
                    </div>
                    <div class="suggestions-column suggestions-products">
                        <h4>Products</h4>
                        <ul class="suggestions-list"></ul>
                    </div>
                </div>
                <div class="no-results hidden">No results found.</div>
            </div>
            
             <div class="search-expand-footer">
                <a href="#" class="view-all-results hidden">View All Results</a>
                <span class="press-enter-hint">Press enter</span>
            </div>
        </div>
    </div>
</div>
