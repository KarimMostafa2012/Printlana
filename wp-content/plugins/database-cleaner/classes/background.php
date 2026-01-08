<?php



class Meow_DBCLNR_Background
{
	public $core = null;

  public function __construct( $core ) {
		$this->core = $core;
    if ( !wp_next_scheduled( 'dbclnr_cron_tasks' ) ) {
      wp_schedule_event( time(), 'hourly', 'dbclnr_cron_tasks' );
    }
    if ( !wp_next_scheduled( 'dbclnr_cron_analytics' ) ) {
      wp_schedule_event( time(), 'twicedaily', 'dbclnr_cron_analytics' );
    }

    $sweeper_enabled = $this->core->get_option( 'sweeper_enabled' );
    if ( $sweeper_enabled ) {
      $sweeper_schedule = $this->core->get_option( 'sweeper_schedule' );
      if ( !wp_next_scheduled( 'dbclnr_cron_sweeper' ) ) {
        wp_schedule_event( time(), $sweeper_schedule, 'dbclnr_cron_sweeper' );
      }
    }

    add_filter( 'cron_schedules', array( $this, 'schedules' ) );
    add_action( 'dbclnr_cron_tasks', array( $this, 'tasks' ) );
    add_action( 'dbclnr_cron_analytics', array( $this, 'analytics' ) );
    add_action( 'dbclnr_cron_sweeper', array( $this, 'sweeper' ) );
	}

  public function analytics() {
    //$this->core->log( "[Cron] Analytics started." );
    $this->core->refresh_database_size();
    //$this->core->log( "[Cron] Analytics finished." );
  }

  public function tasks() {
    //$this->core->log( "[Cron] Tasks started." );
    //$this->core->refresh_database_size();
    //$this->core->log( "[Cron] Tasks finished." );
  }

  public function schedules( $schedules ) {
    $schedules['dbclnr_5mn'] = array(
      'interval' => 300, // 5 minutes in seconds
      'display' => __( 'Every 5 Minutes', 'database-cleaner' )
    );
    $schedules['dbclnr_10mn'] = array(
      'interval' => 600, // 10 minutes in seconds
      'display' => __( 'Every 10 Minutes', 'database-cleaner' )
    );
    $schedules['dbclnr_30mn'] = array(
      'interval' => 1800, // 30 minutes in seconds
      'display' => __( 'Every 30 Minutes', 'database-cleaner' )
    );
    return $schedules;
  }

  public function sweeper() {
    //$this->core->log( "[Cron] Sweeper started." );
    
    // Check if sweeper has been running too long and reset if stuck
    $sweeper_tasks = $this->core->get_option( 'sweeper_tasks' );
    $reset_after_hours = intval( $this->core->get_option( 'sweeper_stuck_reset', 10 ) ); // Default to 10 hours if not set

    if ( isset( $sweeper_tasks['status'] ) && $sweeper_tasks['status'] === 'running' &&
        isset( $sweeper_tasks['last_execution'] ) ) {

      $last_execution = new DateTime( $sweeper_tasks['last_execution'] );
      $now = new DateTime();
      $diff = $now->diff( $last_execution );
      $hours_running = $diff->h + ( $diff->days * 24 );

      if ( $hours_running > $reset_after_hours ) {
        $this->core->log( "[Cron] Sweeper appears stuck (running for {$hours_running} hours), resetting to completed state." );
        $reset_tasks = array_merge( $sweeper_tasks, [
          'status' => 'completed',
          'next_action' => 'reset',
          'last_execution' => $now->format( 'Y-m-d H:i:s' ),
        ]);

        $this->core->update_options( array_merge( $this->core->get_all_options(), [ 'sweeper_tasks' => $reset_tasks ] ) );
      }
    }
    
    $res = apply_filters( 'dbclnr_sweeper_run_next', [
			'success' => false,
			'data' => null,
			'message' => __( 'Feature is not available.', 'database-cleaner' ),
		] );
    if ( $res['success'] ) {
      $this->core->log( "[Cron] Sweeper ran successfully." );
    }
    else {
      if ( !isset( $res['message'] ) ) {
        $res['message'] = __( 'Unknown error.', 'database-cleaner' );
      }
      $this->core->log( "[Cron] Sweeper failed: " . $res['message'] );
    }
  }
}

// TODO: WE should do this when the plugin is desactivated
// wp_clear_scheduled_hook( 'dbclnr_cron' );