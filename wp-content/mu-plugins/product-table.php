<?php
/**
 * Plugin Name: Custom Product Table
 * Plugin URI: https://printlana.com
 * Description: Displays a custom product table with image, price per piece, production time, and quantity controls for WooCommerce products with WAPF integration.
 * Version: 1.0.0
 * Author: Printlana
 * Author URI: https://printlana.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-product-table
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Custom_Product_Table
{

    /**
     * Plugin version
     */
    const VERSION = '1.0.0';

    /**
     * Instance of this class
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct()
    {
        add_action('init', array($this, 'init'));
        add_action('init', array($this, 'register_wpml_strings'));
        add_shortcode('product_table', array($this, 'render_product_table'));
    }

    /**
     * Initialize plugin
     */
    public function init()
    {
        // Load text domain for translations
        load_plugin_textdomain('custom-product-table', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Register strings with WPML String Translation
     */
    public function register_wpml_strings()
    {
        if (function_exists('icl_register_string')) {
            // Register banner strings
            icl_register_string('custom-product-table', 'Minimum Order', 'الحد الأدنى للطلب');
            icl_register_string('custom-product-table', 'Piece', 'قطعة');
            icl_register_string('custom-product-table', 'Price per piece decreases with increased order quantity', 'سعر القطعة ينخفض مع زيادة كمية الطلب');

            // Register table headers
            icl_register_string('custom-product-table', 'Product', 'Product');
            icl_register_string('custom-product-table', 'Price of Piece', 'Price of Piece');
            icl_register_string('custom-product-table', 'Time of Production', 'Time of Production');
            icl_register_string('custom-product-table', 'Quantity', 'Quantity');

            // Register button labels
            icl_register_string('custom-product-table', 'Decrease quantity', 'Decrease quantity');
            icl_register_string('custom-product-table', 'Increase quantity', 'Increase quantity');

            // Register JavaScript strings
            icl_register_string('custom-product-table', 'Loading...', 'Loading...');
            icl_register_string('custom-product-table', 'No image', 'No image');
            icl_register_string('custom-product-table', 'N/A', 'N/A');
        }
    }

    /**
     * Render product table shortcode
     */
    public function render_product_table($atts)
    {
        // Parse attributes
        $atts = shortcode_atts(array(
            'style' => 'default',
            'min_quantity' => '', // Optional: manually set minimum quantity
        ), $atts, 'product_table');

        // Start output buffering
        ob_start();

        // Include HTML
        $this->render_html();

        // Include JavaScript
        $this->render_scripts($atts);

        return ob_get_clean();
    }

    /**
     * Render HTML structure
     */
    private function render_html()
    {
        ?>
        <div class="product-banner-min-quantity">
            <div class="product-icon"></div>
            <div class="banner-info">
                <?php esc_html_e('Minimum Order', 'custom-product-table'); ?>
                <div class="banner-quantity" id="banner-min-qty">
                    --
                </div>
                <?php esc_html_e('Piece', 'custom-product-table'); ?>
            </div>
        </div>
        <div class="product-banner-quantity-info">
            <div class="product-icon"></div>
            <div class="banner-info">
                <?php esc_html_e('Price per piece decreases with increased order quantity', 'custom-product-table'); ?>
            </div>
        </div>
        <table class="acf-product-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Product', 'custom-product-table'); ?></th>
                    <th><?php esc_html_e('Price of Piece', 'custom-product-table'); ?></th>
                    <th><?php esc_html_e('Time of Production', 'custom-product-table'); ?></th>
                    <th><?php esc_html_e('Quantity', 'custom-product-table'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="acf-product-image">
                        <div id="acf-selected-img">
                            <span><?php esc_html_e('Loading...', 'custom-product-table'); ?></span>
                        </div>
                    </td>
                    <td>
                        <span id="acf-price-piece">--</span>
                    </td>
                    <td>
                        <span id="acf-prod-time">N/A</span>
                    </td>
                    <td>
                        <div class="acf-qty-controls">
                            <button type="button" class="acf-qty-minus"
                                aria-label="<?php esc_attr_e('Decrease quantity', 'custom-product-table'); ?>">−</button>
                            <input id="acf-qty-val" type="number" min="1" value="1">
                            <button type="button" class="acf-qty-plus"
                                aria-label="<?php esc_attr_e('Increase quantity', 'custom-product-table'); ?>">+</button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
    }

    /**
     * Render JavaScript
     */
    private function render_scripts($atts = array())
    {
        // Localize script strings for WPML
        $i18n_strings = array(
            'loading' => function_exists('icl_t') ? icl_t('custom-product-table', 'Loading...', __('Loading...', 'custom-product-table')) : __('Loading...', 'custom-product-table'),
            'no_image' => function_exists('icl_t') ? icl_t('custom-product-table', 'No image', __('No image', 'custom-product-table')) : __('No image', 'custom-product-table'),
            'na' => function_exists('icl_t') ? icl_t('custom-product-table', 'N/A', __('N/A', 'custom-product-table')) : __('N/A', 'custom-product-table'),
        );

        // Pass configuration to JavaScript
        $config = array(
            'minQuantity' => !empty($atts['min_quantity']) ? intval($atts['min_quantity']) : 0,
        );
        ?>
        <script>
            var cptI18n = <?php echo wp_json_encode($i18n_strings); ?>;
            var cptConfig = <?php echo wp_json_encode($config); ?>;

            (function ($) {
                'use strict';

                var CustomProductTable = {

                    init: function () {
                        var self = this;
                        this.updateSelectedImage();
                        this.updateProductionTime();
                        this.updateQtyDisplay();
                        this.bindEvents();
                        this.watchPriceChanges();

                        // Wait for min quantity element to be ready, then update with delay
                        this.waitForMinQtyElement(function() {
                            self.updateMinQuantity();
                        });
                    },

                    waitForMinQtyElement: function(callback) {
                        var $target = $('#banner-min-qty');

                        if ($target.length && $target.is(':visible')) {
                            // Element exists and is visible, call immediately with small delay
                            setTimeout(callback, 100);
                        } else {
                            // Wait for element to appear
                            var checkInterval = setInterval(function() {
                                $target = $('#banner-min-qty');
                                if ($target.length) {
                                    clearInterval(checkInterval);
                                    setTimeout(callback, 100);
                                }
                            }, 50);

                            // Fallback: stop checking after 5 seconds
                            setTimeout(function() {
                                clearInterval(checkInterval);
                                callback(); // Try anyway
                            }, 5000);
                        }
                    },

                    updateSelectedImage: function () {
                        var $selected = $('.fastCsutomization .wapf-swatch.wapf--selected img');

                        if (!$selected.length) {
                            $selected = $('.fastCsutomization input[type="radio"]:checked').closest('.wapf-swatch').find('img');
                        }

                        if (!$selected.length) {
                            $selected = $('.fastCsutomization img').first();
                        }

                        if ($selected.length) {
                            var imgSrc = $selected.attr('src');
                            var imgAlt = $selected.attr('alt') || 'Product';
                            $('#acf-selected-img').html('<img src="' + imgSrc + '" alt="' + imgAlt + '">');
                        } else {
                            $('#acf-selected-img').html('<span>' + cptI18n.no_image + '</span>');
                        }
                    },

                    updatePricePerPiece: function () {
                        var qty = parseInt($('.input-text.qty.text.wcmmq-qty-input-box').val()) || 1;
                        var totalPrice = 0;
                        console.log(qty)
                        console.log(totalPrice)

                        // Try to get price from the main product price element
                        var priceElement = document.querySelector('.woocommerce:where(body:not(.woocommerce-uses-block-theme)) div.product span.price');
                        console.log(priceElement)

                        if (priceElement) {
                            var priceText = priceElement.textContent.trim().replace(/,/g, '').replace(/[^0-9.]/g, '');
                            console.log(priceText)
                            totalPrice = parseFloat(priceText);
                        } else {
                            // Fallback to jQuery selectors
                            var $price = $('.wapf-total-price').first();
                            console.log($price)
                            if (!$price.length) {
                                $price = $('.woocommerce-Price-amount').first();
                                console.log($price)
                            }
                            if (!$price.length) {
                                $price = $('.price .amount').first();
                                console.log($price)
                            }

                            if ($price.length) {
                                var priceText = $price.text().replace(/,/g, '').replace(/[^0-9.]/g, '');
                                console.log(priceText)
                                totalPrice = parseFloat(priceText);
                                console.log(totalPrice)
                            }
                        }

                        if (totalPrice > 0 && qty > 0) {
                            var perPiece = (totalPrice / qty).toFixed(4);
                            var currency = this.getCurrency();
                            $('#acf-price-piece').text(perPiece + ' ' + currency);
                        } else {
                            $('#acf-price-piece').text('--');
                        }
                    },

                    getCurrency: function () {
                        var currency = 'EGP';
                        var $currencySymbol = $('.woocommerce-Price-currencySymbol').first();
                        if ($currencySymbol.length) {
                            currency = $currencySymbol.text();
                        }
                        return currency;
                    },

                    updateProductionTime: function () {
                        // Try multiple attribute selectors
                        var time = $('[data-attribute="production-time"]').text().trim() ||
                            $('[data-attribute="production_time"]').text().trim() ||
                            $('.production-time-attribute').text().trim() ||
                            $('.woocommerce-product-attributes-item--production-time .woocommerce-product-attributes-item__value').text().trim() ||
                            $('.woocommerce-product-attributes-item--production_time .woocommerce-product-attributes-item__value').text().trim();

                        $('#acf-prod-time').text(time || cptI18n.na);
                    },

                    updateMinQuantity: function () {
                        // Use class selector for quantity input (ID is variable)
                        var $qtyInput = $('.wcmmq-qty-input-box, input[name="quantity"]').first();
                        var minQty = 1;

                        console.log('=== Debug: Minimum Quantity ===');
                        console.log('Quantity input found:', $qtyInput.length > 0);
                        console.log('Config minQuantity:', cptConfig.minQuantity);

                        // Check if manually set via shortcode
                        if (cptConfig.minQuantity && cptConfig.minQuantity > 0) {
                            minQty = cptConfig.minQuantity;
                            console.log('Using shortcode min_quantity:', minQty);
                        } else if ($qtyInput.length) {
                            // Get min attribute from input
                            minQty = parseInt($qtyInput.attr('min')) || 1;
                            console.log('Using input min attribute:', minQty);
                        }

                        console.log('Final minQty:', minQty);

                        // Format number with commas for thousands
                        var formattedQty = minQty.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                        // Update the element
                        var $target = $('#banner-min-qty');
                        console.log('Target element exists:', $target.length > 0);
                        console.log('Target element visible:', $target.is(':visible'));

                        $target.text(formattedQty);

                        // Force display and verify
                        $target.css('display', '');

                        console.log('Set text to:', formattedQty);
                        console.log('Element text is now:', $target.text());
                        console.log('Element HTML:', $target[0] ? $target[0].outerHTML : 'not found');
                        console.log('=== End Debug ===');
                    },

                    updateQtyDisplay: function () {
                        var qty = parseInt($('input[name="quantity"]').val()) || 1;
                        $('#acf-qty-val').val(qty);
                        this.updatePricePerPiece();
                        this.updateButtonStates();
                    },

                    updateButtonStates: function () {
                        var $input = $('input[name="quantity"]');
                        var current = parseInt($input.val()) || 1;
                        var min = parseInt($input.attr('min')) || 1;
                        var max = parseInt($input.attr('max')) || 999999;

                        $('.acf-qty-minus').prop('disabled', current <= min);
                        $('.acf-qty-plus').prop('disabled', current >= max);
                    },

                    bindEvents: function () {
                        var self = this;

                        // Quantity buttons
                        $('.acf-qty-plus').on('click', function (e) {
                            e.preventDefault();
                            var $input = $('.input-text.qty.text.wcmmq-qty-input-box');
                            var current = parseInt($input.val()) || 1;
                            var max = parseInt($input.attr('max')) || 999999;
                            console.log($input)
                            if (current < max) {
                                console.log(max)
                                console.log(current)
                                $input.val(current + 1).trigger('change');
                                console.log(current)
                            }
                        });

                        $('.acf-qty-minus').on('click', function (e) {
                            e.preventDefault();
                            var $input = $('input[name="quantity"]');
                            var current = parseInt($input.val()) || 1;
                            var min = parseInt($input.attr('min')) || 1;
                            console.log($input)
                            if (current > min) {
                                console.log(max)
                                console.log(current)
                                $input.val(current - 1).trigger('change');
                                console.log(current)
                            }
                        });

                        // ACF quantity input manual change
                        $('#acf-qty-val').on('change input', function () {
                            var $qtyInput = $('input[name="quantity"]');
                            var newQty = parseInt($(this).val()) || 1;
                            var min = parseInt($qtyInput.attr('min')) || 1;
                            var max = parseInt($qtyInput.attr('max')) || 999999;

                            // Clamp value within min/max
                            newQty = Math.max(min, Math.min(max, newQty));

                            // Update both inputs
                            $(this).val(newQty);
                            $qtyInput.val(newQty).trigger('change');
                        });

                        // WooCommerce quantity change
                        $(document).on('change', 'input[name="quantity"]', function () {
                            self.updateQtyDisplay();
                        });

                        // WAPF field changes
                        $(document).on('change click', '.fastCsutomization input, .fastCsutomization select, .fastCsutomization .wapf-swatch', function () {
                            setTimeout(function () {
                                self.updateSelectedImage();
                                self.updatePricePerPiece();
                            }, 250);
                        });
                    },

                    watchPriceChanges: function () {
                        var self = this;

                        if (typeof MutationObserver !== 'undefined') {
                            var observer = new MutationObserver(function () {
                                self.updatePricePerPiece();
                            });

                            var $priceElements = $('.wapf-total-price, .woocommerce-Price-amount, .price');
                            $priceElements.each(function () {
                                if (this) {
                                    observer.observe(this, {
                                        childList: true,
                                        subtree: true,
                                        characterData: true
                                    });
                                }
                            });
                        }
                    }
                };

                // Initialize when ready
                $(document).ready(function () {
                    setTimeout(function () {
                        CustomProductTable.init();
                    }, 500);
                });

            })(jQuery);
        </script>
        <?php
    }
}

// Initialize plugin
function custom_product_table_init()
{
    return Custom_Product_Table::get_instance();
}
add_action('plugins_loaded', 'custom_product_table_init');

// Add settings link on plugin page
function custom_product_table_settings_link($links)
{
    $plugin_links = array(
        '<a href="https://github.com/yourrepo" target="_blank">' . __('Documentation', 'custom-product-table') . '</a>',
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'custom_product_table_settings_link');


