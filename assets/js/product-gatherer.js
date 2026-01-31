document.addEventListener('DOMContentLoaded', function () {
    // ----- Product-gatherer auto-submit -----
    var form = document.querySelector('.pg-toolbar-form');
    if (form) {
        var selects = form.querySelectorAll('select.pg-select');
        selects.forEach(function (select) {
            select.addEventListener('change', function () {
                form.submit();
            });
        });
    }

    // ----- Wishlist drawer toggle -----
    var wishlistToggle = document.querySelector('.header-wishlist-toggle');
    var wishlistPanel  = document.getElementById('wishlist-panel');

    if (wishlistToggle && wishlistPanel) {
        var overlay  = wishlistPanel.querySelector('.wishlist-panel__overlay');
        var closeBtn = wishlistPanel.querySelector('.wishlist-panel__close');

        function closeWishlist() {
            wishlistPanel.classList.remove('is-open');
        }

        wishlistToggle.addEventListener('click', function () {
            wishlistPanel.classList.toggle('is-open');
        });

        if (overlay) {
            overlay.addEventListener('click', closeWishlist);
        }
        if (closeBtn) {
            closeBtn.addEventListener('click', closeWishlist);
        }
    }
});
