<?php
/**
 * Plugin Name: Cutting Optimizer
 * Plugin URI: https://printlana.com
 * Description: Calculate optimal cutting layouts for boxes on sheets with visual representation
 * Version: 1.0.0
 * Author: Printlana
 * Author URI: https://printlana.com
 * License: GPL v2 or later
 * Text Domain: cutting-optimizer
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class CuttingOptimizerPlugin {
    
    public function __init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'Cutting Optimizer',
            'Cutting Optimizer',
            'manage_options',
            'cutting-optimizer',
            array($this, 'render_admin_page'),
            'dashicons-grid-view',
            30
        );
    }
    
    public function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_cutting-optimizer') {
            return;
        }
        
        wp_enqueue_style(
            'cutting-optimizer-css',
            plugin_dir_url(__FILE__) . 'assets/css/style.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'cutting-optimizer-js',
            plugin_dir_url(__FILE__) . 'assets/js/optimizer.js',
            array('jquery'),
            '1.0.0',
            true
        );
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap cutting-optimizer-wrap">
            <h1><span class="dashicons dashicons-grid-view"></span> Cutting Optimizer</h1>
            <p class="description">Calculate the most efficient way to cut boxes from sheets</p>
            
            <div class="co-container">
                <div class="co-input-section">
                    <div class="co-card">
                        <h2>Input Dimensions</h2>
                        
                        <div class="co-input-group">
                            <label for="box-width">
                                <span class="dashicons dashicons-admin-page"></span>
                                Box Width (cm)
                            </label>
                            <input type="number" id="box-width" value="1" step="0.1" min="0.1">
                        </div>
                        
                        <div class="co-input-group">
                            <label for="box-height">
                                <span class="dashicons dashicons-admin-page"></span>
                                Box Length (cm)
                            </label>
                            <input type="number" id="box-height" value="1" step="0.1" min="0.1">
                        </div>
                        
                        <div class="co-input-group">
                            <label for="sheet-width">
                                <span class="dashicons dashicons-media-document"></span>
                                Sheet Width (cm)
                            </label>
                            <input type="number" id="sheet-width" value="99" step="0.1" min="0.1">
                        </div>
                        
                        <div class="co-input-group">
                            <label for="sheet-height">
                                <span class="dashicons dashicons-media-document"></span>
                                Sheet Height (cm)
                            </label>
                            <input type="number" id="sheet-height" value="69" step="0.1" min="0.1">
                        </div>
                        
                        <div class="co-input-group">
                            <label for="gap">
                                <span class="dashicons dashicons-leftright"></span>
                                Gap Between Boxes (cm)
                            </label>
                            <input type="number" id="gap" value="0.3" step="0.1" min="0">
                        </div>
                        
                        <button type="button" id="calculate-btn" class="button button-primary button-large">
                            <span class="dashicons dashicons-calculator"></span>
                            Calculate Optimal Layout
                        </button>
                    </div>
                </div>
                
                <div class="co-output-section">
                    <div id="loading" class="co-loading" style="display: none;">
                        <div class="spinner is-active"></div>
                        <p>Calculating optimal layout...</p>
                    </div>
                    
                    <div id="results" class="co-results" style="display: none;">
                        <!-- Results will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
$cutting_optimizer = new CuttingOptimizerPlugin();
$cutting_optimizer->__init();