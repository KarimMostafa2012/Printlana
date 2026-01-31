<?php

namespace WPML\TM\Jobs\Log;

use WPML\TM\Jobs\JobLog;

class Hooks implements \IWPML_Backend_Action, \IWPML_DIC_Action {

	const SUBMENU_HANDLE = 'wpml-tm-job-log';

	/** @var ViewFactory $viewFactory */
	private $viewFactory;

	public function __construct( ViewFactory $viewFactory ) {
		$this->viewFactory = $viewFactory;
	}

	public function add_hooks() {
		add_action( 'admin_menu', [ $this, 'addLogSubmenuPage' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueScripts' ] );
		add_action( 'wp_ajax_wpml_tm_job_log_toggle_feature', [ $this, 'handleAjaxToggle' ] );
		add_action( 'wp_ajax_wpml_tm_job_log_clear', [ $this, 'handleAjaxClear' ] );
		add_action( 'wp_ajax_wpml_tm_job_log_download', [ $this, 'handleAjaxDownload' ] );
	}

	public function addLogSubmenuPage() {
		$x = WPML_PLUGIN_FOLDER . '/menu/support.php';
		add_submenu_page(
			WPML_PLUGIN_FOLDER . '/menu/support.php',
			__( 'Translation Management Job Logs', 'wpml-translation-management' ),
			'TM job logs',
			'manage_options',
			self::SUBMENU_HANDLE,
			[ $this, 'renderPage' ]
		);
	}

	public function renderPage() {
		$this->viewFactory->create()->renderPage();
	}

	public function enqueueScripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === self::SUBMENU_HANDLE ) {
			wp_enqueue_style(
				'wpml-tm-job-log',
				WPML_TM_URL . '/res/css/job-log.css',
				array(),
				ICL_SITEPRESS_SCRIPT_VERSION
			);

			wp_enqueue_script(
				'support-tm-logs',
				WPML_TM_URL . '/res/js/support-tm-logs.js',
				array( 'jquery' ),
				ICL_SITEPRESS_SCRIPT_VERSION,
				true
			);
			wp_localize_script(
				'support-tm-logs',
				'wpmlTmJobLog',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wpml_tm_job_log' ),
				)
			);
		}
	}

	public function handleAjaxToggle() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wpml_tm_job_log' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$enabled = isset( $_POST['enabled'] ) ? (bool) intval( $_POST['enabled'] ) : false;
		JobLog::setIsEnabled( $enabled );

		wp_send_json_success( array(
			'enabled' => $enabled,
		) );
	}

	public function handleAjaxClear() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wpml_tm_job_log' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		$result = JobLog::clearLogs();

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Logs cleared successfully', 'sitepress' ),
			) );
		} else {
			wp_send_json_error( __( 'Failed to clear logs', 'sitepress' ) );
		}
	}

	public function handleAjaxDownload() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wpml_tm_job_log' ) ) {
			wp_die( 'Invalid nonce' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions' );
		}
		if (
			isset($_POST['loguid']) &&
			preg_match('/^[0-9a-f]{13}$/i', $_POST['loguid'])
		) {
			$logUid = $_POST['loguid'];
		} else {
			wp_die( 'Wrong loguid.' );
		}

		$logs = JobLog::getLogs();
		$log  = null;
		foreach ( $logs as $logItem ) {
			if ( $logItem['logUid'] === $logUid ) {
				$log = $logItem;
				break;
			}
		}

		if ( ! $log ) {
			wp_die( 'Log not found' );
		}

		$text = $this->generateLogText( $log );

		$filename = 'job-log-' . sanitize_file_name( $log['requestDateTime'] ) . '.txt';

		// Set headers for download
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Cache-Control: no-cache, must-revalidate' );
		header( 'Expires: 0' );

		echo $text;
		exit;
	}

	private function generateLogText( $log ) {
		$text = "";

		// Header
		$text .= "========================================\n";
		$text .= "WPML Translation Management Job Log\n";
		$text .= "========================================\n\n";

		// Request info
		$text .= "Date/Time: " . $log['requestDateTime'] . "\n";
		$text .= "URL: " . $log['requestUrl'] . "\n\n";

		// Request parameters
		$text .= "Request Parameters:\n";
		$text .= "------------------\n";
		$text .= wp_json_encode( $log['requestParams'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n\n";

		// Process log groups
		if ( ! empty( $log['logsByGroup'] ) && is_array( $log['logsByGroup'] ) ) {
			$action_num = 0;
			foreach ( $log['logsByGroup'] as $group ) {
				$action_num++;
				$text .= "========================================\n";
				$text .= "Action " . $action_num . ": " . $group['label'] . "\n";
				$text .= "========================================\n\n";

				if ( ! empty( $group['logs'] ) && is_array( $group['logs'] ) ) {
					$step_num = 0;
					foreach ( $group['logs'] as $log_item ) {
						$step_num++;
						$text .= "Step " . $step_num . ": " . ( $log_item['id'] ?? 'N/A' ) . "\n";
						$text .= str_repeat( '-', 40 ) . "\n";

						if ( isset( $log_item['data'] ) ) {
							$text .= wp_json_encode( $log_item['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n\n";
						}
					}
				}
			}
		}

		$text .= "========================================\n";
		$text .= "End of Log\n";
		$text .= "========================================\n";

		return $text;
	}
}
