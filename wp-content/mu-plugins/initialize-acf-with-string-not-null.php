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
 * FIX #1: Initialize ACF text fields on save
 * Runs after ACF saves a post to ensure all text-based fields
 * have proper string values in the database instead of NULL.
 */
add_action('acf/save_post', 'pl_initialize_acf_fields_with_strings', 20);
function pl_initialize_acf_fields_with_strings($post_id)
{
    // Only run for products
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    error_log('[ACF Init Save] Running for product ID: ' . $post_id);

    // Get all ACF field groups assigned to this post
    $field_groups = acf_get_field_groups(['post_id' => $post_id]);

    if (empty($field_groups)) {
        error_log('[ACF Init Save] No field groups found for product ' . $post_id);
        return;
    }

    error_log('[ACF Init Save] Found ' . count($field_groups) . ' field group(s)');
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
                    error_log('[ACF Init Save] Initialized field: ' . $field['name'] . ' = ' . json_encode($default_value));
                }
            }
        }
    }

    error_log('[ACF Init Save] Initialized ' . $initialized_count . ' field(s)');
}

/**
 * FIX #2: Filter ACF values when loading on front end
 * This prevents NULL values from being returned and causing warnings
 */
add_filter('acf/load_value', 'pl_convert_null_to_empty_string', 10, 3);
function pl_convert_null_to_empty_string($value, $post_id, $field)
{
    // Only process text-type fields
    $text_types = [
        'text',
        'textarea',
        'wysiwyg',
        'email',
        'url',
        'number',
        'select',
        'radio'
    ];

    if (!in_array($field['type'], $text_types)) {
        return $value;
    }

    // If value is NULL, return empty string instead
    if ($value === null || $value === false) {
        return '';
    }

    return $value;
}

/**
 * FIX #3: Format ACF values before display
 * Additional safety layer to ensure no NULL values reach the template
 */
add_filter('acf/format_value', 'pl_ensure_string_value', 10, 3);
function pl_ensure_string_value($value, $post_id, $field)
{
    // Text fields must return strings
    $text_types = ['text', 'textarea', 'email', 'url', 'number'];

    if (in_array($field['type'], $text_types) && ($value === null || $value === false)) {
        return '';
    }

    return $value;
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
