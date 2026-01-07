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

class Custom_Product_Table {
    
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
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('product_table', array($this, 'render_product_table'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('custom-product-table', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Render product table shortcode
     */
    public function render_product_table($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'style' => 'default',
        ), $atts, 'product_table');
        
        // Start output buffering
        ob_start();
        
        // Include HTML
        $this->render_html();
        
        // Include JavaScript
        $this->render_scripts();
        
        return ob_get_clean();
    }
    
    /**
     * Render HTML structure
     */
    private function render_html() {
        ?>
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
                            <button type="button" class="acf-qty-minus" aria-label="<?php esc_attr_e('Decrease quantity', 'custom-product-table'); ?>">−</button>
                            <input id="acf-qty-val" type="number" min="1" value="1">
                            <button type="button" class="acf-qty-plus" aria-label="<?php esc_attr_e('Increase quantity', 'custom-product-table'); ?>">+</button>
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
    private function render_scripts() {
        ?>
        <script>
        (function($) {
            'use strict';
            
            var CustomProductTable = {
                
                init: function() {
                    this.updateSelectedImage();
                    this.updateProductionTime();
                    this.updateQtyDisplay();
                    this.bindEvents();
                    this.watchPriceChanges();
                },
                
                updateSelectedImage: function() {
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
                        $('#acf-selected-img').html('<span>No image</span>');
                    }
                },
                
                updatePricePerPiece: function() {
                    var qty = parseInt($('input[name="quantity"]').val()) || 1;
                    var totalPrice = 0;
                    
                    // Try multiple price sources
                    var $price = $('.wapf-total-price').first();
                    if (!$price.length) {
                        $price = $('.woocommerce-Price-amount').first();
                    }
                    if (!$price.length) {
                        $price = $('.price .amount').first();
                    }
                    
                    if ($price.length) {
                        var priceText = $price.text().replace(/[^0-9.]/g, '');
                        totalPrice = parseFloat(priceText);
                    }
                    
                    if (totalPrice > 0 && qty > 0) {
                        var perPiece = (totalPrice / qty).toFixed(2);
                        var currency = this.getCurrency();
                        $('#acf-price-piece').text(perPiece + ' ' + currency);
                    } else {
                        $('#acf-price-piece').text('--');
                    }
                },
                
                getCurrency: function() {
                    var currency = 'EGP';
                    var $currencySymbol = $('.woocommerce-Price-currencySymbol').first();
                    if ($currencySymbol.length) {
                        currency = $currencySymbol.text();
                    }
                    return currency;
                },
                
                updateProductionTime: function() {
                    // Try multiple attribute selectors
                    var time = $('[data-attribute="production-time"]').text().trim() ||
                              $('[data-attribute="production_time"]').text().trim() ||
                              $('.production-time-attribute').text().trim() ||
                              $('.woocommerce-product-attributes-item--production-time .woocommerce-product-attributes-item__value').text().trim() ||
                              $('.woocommerce-product-attributes-item--production_time .woocommerce-product-attributes-item__value').text().trim();
                    
                    $('#acf-prod-time').text(time || 'N/A');
                },
                
                updateQtyDisplay: function() {
                    var qty = parseInt($('input[name="quantity"]').val()) || 1;
                    $('#acf-qty-val').text(qty);
                    this.updatePricePerPiece();
                    this.updateButtonStates();
                },
                
                updateButtonStates: function() {
                    var $input = $('input[name="quantity"]');
                    var current = parseInt($input.val()) || 1;
                    var min = parseInt($input.attr('min')) || 1;
                    var max = parseInt($input.attr('max')) || 999999;
                    
                    $('.acf-qty-minus').prop('disabled', current <= min);
                    $('.acf-qty-plus').prop('disabled', current >= max);
                },
                
                bindEvents: function() {
                    var self = this;
                    
                    // Quantity buttons
                    $('.acf-qty-plus').on('click', function(e) {
                        e.preventDefault();
                        var $input = $('input[name="quantity"]');
                        var current = parseInt($input.val()) || 1;
                        var max = parseInt($input.attr('max')) || 999999;
                        
                        if (current < max) {
                            $input.val(current + 1).trigger('change');
                        }
                    });
                    
                    $('.acf-qty-minus').on('click', function(e) {
                        e.preventDefault();
                        var $input = $('input[name="quantity"]');
                        var current = parseInt($input.val()) || 1;
                        var min = parseInt($input.attr('min')) || 1;
                        
                        if (current > min) {
                            $input.val(current - 1).trigger('change');
                        }
                    });
                    
                    // WooCommerce quantity change
                    $(document).on('change', 'input[name="quantity"]', function() {
                        self.updateQtyDisplay();
                    });
                    
                    // WAPF field changes
                    $(document).on('change click', '.fastCsutomization input, .fastCsutomization select, .fastCsutomization .wapf-swatch', function() {
                        setTimeout(function() {
                            self.updateSelectedImage();
                            self.updatePricePerPiece();
                        }, 250);
                    });
                },
                
                watchPriceChanges: function() {
                    var self = this;
                    
                    if (typeof MutationObserver !== 'undefined') {
                        var observer = new MutationObserver(function() {
                            self.updatePricePerPiece();
                        });
                        
                        var $priceElements = $('.wapf-total-price, .woocommerce-Price-amount, .price');
                        $priceElements.each(function() {
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
            $(document).ready(function() {
                setTimeout(function() {
                    CustomProductTable.init();
                }, 500);
            });
            
        })(jQuery);
        </script>
        <?php
    }
}

// Initialize plugin
function custom_product_table_init() {
    return Custom_Product_Table::get_instance();
}
add_action('plugins_loaded', 'custom_product_table_init');

// Add settings link on plugin page
function custom_product_table_settings_link($links) {
    $plugin_links = array(
        '<a href="https://github.com/yourrepo" target="_blank">' . __('Documentation', 'custom-product-table') . '</a>',
    );
    return array_merge($plugin_links, $links);
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'custom_product_table_settings_link');


