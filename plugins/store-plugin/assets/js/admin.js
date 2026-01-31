jQuery(document).ready(function ($) {

    // ============================================
    // MAP PICKER LOGIC
    // ============================================
    if ($('#store-map-picker').length) {
        var lat = $('#store_lat').val() || 14.5547;
        var lng = $('#store_lng').val() || 121.0244;

        var map = L.map('store-map-picker').setView([lat, lng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        // Update inputs on drag end
        marker.on('dragend', function (e) {
            var position = marker.getLatLng();
            $('#store_lat').val(position.lat);
            $('#store_lng').val(position.lng);
        });

        // Click on map to move marker
        map.on('click', function (e) {
            marker.setLatLng(e.latlng);
            $('#store_lat').val(e.latlng.lat);
            $('#store_lng').val(e.latlng.lng);
        });

        // Refresh map size if hidden initially (e.g. inside tabs or metaboxes)
        setTimeout(function () { map.invalidateSize(); }, 500);
    }

    // ============================================
    // REPEATER LOGIC
    // ============================================
    var productList = $('#store-products-list');
    var rowTemplate = $('#store-product-row-template').html();
    var productIndex = productList.children('tr').length; // simple indexer

    // Add Row
    $('#add-store-product').on('click', function (e) {
        e.preventDefault();
        var newRow = rowTemplate.replace(/\{\{INDEX\}\}/g, productIndex);
        productList.append(newRow);
        productIndex++;
    });

    // Remove Row
    productList.on('click', '.remove-store-product', function (e) {
        e.preventDefault();
        if (confirm('Are you sure you want to remove this product?')) {
            $(this).closest('tr').remove();
        }
    });

    // Sortable (requires jQuery UI Sortable enqueued by WP usually, but we check first)
    if ($.fn.sortable) {
        productList.sortable({
            handle: '.dashicons-menu',
            placeholder: 'ui-state-highlight'
        });
    }

    // ============================================
    // MEDIA UPLOADER LOGIC
    // ============================================
    var mediaUploader;

    productList.on('click', '.upload-product-image', function (e) {
        e.preventDefault();
        var button = $(this);
        var container = button.closest('.store-product-image-container');
        var urlInput = container.find('.store-product-image-url');
        var idInput = container.find('.store-product-image-id');

        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
        } else {
            // Extend the wp.media object
            mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Product Image',
                button: {
                    text: 'Choose Image'
                },
                multiple: false
            });
        }

        // When a file is selected, grab the URL and set it as the text field's value
        mediaUploader.off('select').on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            urlInput.val(attachment.url);
            idInput.val(attachment.id);

            // Preview
            if (container.find('img').length === 0) {
                container.prepend('<img src="' + attachment.url + '" style="max-width: 100px; height: auto; display: block; margin-bottom: 5px;">');
                if (container.find('.remove-product-image').length === 0) {
                    container.append('<button type="button" class="button button-small remove-product-image" style="color: #a00;">Remove</button>');
                }
            } else {
                container.find('img').attr('src', attachment.url);
            }
        });

        // Open the uploader dialog
        mediaUploader.open();
    });

    // Remove Image
    productList.on('click', '.remove-product-image', function (e) {
        e.preventDefault();
        var container = $(this).closest('.store-product-image-container');
        container.find('img').remove();
        container.find('.store-product-image-url').val('');
        container.find('.store-product-image-id').val('');
        $(this).remove();
    });

});
