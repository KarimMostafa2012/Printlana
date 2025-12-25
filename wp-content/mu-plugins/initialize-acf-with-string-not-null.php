<?php
/**
 * Plugin Name: Initialize ACF with String Not NULL
 * Description: Fixes ACF 6.0+ strict type checking by initializing text fields with empty strings instead of NULL on new products
 * Version: 1.0.0
 * Author: Qersh Yahya
 *
 * This plugin solves the issue where ACF fields don't render on newly created products
 * because ACF 6.0+ requires text fields to be strings, not NULL values.
 *
 * When duplicating products works but creating new ones doesn't, this is the fix.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Initialize ACF text fields with empty strings instead of NULL
 *
 * This runs after ACF saves a post to ensure all text-based fields
 * have proper string values in the database instead of NULL.
 *
 * @param int $post_id The post ID being saved
 * @return void
 */
add_action('acf/save_post', 'pl_initialize_acf_fields_with_strings', 20);
function pl_initialize_acf_fields_with_strings($post_id)
{
    // Only run for products (change this if you need it for other post types)
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    error_log('[ACF Init] Running for product ID: ' . $post_id);

    // Get all ACF field groups assigned to this post
    $field_groups = acf_get_field_groups(['post_id' => $post_id]);

    if (empty($field_groups)) {
        error_log('[ACF Init] No field groups found for product ' . $post_id);
        return;
    }

    error_log('[ACF Init] Found ' . count($field_groups) . ' field group(s) for product ' . $post_id);
    $initialized_count = 0;

    // Loop through each field group
    foreach ($field_groups as $field_group) {
        $fields = acf_get_fields($field_group['key']);

        if (empty($fields)) {
            continue;
        }

        // Loop through each field in the group
        foreach ($fields as $field) {
            $value = get_field($field['name'], $post_id);

            // If value is NULL or false, initialize it with proper type
            if ($value === null || $value === false || $value === '') {
                $field_type = $field['type'];

                // Text-based fields that must be strings, not NULL
                $text_types = [
                    'text',
                    'textarea',
                    'wysiwyg',
                    'email',
                    'url',
                    'number',
                    'select',
                    'radio',
                    'checkbox',
                    'button_group',
                    'true_false',
                    'date_picker',
                    'time_picker',
                    'color_picker',
                    'range'
                ];

                if (in_array($field_type, $text_types)) {
                    // Determine the correct default value based on field type
                    switch ($field_type) {
                        case 'checkbox':
                            $default_value = [];
                            break;
                        case 'true_false':
                            $default_value = 0;
                            break;
                        case 'number':
                        case 'range':
                            $default_value = '';
                            break;
                        default:
                            $default_value = '';
                            break;
                    }

                    // Update the field with the proper default value
                    update_field($field['name'], $default_value, $post_id);
                    $initialized_count++;
                    error_log('[ACF Init] Initialized field: ' . $field['name'] . ' (type: ' . $field_type . ') with value: ' . json_encode($default_value));
                }
            }
        }
    }

    error_log('[ACF Init] Initialized ' . $initialized_count . ' field(s) for product ' . $post_id);
}

/**
 * Optional: Add admin notice to confirm plugin is active
 * Uncomment this section if you want to see a confirmation message in admin
 */
/*
add_action('admin_notices', 'pl_acf_init_plugin_notice');
function pl_acf_init_plugin_notice()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    // Only show on plugins page
    $screen = get_current_screen();
    if ($screen && $screen->id === 'plugins') {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>ACF String Initializer:</strong> Active and protecting against NULL value errors on new products.</p>';
        echo '</div>';
    }
}
*/
