<?php namespace U2Code\OrderMessenger\Services;

use Exception;
use U2Code\OrderMessenger\Config\Config;

class FileUploader {
	
	public static function upload( $file, $orderId = null ) {

		self::validate( $file );

		$wordpress_upload_dir = wp_upload_dir();
		$fileExtension        = pathinfo( $file['name'], PATHINFO_EXTENSION );
		$fileName             = wp_generate_password( 12, false ) . '.' . $fileExtension;
		$filePath             = $wordpress_upload_dir['path'] . '/' . $fileName;

		$fileMine = mime_content_type( $file['tmp_name'] );

		while ( file_exists( $filePath ) ) {
			$fileName = wp_generate_password( 12, false ) . '.' . $fileExtension;
			$filePath = $wordpress_upload_dir['path'] . '/' . $fileName;
		}
		
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}
		
		if ( move_uploaded_file( $file['tmp_name'], $filePath ) ) {

			$imageId = wp_insert_attachment( array(
				'guid'           => $filePath,
				'post_mime_type' => $fileMine,
				'post_title'     => preg_replace( '/\.[^.]+$/', '', $file['name'] ) . '.' . $fileExtension,
				'post_content'   => '',
				'post_status'    => 'private',
			), $filePath );

			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			wp_update_attachment_metadata( $imageId, wp_generate_attachment_metadata( $imageId, $filePath ) );

			return $imageId;
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		$moveFile = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( $moveFile && ! isset( $moveFile['error'] ) ) {

			$attachment = array(
				'guid'           => $moveFile['url'],
				'post_mime_type' => $file['type'],
				'post_status'    => 'inherit',
			);

			$imageId = wp_insert_attachment( $attachment, $file['name'], $orderId );

			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			$attach_data = wp_generate_attachment_metadata( $imageId, $file['name'] );
			wp_update_attachment_metadata( $imageId, $attach_data );

			return $imageId;

		} else {
			throw new Exception( esc_html( $moveFile['error'] ) );
		}

		throw new Exception( 'File: something went wrong' );
	}

	protected static function validate( $file ) {

		$maxsize = Config::getMaxFileSize( true );

		$acceptable = array_column( Config::getEnabledFileFormats(), 'mime' );

		if ( ( $file['size'] >= $maxsize ) || ( 0 == $file['size'] ) ) {
			throw new Exception( esc_html( 'File too large. File must be less than ' . size_format( $maxsize ) . '.' ) );
		}

		if ( ! in_array( $file['type'], $acceptable ) || empty( $file['type'] ) ) {
			throw new Exception( 'Invalid file type.' );
		}

		return true;
	}

}
