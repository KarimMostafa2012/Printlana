<?php

namespace WeDevs\DokanPro\Modules\DeliveryTime;

use DateInterval;
use DatePeriod;
use Exception;
use WC_Order;
use WeDevs\DokanPro\Modules\DeliveryTime\StorePickup\Helper as StorePickupHelper;

/**
 * Class DeliveryTimeHelper
 */
class Helper {

    /**
     * Gets all time slots for a day
     *
     * @since 3.3.0
     *
     * @return array
     */
    public static function get_all_delivery_time_slots() {
        $minutes = [];

        $date       = dokan_current_datetime();
        $start_date = $date->setTime( 0, 0, 0 );
        $end_date   = $date->setTime( 23, 59, 59 );

        $interval    = new DateInterval( 'PT30M' );
        $date_period = new DatePeriod( $start_date, $interval, $end_date );

        foreach ( $date_period as $date ) {
            $time_key             = $date->format( 'h:i A' );
            $time_value           = $date->format( wc_time_format() );
            $minutes[ $time_key ] = $time_value;
        }

        return $minutes;
    }

    /**
     * Generates time slot based on start, end time and defined slot duration
     *
     * @since 3.3.0
     *
     * @param int    $duration
     * @param string $starts
     * @param string $ends
     *
     * @return array
     */
    public static function generate_delivery_time_slots( $duration, $starts, $ends ) {
        $time = [];

        if ( empty( $starts ) || empty( $ends ) ) {
            return $time;
        }

        $date          = dokan_current_datetime();
        $ends          = (array) $ends;
        $starts        = (array) $starts;
        $interval      = new DateInterval( 'PT' . intval( $duration ) . 'M' );
        $starts_length = count( $starts );

        for ( $index = 0; $index < $starts_length; $index++ ) {
            $start_date = $date->modify( $starts[ $index ] );
            $end_date   = $date->modify( $ends[ $index ] );

            if ( ! $start_date || ! $end_date ) {
                continue;
            }

            while ( $start_date < $end_date ) {
                $start      = $start_date->format( 'g:i a' );
                $start_date = $start_date->add( $interval );
                $end        = $start_date->format( 'g:i a' );

                $time[ $start . ' - ' . $end ]['start'] = $start;
                $time[ $start . ' - ' . $end ]['end']   = $end;
            }
        }

        return $time;
    }

    /**
     * Gets available delivery slots by date for a vendor
     *
     * @since 3.3.0
     *
     * @param int $vendor_id
     * @param int $vendor_order_per_slot
     * @param string $date
     *
     * @return array
     */
    public static function get_available_delivery_slots_by_date( $vendor_id, $vendor_order_per_slot, $date ) {
        global $wpdb;

        $delivery_slots = [];
        $blocked_slots  = [];

        if ( empty( $vendor_id ) || empty( $date ) || -1 === $vendor_order_per_slot ) {
            return $delivery_slots;
        }

        $default_delivery_slots_all = self::get_delivery_slot_settings( $vendor_id, $date );

        if ( empty( $default_delivery_slots_all ) ) {
            return $delivery_slots;
        }

        $current_date           = dokan_current_datetime();
        $current_date           = $current_date->modify( $date );
        $day                    = strtolower( trim( $current_date->format( 'l' ) ) );
        $default_delivery_slots = isset( $default_delivery_slots_all[ $day ] ) ? $default_delivery_slots_all[ $day ] : [];

        if ( empty( $default_delivery_slots ) ) {
            return $delivery_slots;
        }

        // Vendor vacation support
        $vendor_infos = dokan()->vendor->get( $vendor_id )->get_shop_info();

        $vendor_vacation_active = isset( $vendor_infos['setting_go_vacation'] ) ? $vendor_infos['setting_go_vacation'] : '';
        $vendor_vacation_style  = isset( $vendor_infos['settings_closing_style'] ) ? $vendor_infos['settings_closing_style'] : '';

        if ( 'yes' === $vendor_vacation_active && 'instantly' === $vendor_vacation_style ) {
            return $delivery_slots;
        }

        $vendor_vacation_dates = ( isset( $vendor_infos['seller_vacation_schedules'] ) && is_array( $vendor_infos['seller_vacation_schedules'] ) ) ? $vendor_infos['seller_vacation_schedules'] : [];
        foreach ( $vendor_vacation_dates as $vacation_date ) {
            if ( ( $date >= $vacation_date['from'] ) && ( $date <= $vacation_date['to'] ) ) {
                return $delivery_slots;
            }
        }

        // When vendor order per slot is 0, no limitation on delivery time slots
        if ( 0 === (int) $vendor_order_per_slot ) {
            return $default_delivery_slots;
        }

        $blocked_slots_result = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT slot, COUNT(*) AS count
                    FROM {$wpdb->prefix}dokan_delivery_time
                    where vendor_id = %d
                    AND date = %s
                    GROUP BY slot
                    HAVING count >= %d",
                $vendor_id,
                $date,
                absint( $vendor_order_per_slot )
            )
        );

        foreach ( $blocked_slots_result as $blocked_slot ) {
            $blocked_slots[ $blocked_slot->slot ] = $blocked_slot->slot;
        }

        $delivery_slots = array_diff_key( $default_delivery_slots, array_flip( $blocked_slots ) );

        return $delivery_slots;
    }

    /**
     * Saves dokan delivery time date slot for tracking the slot availability on a date
     *
     * @since 3.3.0
     *
     * @param array $data
     * @param WC_Order|null $order
     *
     * @return void
     */
    public static function save_delivery_time_date_slot( $data, $order = null ) {
        $order                  = $order ? $order : $data['order'];
        $vendor_id              = $data['vendor_id'];
        $delivery_date          = $data['delivery_date'];
        $delivery_time_slot     = $data['delivery_time_slot'];
        $selected_delivery_type = isset( $data['selected_delivery_type'] ) ? $data['selected_delivery_type'] : 'delivery';

        if ( empty( $delivery_date ) ) {
            dokan_log( sprintf( 'Failed to get delivery date for order id: %1$s', $order->get_id() ) );
            return;
        }

        if ( empty( $delivery_time_slot ) ) {
            dokan_log( sprintf( 'Failed to get delivery slot for order id: %1$s', $order->get_id() ) );
            return;
        }

        $args = [
            'order_id'      => $order->get_id(),
            'vendor_id'     => absint( $vendor_id ),
            'date'          => $delivery_date,
            'slot'          => $delivery_time_slot,
            'delivery_type' => $selected_delivery_type,
        ];

        global $wpdb;

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'dokan_delivery_time',
            $args,
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        // Check if the order is inserted to DB
        if ( ! $inserted ) {
            dokan_log( sprintf( 'Failed to insert delivery time row for order id: %1$s', $order->get_id() ) );
            return;
        }

        $order->update_meta_data( 'dokan_delivery_time_slot', $delivery_time_slot );
        $order->update_meta_data( 'dokan_delivery_time_date', $delivery_date );

        do_action( 'dokan_delivery_time_before_meta_save', $order, $data );
    }

    /**
     * Gets delivery time settings for vendor
     *
     * @since 3.3.0
     *
     * @param int $vendor_id
     *
     * @return array
     */
    public static function get_delivery_time_settings( $vendor_id ) {
        $delivery_settings = [];

        if ( ! $vendor_id ) {
            return $delivery_settings;
        }

        // Getting override settings for vendor
        $vendor_can_override_settings = static::vendor_can_override_settings();
        // Getting vendor delivery settings
        $vendor_delivery_time_settings = get_user_meta( $vendor_id, '_dokan_vendor_delivery_time_settings', true );

        // get delivery time settings from vendor user meta if set
        if ( $vendor_can_override_settings && isset( $vendor_delivery_time_settings['delivery_day'] ) ) {
            return $vendor_delivery_time_settings;
        }

        // Getting admin delivery settings
        $admin_delivery_time_settings      = get_option( 'dokan_delivery_time', [] );
        $delivery_settings['delivery_day'] = [];

        foreach ( dokan_get_translated_days() as $day_key => $day ) {
            $delivery_settings['opening_time'][ $day_key ] = ! empty( $admin_delivery_time_settings[ 'delivery_day_' . $day_key ]['opening_time'] ) ? $admin_delivery_time_settings[ 'delivery_day_' . $day_key ]['opening_time'] : '';
            $delivery_settings['closing_time'][ $day_key ] = ! empty( $admin_delivery_time_settings[ 'delivery_day_' . $day_key ]['closing_time'] ) ? $admin_delivery_time_settings[ 'delivery_day_' . $day_key ]['closing_time'] : '';

            if ( ! empty( $admin_delivery_time_settings[ 'delivery_day_' . $day_key ]['delivery_status'] ) ) {
                $delivery_settings['delivery_day'][ $day_key ] = $day_key;
            }
        }

        $delivery_settings['preorder_date']     = isset( $admin_delivery_time_settings['preorder_date'] ) ? $admin_delivery_time_settings['preorder_date'] : '0';
        $delivery_settings['order_per_slot']    = isset( $admin_delivery_time_settings['order_per_slot']) ? $admin_delivery_time_settings['order_per_slot'] : '0';
        $delivery_settings['delivery_support']  = ! empty( $admin_delivery_time_settings['delivery_support']['delivery'] ) && 'delivery' === $admin_delivery_time_settings['delivery_support']['delivery'] ? 'on' : 'off';
        $delivery_settings['time_slot_minutes'] = isset( $admin_delivery_time_settings['time_slot_minutes'] ) ? $admin_delivery_time_settings['time_slot_minutes'] : '30';

        return $delivery_settings;
    }

    /**
     * Collect delivery times here.
     *
     * @since 3.7.8
     *
     * @param string       $current_day
     * @param array|string $delivery_times
     * @param int          $index
     *
     * @return mixed|string
     */
    public static function get_delivery_times( $current_day, $delivery_times, $index = 0 ) {
        $delivery_times         = ! empty( $delivery_times ) ? $delivery_times : [];
        $current_delivery_times = ! empty( $delivery_times[ $current_day ] ) ? $delivery_times[ $current_day ] : '';

        if ( empty( $current_delivery_times ) ) {
            return '';
        }

        if ( ! is_array( $current_delivery_times ) ) {
            return $current_delivery_times;
        }

        if ( is_numeric( $index ) && isset( $current_delivery_times[ $index ] ) ) {
            return $current_delivery_times[ $index ];
        }

        return $current_delivery_times[0]; // return the 1st index
    }

    /**
     * Gets delivery slot settings for a vendor
     *
     * @since 3.3.0
     *
     * @param int    $vendor_id
     * @param string $date
     *
     * @return array
     */
    public static function get_delivery_slot_settings( $vendor_id, $date ) {
        $delivery_slot_settings = [];

        if ( ! $vendor_id ) {
            return $delivery_slot_settings;
        }

        // Getting override settings for vendor
        $vendor_can_override_settings = static::vendor_can_override_settings();

        // Getting admin slot settings
        $delivery_slot_settings = get_option( '_dokan_delivery_slot_settings', [] );

        // Getting admin buffer day setting
        $delivery_buffer_days = dokan_get_option( 'preorder_date', 'dokan_delivery_time', '0' );
        $time_slot_duration   = dokan_get_option( 'time_slot_minutes', 'dokan_delivery_time', '0' );

        // Get todays date data
        $now          = dokan_current_datetime();
        $today        = strtolower( $now->format( 'l' ) );
        $current_date = $now->format( 'Y-m-d' );

        // Getting vendor slot settings
        $vendor_slot_settings = get_user_meta( $vendor_id, '_dokan_vendor_delivery_time_slots', true );
        if ( $vendor_can_override_settings && ( is_array( $vendor_slot_settings ) && ! empty( $vendor_slot_settings ) ) ) {
            $delivery_slot_settings        = $vendor_slot_settings;
            $vendor_delivery_time_settings = get_user_meta( $vendor_id, '_dokan_vendor_delivery_time_settings', true );
            $delivery_buffer_days          = isset( $vendor_delivery_time_settings['preorder_date'] ) ? $vendor_delivery_time_settings['preorder_date'] : '0';
            $time_slot_duration            = isset( $vendor_delivery_time_settings['time_slot_minutes'][ $today ] ) ? $vendor_delivery_time_settings['time_slot_minutes'][ $today ] : '0';
        }

        // Return if delivery time slots isn't current day time slot.
        if ( 0 !== intval( $delivery_buffer_days ) || ! array_key_exists( $today, $delivery_slot_settings ) ) {
            return $delivery_slot_settings;
        }

        $current_time = $now->modify( "+ $time_slot_duration minutes" )->format( 'h:i a' );
        $delivery_slot_settings[ $today ] = array_filter(
            $delivery_slot_settings[ $today ], function( $data ) use ( $current_time, $current_date, $date ) {
                return ! ( $current_date === $date ) || strtotime( $data['start'] ) > strtotime( $current_time );
            }
        );

        return $delivery_slot_settings;
    }

    /**
     * Formats delivery date and time slot string
     *
     * @since 3.3.0
     *
     * @param string $date
     * @param string $slot
     *
     * @return string
     * @throws \Exception
     */
    public static function get_formatted_delivery_date_time_string( $date, $slot ) {
        $formatted_string = '-- @ --';
        if ( empty( $date ) || empty( $slot ) || ! strtotime( $date ) ) {
            return $formatted_string;
        }

        try {
            $current_date   = dokan_current_datetime();
            $current_date   = $current_date->modify( $date );
            $change_to_date = $current_date->format( wc_date_format() );
            $formatted_slot = self::get_formatted_delivery_slot_string( $slot );

            $formatted_string = $change_to_date . ' @ ' . $formatted_slot;
        } catch ( Exception $e ) {
            dokan_log( sprintf( 'Failed to parse date for: %1$s', $date ) );
        }

        return $formatted_string;
    }

    /**
     * Checks if delivery date and time is updated
     *
     * @since 3.9.4
     *
     * @param int|string $vendor_id
     * @param int|string $order_id
     * @param array $new_data
     *
     * @return bool
     */
    public static function is_delivery_data_updated( $vendor_id, $order_id, $new_data ) {
        return ( ! empty( $new_data['delivery_date'] )
            || ! empty( $new_data['delivery_time_slot'] )
            || ! empty( $new_data['store_pickup_location'] ) );
    }

    /**
     * Updates the delivery time date slot
     *
     * @since 3.3.0
     *
     * @param array $data
     *
     * @return void
     */
    public static function update_delivery_time_date_slot( $data ) {
        $delivery_date                              = isset( $data['delivery_date'] ) ? $data['delivery_date'] : '';
        $delivery_time_slot                         = isset( $data['delivery_time_slot'] ) ? $data['delivery_time_slot'] : '';
        $vendor_selected_current_delivery_date_slot = isset( $data['vendor_selected_current_delivery_date_slot'] ) ? $data['vendor_selected_current_delivery_date_slot'] : '';
        $order_id                                   = isset( $data['order_id'] ) ? $data['order_id'] : 0;
        $delivery_type                              = isset( $data['selected_delivery_type'] ) ? $data['selected_delivery_type'] : 'delivery';
        $store_pickup_location                      = isset( $data['store_pickup_location'] ) ? $data['store_pickup_location'] : '';
        $prev_delivery_info                         = isset( $data['prev_delivery_info'] ) ? $data['prev_delivery_info'] : '';

        $vendor_id = (int) dokan_get_seller_id_by_order( $order_id );

        if ( empty( $delivery_date ) || empty( $delivery_time_slot ) || ! strtotime( $delivery_date ) || 0 === $order_id || 0 === $vendor_id ) {
            return;
        }

        if ( 'store-pickup' === $delivery_type && empty( $store_pickup_location ) ) {
            return;
        }

        $order               = wc_get_order( $order_id );
        $prev_store_location = $order->get_meta( 'dokan_store_pickup_location' );

        global $wpdb;

        // Delete delivery time slot record for the order
        $wpdb->delete( $wpdb->prefix . 'dokan_delivery_time', [ 'order_id' => $order_id ] );

        // Save new slot to the database
        $data = [
            'order'                  => $order,
            'vendor_id'              => $vendor_id,
            'delivery_date'          => $delivery_date,
            'delivery_time_slot'     => $delivery_time_slot,
            'selected_delivery_type' => $delivery_type,
        ];

        if ( 'store-pickup' === $delivery_type ) {
            $data['store_pickup_location'] = $store_pickup_location;
        }

        self::save_delivery_time_date_slot( $data, $order );

        $user_info       = get_userdata( dokan_get_current_user_id() );
        $updated_by      = $user_info->user_nicename;
        $current_date    = dokan_current_datetime();
        $change_to_date  = $current_date->modify( $delivery_date )->format( wc_date_format() );
        $formatted_slot  = self::get_formatted_delivery_slot_string( $delivery_time_slot );
        $store_location  = $prev_store_location === $store_pickup_location ? " : $store_pickup_location" :
            " : & also store pickup location updated from $prev_store_location to $store_pickup_location";
        $pickup_location = 'store-pickup' === $delivery_type ? $store_location : '';

        if ( $prev_delivery_info ) {
            $policy_note     = $prev_delivery_info->delivery_type === $delivery_type ? "$delivery_type time changed from" :
                "policy changed from $prev_delivery_info->delivery_type to $delivery_type & time";
        } else {
            $policy_note = '';
        }

        // Saving order note
        $note = sprintf(
            'Order %1$s %2$s to %3$s @ %4$s %5$s - by %6$s',
            $policy_note,
            $vendor_selected_current_delivery_date_slot,
            $change_to_date,
            $formatted_slot,
            $pickup_location,
            $updated_by
        );

        $order->add_order_note( $note, false, true );

        // Saving order meta
        $order->update_meta_data( 'dokan_delivery_time_date', $delivery_date );
        $order->update_meta_data( 'dokan_delivery_time_slot', $delivery_time_slot );

        if ( 'store-pickup' === $delivery_type ) {
            $order->update_meta_data( 'dokan_store_pickup_location', $store_pickup_location );
        }

        if ( is_admin() ) {
            remove_action( 'save_post_shop_order', [ dokan_pro()->module->delivery_time->dt_admin, 'save_admin_delivery_time_meta_box' ], 10, 1 );
            $order->save();
        } else {
            $order->save();
        }
    }

    /**
     * Gets delivery event additional infos
     *
     * @since 3.3.7
     *
     * @param int $order_id
     * @param string $type
     * @param string $date
     * @param string $slot
     *
     * @return array
     */
    public static function get_delivery_event_additional_info( $order_id, $type, $date, $slot ) {
        $additional_info = [];

        $order = wc_get_order( $order_id );

        if ( ! $order ) {
            return $additional_info;
        }

        $body = sprintf( '<span>%s</span>', self::get_formatted_delivery_date_time_string( $date, $slot ) );

        if ( 'store-pickup' === $type ) {
            $location       = $order->get_meta( 'dokan_store_pickup_location' );
            $formatted_info = StorePickupHelper::get_formatted_date_store_location_string( $date, $location, $slot );
            $body           = sprintf( '<span>%s</span>', $formatted_info );
        }

        $additional_info['body'] = $body;

        return $additional_info;
    }

    /**
     * Get formatted delivery slot.
     *
     * @since 3.7.8
     *
     * @param string $slot_string
     *
     * @return string
     */
    public static function get_formatted_delivery_slot_string( $slot_string ) {
        $slot_data            = explode( ' - ', $slot_string );
        $formatted_start_slot = dokan_format_time( $slot_data[0] );
        $formatted_end_slot   = dokan_format_time( $slot_data[1] );
        $formatted_slot       = $formatted_start_slot . ' - ' . $formatted_end_slot;

        return $formatted_slot;
    }

    /**
     * Collect current order delivery type data.
     *
     * @since 3.7.8
     *
     * @param int $seller_id
     * @param int $order_id
     *
     * @return mixed
     */
    public static function get_order_delivery_info( $seller_id, $order_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM `{$wpdb->prefix}dokan_delivery_time` where `order_id` = %d AND `vendor_id` = %d", $order_id, $seller_id )
        );
    }

    /**
     * Collect active delivery time slots for current date.
     *
     * @since 3.7.8
     *
     * @param int    $vendor_id
     * @param string $date
     *
     * @return array
     */
    public static function get_current_date_active_delivery_time_slots( $vendor_id, $date ) {
        $vendor_can_override  = static::vendor_can_override_settings();
        $admin_delivery_slots = get_option( '_dokan_delivery_slot_settings', [] );
        $current_day_of_week  = strtolower( dokan_current_datetime()->modify( $date )->format( 'l' ) );

        $vendor_delivery_options = self::get_delivery_time_settings( $vendor_id );
        $vendor_order_per_slot   = (int) isset( $vendor_delivery_options['order_per_slot'] ) ? $vendor_delivery_options['order_per_slot'] : -1;

        $admin_delivery_slots  = ! empty( $admin_delivery_slots[ $current_day_of_week ] ) ? $admin_delivery_slots[ $current_day_of_week ] : [];
        $vendor_delivery_slots = self::get_available_delivery_slots_by_date( $vendor_id, $vendor_order_per_slot, $date );

        $delivery_slots = $vendor_can_override ? $vendor_delivery_slots : $admin_delivery_slots;

        if ( $date === dokan_current_datetime()->format( "Y-m-d" ) ) {
            $delivery_slots = array_filter( $delivery_slots, function( $slot ) {
                return strtotime( $slot[ 'start' ] ) > strtotime( dokan_current_datetime()->format( 'g:i a' ) );
            } );
        }

        return $delivery_slots;
    }

    /**
     * Check if vendor can override delivery time settings
     *
     * @since 3.7.8
     *
     * @return bool
     */
    public static function vendor_can_override_settings() {
        return 'on' === dokan_get_option( 'allow_vendor_override_settings', 'dokan_delivery_time', 'off' );
    }

    /**
     * Gets vendor delivery time infos for customers
     *
     * @since 3.3.0
     *
     * @return array
     */
    public static function get_vendor_delivery_time_info() {
        $vendor_infos = [];

        if ( ! wc() || ! wc()->cart ) {
            return $vendor_infos;
        }

        $items = wc()->cart->get_cart();

        foreach ( $items as $item => $values ) {
            $vendor   = [];
            $_product = wc_get_product( $values['data']->get_id() );

            // Continue if the product is downloadable or virtual
            if ( $_product->is_downloadable() || $_product->is_virtual() ) {
                continue;
            }

            $_vendor    = dokan_get_vendor_by_product( $_product );
            $_vendor_id = (int) $_vendor->get_id();

            if ( isset( $vendor_infos[ $_vendor_id ] ) ) {
                continue;
            }

            $vendor_delivery_options = self::get_delivery_time_settings( $_vendor_id );

            $delivery_date_label = dokan_get_option( 'delivery_date_label', 'dokan_delivery_time', 'off' );
            $delivery_box_info   = dokan_get_option( 'delivery_box_info', 'dokan_delivery_time', 'off' );

            $is_delivery_time_active           = isset( $vendor_delivery_options['delivery_support'] ) && 'on' === $vendor_delivery_options['delivery_support'];
            $vendor['is_delivery_time_active'] = $is_delivery_time_active;

            $preorder_date = isset( $vendor_delivery_options['preorder_date'] ) ? $vendor_delivery_options['preorder_date'] : '';

            $delivery_box_info_message = str_replace( '%DAY%', $preorder_date, $delivery_box_info );

            $vendor_delivery_options['delivery_date_label']       = $delivery_date_label;
            $vendor_delivery_options['delivery_box_info_message'] = $delivery_box_info_message;

            $store_info = dokan_get_store_info( $_vendor_id );

            $current_date = dokan_current_datetime();
            $date         = strtolower( $current_date->format( 'Y-m-d' ) );
            $day          = strtolower( $current_date->format( 'l' ) );

            $vendor_order_per_slot              = (int) isset( $vendor_delivery_options['order_per_slot'][ $day ] ) ? $vendor_delivery_options['order_per_slot'][ $day ] : -1;
            $vendor_preorder_blocked_date_count = (int) ( isset( $vendor_delivery_options['preorder_date'] ) && $vendor_delivery_options['preorder_date'] > 0 ) ? $vendor_delivery_options['preorder_date'] : 0;
            $vendor_delivery_slots              = $is_delivery_time_active ? self::get_available_delivery_slots_by_date( $_vendor_id, $vendor_order_per_slot, $date ) : [];

            $vendor['store_name']              = $store_info['store_name'];
            $vendor['delivery_time_slots']     = $vendor_delivery_slots;
            $vendor['vendor_delivery_options'] = $vendor_delivery_options;
            $vendor['vendor_vacation_days']    = ( dokan_pro()->module->is_active( 'seller_vacation' ) && isset( $store_info['seller_vacation_schedules'] ) && isset( $store_info['setting_go_vacation'] ) && 'yes' === $store_info['setting_go_vacation'] ) ? $store_info['seller_vacation_schedules'] : [];

            $current_date                  = $current_date->modify( '+' . $vendor_preorder_blocked_date_count . ' day' );
            $vendor_preorder_block_date_to = strtolower( $current_date->format( 'Y-m-d' ) );

            $vendor['vendor_preorder_blocked_dates'] = [];

            if ( $vendor_preorder_blocked_date_count > 0 ) {
                $vendor['vendor_preorder_blocked_dates'] = [
                    [
                        'from' => $date,
                        'to'   => $vendor_preorder_block_date_to,
                    ],
                ];
            }

            $is_store_location_pickup_active    = StorePickupHelper::is_store_pickup_location_active( $_vendor_id );
            $vendor['is_store_location_active'] = $is_store_location_pickup_active;

            $vendor = apply_filters( 'dokan_vendor_delivery_time_info', $vendor, $_vendor );

            if ( ( isset( $vendor['is_store_location_active'] ) && $vendor['is_store_location_active'] ) || $is_delivery_time_active ) {
                $vendor_infos[ $_vendor_id ] = $vendor;
            }
        }

        return apply_filters( 'dokan_all_vendors_delivery_time_info', $vendor_infos );
    }

    /**
     * Gets dashboard calendar events.
     *
     * Moved from dokan_pro/modules/delivery-time/includes/Vendor.php Line#609
     *
     * @since 4.0.10
     *
     * @return array
     */
    public static function get_dashboard_calendar_event( $start_date, $end_date, $filter_type = '' ) {
        global $wpdb;
        $filter_query = ( ! empty( $filter_type ) && in_array( $filter_type, [ 'delivery', 'store-pickup' ], true ) ) ? $wpdb->prepare( ' AND `delivery_type` = %s', $filter_type ) : '';

        $vendor_id = dokan_get_current_user_id();

        // @codingStandardsIgnoreStart
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}dokan_delivery_time`
                WHERE `vendor_id` = %d
                AND DATE(`date`) BETWEEN %s AND %s
                {$filter_query}
                ORDER BY `date`;",
                $vendor_id,
                $start_date,
                $end_date
            )
        );
        // @codingStandardsIgnoreEnd

        $calendar_events = [];

        foreach ( $results as $result ) {
            $order_id  = absint( $result->order_id );
            $time_slot = preg_split( '/[\\n\-]+/', $result->slot );

            $type = $result->delivery_type;

            if ( empty( $type ) ) {
                return;
            }

            $url = add_query_arg(
                [
                    'order_id' => $order_id,
                    '_wpnonce' => wp_create_nonce( 'dokan_view_order' ),
                ], dokan_get_navigation_url( 'orders' )
            );

            $start_date = dokan_current_datetime();
            $start_date = $start_date->modify( $result->date . ' ' . $time_slot[0] );
            $end_date   = $start_date->modify( $time_slot[1] )->format( 'Y-m-d\TH:i' );
            $start_date = $start_date->format( 'Y-m-d\TH:i' );

            $title = '';

            if ( 'delivery' === $type ) {
                /* translators: %s: order ID */
                $title = sprintf( __( 'Delivery #%1$s', 'dokan' ), $result->order_id );
            } elseif ( 'store-pickup' === $type ) {
                /* translators: %s: order ID */
                $title = sprintf( __( 'Store Pickup #%1$s', 'dokan' ), $result->order_id );
            }

            $additional_info = self::get_delivery_event_additional_info( $order_id, $type, $result->date, $result->slot );

            $calendar_events[] = [
                'title' => $title,
                'start' => $start_date,
                'end'   => $end_date,
                'url'   => $url,
                'info'  => $additional_info,
            ];
        }

        return apply_filters( 'dokan_delivery_time_dashboard_calendar_events', $calendar_events, $start_date, $end_date, $filter_type );
    }
}
