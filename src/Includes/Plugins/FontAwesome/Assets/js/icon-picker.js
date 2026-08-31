/**
 * ModPress FontAwesome Icon Picker JavaScript
 *
 * @package ModPress
 * @subpackage FontAwesome
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * FontAwesome Icon Picker
     */
    var ModPressFAIconPicker = {

        /**
         * Initialize the icon picker
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Open picker modal
            $(document).on('click', '.modpress-fa-picker-button', function(e) {
                e.preventDefault();
                var pickerId = $(this).data('picker-id');
                self.openPicker(pickerId);
            });

            // Close picker modal
            $(document).on('click', '.modpress-fa-picker-close, .modpress-fa-picker-overlay, .modpress-fa-picker-cancel', function(e) {
                e.preventDefault();
                self.closePicker();
            });

            // Search icons
            $(document).on('input', '.modpress-fa-picker-search-input', function() {
                self.searchIcons();
            });

            // Filter by pack
            $(document).on('change', '.modpress-fa-picker-pack-filter', function() {
                var pack = $(this).val();
                var $container = $(this).closest('.modpress-fa-picker-modal');
                var $styleFilter = $container.find('.modpress-fa-picker-style-filter');

                // Handle brands pack - it only has one style
                if (pack === 'brands') {
                    $styleFilter.prop('disabled', true).hide();
                    // Set style to solid for brands
                    $styleFilter.val('solid');
                } else {
                    $styleFilter.prop('disabled', false).show();
                }

                self.searchIcons();
            });

            // Filter by style
            $(document).on('change', '.modpress-fa-picker-style-filter', function() {
                self.searchIcons();
            });

            // Select icon
            $(document).on('click', '.modpress-fa-picker-icon-item', function() {
                self.selectIcon($(this));
                self.confirmSelection();
            });

            // Confirm selection
            $(document).on('click', '.modpress-fa-picker-select', function() {
                self.confirmSelection();
            });

            // Load more icons (pagination)
            $(document).on('click', '.modpress-fa-picker-load-more', function() {
                self.loadMoreIcons();
            });
        },

        /**
         * Open the icon picker modal
         *
         * @param {string} pickerId The picker container ID
         */
        openPicker: function(pickerId) {
            this.currentPickerId = pickerId;
            this.currentPage = 1;
            this.selectedIcon = null;

            var $container = $('#' + pickerId);
            var $modal = $container.find('.modpress-fa-picker-modal');

            // Show modal
            $modal.show();

            // Handle initial pack selection
            var $packFilter = $modal.find('.modpress-fa-picker-pack-filter');
            var $styleFilter = $modal.find('.modpress-fa-picker-style-filter');
            var pack = $packFilter.val();

            if (pack === 'brands') {
                $styleFilter.prop('disabled', true).hide();
            } else {
                $styleFilter.prop('disabled', false).show();
            }

            // Load initial icons
            this.searchIcons();
        },

        /**
         * Close the icon picker modal
         */
        closePicker: function() {
            $('.modpress-fa-picker-modal').hide();
            this.currentPickerId = null;
            this.selectedIcon = null;
        },

        /**
         * Search for icons
         */
        searchIcons: function() {
            var self = this;
            var $container = $('#' + this.currentPickerId);
            var $searchInput = $container.find('.modpress-fa-picker-search-input');
            var $packFilter = $container.find('.modpress-fa-picker-pack-filter');
            var $styleFilter = $container.find('.modpress-fa-picker-style-filter');
            var $results = $container.find('.modpress-fa-picker-results');

            var search = $searchInput.val();
            var pack = $packFilter.val();
            var style = $styleFilter.val();

            // Show loading
            $results.html('<div class="modpress-fa-picker-loading">' + modpress_fa_picker.strings.loading + '</div>');

            // AJAX request
            $.ajax({
                url: modpress_fa_picker.ajax_url,
                type: 'POST',
                data: {
                    action: 'modpress_fontawesome_search_icons',
                    nonce: modpress_fa_picker.nonce,
                    search: search,
                    pack: pack,
                    style: style,
                    page: this.currentPage,
                    per_page: 50
                },
                success: function(response) {
                    if (response.success) {
                        self.displayIcons(response.data, pack, style);
                    } else {
                        $results.html('<div class="modpress-fa-picker-error">' + response.data + '</div>');
                    }
                },
                error: function() {
                    $results.html('<div class="modpress-fa-picker-error">' + modpress_fa_picker.strings.no_icons_found + '</div>');
                }
            });
        },

        /**
         * Display icons in the results area
         *
         * @param {object} data Icon data from AJAX response
         * @param {string} pack Selected pack
         * @param {string} style Selected style
         */
        displayIcons: function(data, pack, style) {
            var self = this;
            var $container = $('#' + this.currentPickerId);
            var $results = $container.find('.modpress-fa-picker-results');

            if (!data.icons || data.icons.length === 0) {
                $results.html('<div class="modpress-fa-picker-no-results">' + modpress_fa_picker.strings.no_icons_found + '</div>');
                return;
            }

            var html = '<div class="modpress-fa-picker-grid">';

            data.icons.forEach(function(icon) {
                var iconClass = data.prefix + ' fa-' + icon.name;

                html += '<div class="modpress-fa-picker-icon-item" data-icon-name="' + icon.name + '" data-icon-pack="' + pack + '" data-icon-style="' + style + '" data-icon-prefix="' + data.prefix + '">';
                html += '<i class="' + iconClass + '"></i>';
                html += '<span class="modpress-fa-picker-icon-label">' + icon.label + '</span>';
                html += '</div>';
            });

            html += '</div>';

            // Add pagination if needed
            if (data.total_pages > 1 && data.page < data.total_pages) {
                html += '<div class="modpress-fa-picker-pagination">';
                html += '<button class="btn btn-link modpress-fa-picker-load-more" data-next-page="' + (data.page + 1) + '">' + 'Load More' + '</button>';
                html += '</div>';
            }

            $results.html(html);
        },

        /**
         * Select an icon
         *
         * @param {jQuery} $iconItem The clicked icon item
         */
        selectIcon: function($iconItem) {
            var $container = $('#' + this.currentPickerId);

            // Remove previous selection
            $container.find('.modpress-fa-picker-icon-item').removeClass('selected');

            // Add selection to clicked item
            $iconItem.addClass('selected');

            // Store selected icon data
            this.selectedIcon = {
                name: $iconItem.data('icon-name'),
                pack: $iconItem.data('icon-pack'),
                style: $iconItem.data('icon-style'),
                prefix: $iconItem.data('icon-prefix'),
                class: $iconItem.data('icon-prefix') + ' fa-' + $iconItem.data('icon-name')
            };

            // Enable select button
            $container.find('.modpress-fa-picker-select').prop('disabled', false);
        },

        /**
         * Confirm the icon selection
         */
        confirmSelection: function() {
            if (!this.selectedIcon) {
                return;
            }

            var $container = $('#' + this.currentPickerId);
            var $input = $container.find('.modpress-fa-picker-input');
            var $preview = $container.find('.modpress-fa-picker-preview');

            // Update hidden input
            $input.val(this.selectedIcon.class).trigger('change');

            // Update preview
            $preview.html('<i class="' + this.selectedIcon.class + '"></i>');

            // Directly update mod manager fields if present
            if (jQuery('#taxonomy-icon-class').length) {
                jQuery('#taxonomy-icon-class').val(this.selectedIcon.class);
                jQuery('#taxonomy-icon-color').val('#333333');
                jQuery('#taxonomy-icon-preview-icon').attr('class', this.selectedIcon.class).css('color', '#333333');
                jQuery('#taxonomy-icon-preview').show();
            }

            // Trigger custom event for external listeners
            $container.trigger('icon-selected', [this.selectedIcon.class, '#333333']);

            // Close modal
            this.closePicker();
        },

        /**
         * Load more icons (pagination)
         */
        loadMoreIcons: function() {
            var $container = $('#' + this.currentPickerId);
            var $loadMoreBtn = $container.find('.modpress-fa-picker-load-more');

            this.currentPage = parseInt($loadMoreBtn.data('next-page'));

            // Remove load more button temporarily
            $loadMoreBtn.remove();

            // Search with new page
            this.searchIcons();
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        ModPressFAIconPicker.init();
    });

    // Expose to global scope for potential external use
    window.ModPressFAIconPicker = ModPressFAIconPicker;

})(jQuery);