<?php namespace U2Code\OrderMessenger\Settings;

use WC_Admin_Settings;

class AllowSendingFilesOption {

	const FIELD_TYPE = 'oc_allow_files';
	const FIELD_ID = 'allow_send_files';

	public function __construct() {
		add_action( 'woocommerce_admin_field_' . self::FIELD_TYPE, array( $this, 'render' ) );
		add_action( 'woocommerce_admin_settings_sanitize_option_' . Settings::SETTINGS_PREFIX . self::FIELD_ID, array(
			$this,
			'sanitize'
		), 3, 10 );
	}

	public function render( $value ) {

		$field_description = WC_Admin_Settings::get_field_description( $value );
		$description       = $field_description['description'];
		$option_value      = $value['value'];
		$visibility_class  = array();

		$option_value['files']    = isset( $option_value['files'] ) ? (array) $option_value['files'] : array();
		$option_value['filesize'] = isset( $option_value['filesize'] ) ? $option_value['filesize'] : min( $this->getMaxUploadSizeServer( true ), 5 );
		$option_value['enabled']  = isset( $option_value['enabled'] ) ? $option_value['enabled'] : 'yes';

		?>
		<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
			<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
			<td class="forminp forminp-checkbox">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
					<label for="<?php echo esc_attr( $value['id'] ); ?>">
						<input
								name="<?php echo esc_attr( $value['id'] ); ?>[enabled]"
								id="<?php echo esc_attr( $value['id'] ); ?>"
								type="checkbox"
								data-om-extra-settings-controll-checkbox
								class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
								value="1"
							<?php checked( $option_value['enabled'], 'yes' ); ?>
						/> <?php echo esc_html( $description ); ?>
					</label> <?php echo wc_help_tip( $value['desc'] ); ?>
				</fieldset>

				<div data-om-extra-settings data-order-messenger-premium-option>
					<fieldset style="display: flex" data->
						<legend style="margin-bottom: 10px">
							<span><?php esc_attr_e( 'Allowed file types', 'order-messenger-for-woocommerce::' ); ?>:</span>
						</legend>

						<?php foreach ( $this->getAvailableFileTypes() as $fileName => $type ) : ?>
							<div style="padding: 5px 10px; border: 1px solid #ccc; margin-right: 10px;">
								<label for="<?php echo esc_attr( $value['id'] . '-filetype-' . $fileName ); ?>">
									<?php echo esc_html( $type['title'] ); ?>
								</label>
								<input <?php echo checked( array_key_exists( $fileName, $option_value['files'] ), true ); ?>
										type="checkbox"
										id="<?php echo esc_attr( $value['id'] . '-filetype-' . $fileName ); ?>"
										value="1"
										name="<?php echo esc_attr( $value['id'] . '[files][' . $fileName . ']' ); ?>"
								>
							</div>
						<?php endforeach; ?>
					</fieldset>

					<fieldset style="margin-top: 10px">
						<legend style="margin-bottom: 10px">
							<span><?php esc_attr_e( 'Maximum allowed file size', 'order-messenger-for-woocommerce' ); ?> <b>MB</b>:</span>
						</legend>

						<div>
							<label for="<?php echo esc_attr( $value['id'] ) . '-filesize'; ?>">

							</label>
							<input style="width: 100px" type="number" step="any"
								   id="<?php echo esc_attr( $value['id'] ) . '-filesize'; ?>"
								   name="<?php echo esc_attr( $value['id'] ) . '[filesize]'; ?>"
								   value="<?php echo esc_attr( $option_value['filesize'] ); ?>"
								   max="<?php echo esc_attr( $this->getMaxUploadSizeServer( true ) ); ?>"
								   min="0.1"
							>
							<p class="description">
								<?php esc_html_e( 'Please note that the largest filesize that can be uploaded to your WordPress installation is', 'order-messenger-for-woocommerce' ); ?>
								<b> <?php echo esc_html( size_format( $this->getMaxUploadSizeServer() ) ); ?>. </b>
								<?php esc_html_e( 'Contact your hosting provider to increase this value if needed.', 'order-messenger-for-woocommerce' ); ?>
							</p>
						</div>
					</fieldset>
				</div>
			</td>
		</tr>
		<?php
	}

	public function getAvailableFileTypes() {

		$availableFileTypes = array(
			'jpeg' => array(
				'title' => 'JPG/JPEG',
				'mime'  => 'image/jpeg'
			),
			'png'  => array(
				'title' => 'PNG',
				'mime'  => 'image/png'
			),
			'gif'  => array(
				'title' => 'GIF',
				'mime'  => 'image/gif'
			),
			'pdf'  => array(
				'title' => 'PDF',
				'mime'  => 'application/pdf'
			),
			'doc'  => array(
				'title' => 'DOC',
				'mime'  => 'application/msword'
			),
			'docx' => array(
				'title' => 'DOCX',
				'mime'  => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
			),
			'zip'  => array(
				'title' => 'ZIP',
				'mime'  => 'application/zip'
			),
		);

		return apply_filters( 'order_messenger/settings/available_file_types', $availableFileTypes );
	}

	public function getWooCommerceArrayFormat() {
		return array(
			'title'    => __( 'Allow sending files', 'order-messenger-for-woocommerce' ),
			'id'       => Settings::SETTINGS_PREFIX . self::FIELD_ID,
			'type'     => self::FIELD_TYPE,
			'default'  => $this->getDefaults(),
			'desc'     => __( 'Allow users to attach a file.',
				'order-messenger-for-woocommerce' ),
			'desc_tip' => true,
			'custom_attributes' => [ 'data-order-messenger-premium-option' => true ],
		);
	}

	public function getDefaults() {

		return array(
			'enabled'  => 'yes',
			'filesize' => min( $this->getMaxUploadSizeServer( true ), 5 ),
			'files'    => array_map( function ( $fileType ) {
				return 'yes';
			}, $this->getAvailableFileTypes() ),
		);
	}

	public function sanitize( $value ) {
		$value['enabled']  = isset( $value['enabled'] ) ? 'yes' : 'no';
		$value['filesize'] = isset( $value['filesize'] ) ? floatVal( $value['filesize'] ) : min( $this->getMaxUploadSizeServer( true ), 5 );
		$value['filesize'] = max( 0.1, $value['filesize'] );

		foreach ( $this->getAvailableFileTypes() as $fileName => $file ) {
			if ( ! empty( $value['files'][ $fileName ] ) ) {
				$value['files'][ $fileName ] = 'yes';
			}
		}

		return $value;
	}

	public function getMaxUploadSizeServer( $inMB = false ) {
		static $max_size = - 1;

		if ( $max_size < 0 ) {

			$post_max_size = $this->parseFileSize( ini_get( 'post_max_size' ) );

			if ( $post_max_size > 0 ) {
				$max_size = $post_max_size;
			}

			$upload_max = $this->parseFileSize( ini_get( 'upload_max_filesize' ) );

			if ( $upload_max > 0 && $upload_max < $max_size ) {
				$max_size = $upload_max;
			}
		}
		if ( $inMB ) {
			return floor( $max_size / 1024 / 1024 );
		}

		return $max_size;
	}

	protected function parseFileSize( $size ) {
		$unit = preg_replace( '/[^bkmgtpezy]/i', '', $size );
		$size = preg_replace( '/[^0-9\.]/', '', $size );
		if ( $unit ) {
			return round( $size * pow( 1024, stripos( 'bkmgtpezy', $unit[0] ) ) );
		} else {
			return round( $size );
		}
	}
}
