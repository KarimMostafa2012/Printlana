<?php
// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

global $wpdb;

if ( $wpdb->num_rows < 0 ) {
	return false;
}

$class = \WPS\WPS_Bidouille\Helpers::wps_display_block( 'wps-dashboard-mysql' ); ?>

<div id="wps-dashboard-mysql">
    <h2 class="hndle <?php echo esc_attr( $class['h2'] ); ?>"><span><?php _e( 'Report MYSQL', 'wps-bidouille' ); ?></span></h2>
    <div class="main <?php echo esc_attr( $class['div'] ); ?>">
		<?php
		$result = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A );
		$dbsize = 0;
		foreach ( $result as $row ) {
			$dbsize += $row["Data_length"] + $row["Index_length"];
		} ?>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-database"></i></span>
            <span class="wps-mysql-name"><?php _e( 'Database :', 'wps-bidouille' ); ?></span>
            <span class="wps-mysql-result"><?php echo size_format( $dbsize, 2 ); ?></span>
        </div>
        <div class="wps-row">
            <span class="wps-icon"><i class="fal fa-table"></i></span>
            <span class="wps-mysql-name"><?php _e( 'Found tables:', 'wps-bidouille' ); ?></span>
            <span class="wps-mysql-result"><?php echo count( $result ); ?></span>
        </div>
        <div class="wps-table">
            <span class="wps-icon"><i class="fas fa-info-square" data-fa-transform="grow-6"></i></span>
            <span class="wps-mysql-name"><?php _e( 'Details of the tables:', 'wps-bidouille' ); ?></span>
            <div id="wps-result-mysql">
                <ul>
					<?php
					foreach ( $result as $row ) {
						echo '<li><span class="name_mysql"><i class="fas fa-chevron-square-right"></i>  ' . $row['Name'] . '</span><span class="weight_mysql">' . round( ( ( $row["Data_length"] + $row["Index_length"] ) / 1024 / 1024 ), 2 ) . ' mb</span></li>';
					} ?>
                </ul>
            </div>
        </div>
        <p class="step">
            <a class="btn btn-wps-phpmyadmin wps-repair-db" data-nonce="<?php echo wp_create_nonce( 'repair-db' ); ?>" href="<?php echo admin_url( 'maint/repair.php?repair=1' ); ?>"
               target="_blank">
                <span class="icon-btn"><i class="fal fa-wrench" data-fa-transform="grow-6"></i></span>
                <span class="txt-btn"><?php _e( 'Repair Database' ); ?></span>
            </a>
            <a class="btn btn-wps-phpmyadmin wps-repair-db" data-nonce="<?php echo wp_create_nonce( 'repair-db' ); ?>" href="<?php echo admin_url( 'maint/repair.php?repair=2' ); ?>"
               target="_blank">
                <span class="icon-btn"><i class="fal fa-sliders-v-square" data-fa-transform="grow-6"></i></span>
                <span class="txt-btn"><?php _e( 'Repair and Optimize Database' ); ?></span>
            </a>
        </p>
    </div>
</div>