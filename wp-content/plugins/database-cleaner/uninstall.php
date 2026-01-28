<?php

if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
  die;
}

function dbclnr_remove_databse( ) {
    global $wpdb;

    $table_1 = $wpdb->prefix . 'dbclnr_fake_table';

    $sql = "DROP TABLE IF EXISTS $table_1";
    $wpdb->query( $sql );
}


function dbclnr_remove_crons( ) {
    // dbclnr_cron_tasks
    $timestamp = wp_next_scheduled( 'dbclnr_cron_tasks' );
    wp_unschedule_event( $timestamp, 'dbclnr_cron_tasks' );

    // dbclnr_cron_analytics
    $timestamp = wp_next_scheduled( 'dbclnr_cron_analytics' );
    wp_unschedule_event( $timestamp, 'dbclnr_cron_analytics' );
    

    // dbclnr_cron_sweeper
    $timestamp = wp_next_scheduled( 'dbclnr_cron_sweeper' );
    wp_unschedule_event( $timestamp, 'dbclnr_cron_sweeper' );
}


function dbclnr_remove_options( ) {

    global $wpdb;
    $options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'dbclnr_%'" );
    foreach ( $options as $option ) {
        delete_option( $option->option_name );
    }
}



function dbclnr_uninstall() {
    dbclnr_remove_options();
    dbclnr_remove_crons();
    dbclnr_remove_databse();
}


dbclnr_uninstall();