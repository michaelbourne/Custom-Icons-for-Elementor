<?php
/**
 * Class for saving font uploads
 *
 * @package   Elementor Custom icons
 * @author    Michael Bourne
 * @license   GPL3
 * @link      https://ursa6.com
 * @since     0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}

/**
 * SaveFont_ECIcons
 */
class SaveFont_ECIcons extends ECIcons {


	/**
	 * Register AJAX handlers.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_' . ECICONS_ACTION_SAVE_FONT, array( $this, 'ec_icons_save_font' ) );
		add_action( 'wp_ajax_' . ECICONS_ACTION_DELETE_FONT, array( $this, 'ec_icons_delete_font' ) );
		add_action( 'wp_ajax_' . ECICONS_ACTION_REGENERATE_FONT, array( $this, 'ec_icons_regenerate' ) );
	}

	/**
	 * Validate nonce and capability for AJAX write endpoints.
	 *
	 * @return bool
	 */
	private function can_manage_icons() {
		check_ajax_referer( 'ec_icons_nonce' );
		return current_user_can( 'manage_options' );
	}



	/**
	 * Saves the uploaded font file and processes its icons.
	 *
	 * This method verifies the nonce and user permissions before proceeding. It checks if the ZipArchive class
	 * exists and handles the uploaded font file. The font file is extracted, and its icons are parsed and saved.
	 * The method returns a JSON response indicating the status of the operation.
	 *
	 * @return void
	 */
	public function ec_icons_save_font() {
		if ( ! $this->can_manage_icons() ) {
			wp_send_json_error( array( 'status_save' => 'invalid_permissions' ) );
		}

		if ( ! class_exists( 'ZipArchive' ) ) {
			wp_send_json_error( array( 'status_save' => 'failedopen' ) );
		}

		$file_name = str_replace( '.zip', '', $this->get_request( 'file_name', 'font' ) );
		$file_name = sanitize_file_name( $file_name );

		if (
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce validated in can_manage_icons().
			empty( $_FILES )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce validated in can_manage_icons().
			|| empty( $_FILES['source_file'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce validated in can_manage_icons().
			|| empty( $_FILES['source_file']['name'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce validated in can_manage_icons().
			|| empty( $_FILES['source_file']['tmp_name'] )
		) {
			wp_send_json_error( array( 'status_save' => 'emptyfile' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce validated in can_manage_icons().
		$sanitized_file_name = sanitize_file_name( wp_unslash( (string) $_FILES['source_file']['name'] ) );
		$file_type           = wp_check_filetype( $sanitized_file_name );
		if ( 'zip' !== $file_type['ext'] ) {
			wp_send_json_error( array( 'status_save' => 'invalidfiletype' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce validated in can_manage_icons(); temp path is validated by is_uploaded_file().
		$tmp_file = $_FILES['source_file']['tmp_name'];
		if ( ! is_uploaded_file( $tmp_file ) ) {
			wp_send_json_error( array( 'status_save' => 'invalidfile' ) );
		}

		$zip = new ZipArchive();
		$res = $zip->open( $tmp_file );
		if ( true !== $res ) {
			wp_send_json_error( array( 'status_save' => 'failedopen' ) );
		}

		$allowed_extensions = array( 'eot', 'svg', 'ttf', 'woff', 'woff2', 'css', 'json' );
		$extract_dir        = $this->upload_dir . '/' . $file_name;
		$extract_root       = realpath( $this->upload_dir );
		if ( false === $extract_root ) {
			$extract_root = $this->upload_dir;
		}

		if ( ! is_dir( $extract_dir ) ) {
			wp_mkdir_p( $extract_dir );
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- ZipArchive uses camelCase property name.
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$stat             = $zip->statIndex( $i );
			$file_name_in_zip = $stat['name'];
			$file_extension   = strtolower( pathinfo( $file_name_in_zip, PATHINFO_EXTENSION ) );

			if ( ! in_array( $file_extension, $allowed_extensions, true ) ) {
				continue;
			}

			$relative_path = ltrim( str_replace( '\\', '/', $file_name_in_zip ), '/' );
			$target_path   = $extract_dir . '/' . $relative_path;
			$target_dir    = dirname( $target_path );

			if ( ! is_dir( $target_dir ) ) {
				wp_mkdir_p( $target_dir );
			}

			$real_target_dir = realpath( $target_dir );
			if ( false === $real_target_dir || false === $extract_root || strpos( $real_target_dir, $extract_root ) !== 0 ) {
				continue;
			}

			if ( ! copy( 'zip://' . $tmp_file . '#' . $file_name_in_zip, $target_path ) ) {
				$zip->close();
				wp_send_json_error( array( 'status_save' => 'failedextract' ) );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod -- File extracted directly from uploaded ZIP.
			chmod( $target_path, 0644 );
		}

		$zip->close();

		if ( count( glob( $extract_dir . '/*', GLOB_NOSORT ) ) === 0 ) {
			wp_send_json_error( array( 'status_save' => 'failedextract' ) );
		}

		$font_data = $this->get_config_font( $file_name );
		if ( false === $font_data ) {
			wp_send_json_error( array( 'status_save' => 'emptyfile' ) );
		}

		$icons = $this->get_icon_map_from_font_data( $font_data );
		if ( empty( $icons ) || ! is_array( $icons ) ) {
			wp_send_json_error( array( 'status_save' => 'emptyfile' ) );
		}

		$result                = array();
		$result['count_icons'] = count( $icons );
		$result['first_icon']  = ! empty( $icons ) ? key( $icons ) : '';
		$iconlist              = '';
		foreach ( $icons as $iconkey => $iconcode ) {
			$iconlist .= '<div><i class="eci ' . esc_attr( $iconkey ) . '" style="font-size: 16px;"></i><span>' . esc_html( $iconkey ) . '</span></div>';
		}
		$result['iconlist']    = $iconlist;
		$result['name']        = $font_data['name'];
		$result['data']        = $font_data;
		$result['status_save'] = $this->update_options( $font_data, '1' );

		new MergeCss_ECIcons();

		if ( 'updated' !== $result['status_save'] ) {
			wp_send_json_error( $result );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Update Options table
	 *
	 * @param array  $font_data Font data.
	 * @param string $status Status.
	 * @return null|string
	 */
	private function update_options( $font_data, $status ) {

		if ( empty( $font_data['name'] ) ) {
			return null;
		}

		$options = get_option( 'ec_icons_fonts', array() );
		if ( ! empty( $options[ $font_data['name'] ] ) ) {
			return 'exist';
		}

		if ( empty( $options ) || ! is_array( $options ) ) {
			$options = array();
		}

		$options[ $font_data['name'] ] = array(
			'status' => $status,
			'data'   => wp_json_encode( $font_data ),
		);

		if ( update_option( 'ec_icons_fonts', $options ) ) {
			return 'updated';
		} else {
			return 'updatefailed';
		}
	}

	/**
	 * Delete ZIP file
	 */
	public function ec_icons_delete_font() {
		if ( ! $this->can_manage_icons() ) {
			wp_send_json_error( array( 'status_save' => 'deletefailed' ) );
		}

		$file_name = $this->get_request( 'file_name', 'font' );
		$options   = get_option( 'ec_icons_fonts' );
		if ( empty( $options[ $file_name ] ) ) {
			wp_send_json_error( array( 'status_save' => 'deletefailed' ) );
		}

		$data = json_decode( $options[ $file_name ]['data'], true );
		if ( ! is_array( $data ) || empty( $data['file_name'] ) || empty( $data['name'] ) ) {
			wp_send_json_error( array( 'status_save' => 'deletefailed' ) );
		}

		$upload_dir    = (string) ec_icons_manager()->upload_dir;
		$real_upload   = realpath( $upload_dir );
		$candidate_dir = realpath( $upload_dir . '/' . str_replace( '.zip', '', $data['file_name'] ) );
		$candidate_old = realpath( $upload_dir . '/' . $data['file_name'] );

		if ( $candidate_dir && $real_upload && strpos( $candidate_dir, $real_upload ) === 0 ) {
			$this->rrmdir( $candidate_dir );
		} elseif ( $candidate_old && $real_upload && strpos( $candidate_old, $real_upload ) === 0 ) {
			$this->rrmdir( $candidate_old );
		}

		wp_delete_file( $upload_dir . '/' . $data['name'] . '.json' );
		unset( $options[ $file_name ] );

		$result = array(
			'name'        => $file_name,
			'status_save' => 'none',
		);

		if ( update_option( 'ec_icons_fonts', $options ) ) {
			$result['status_save'] = 'remove';
			new MergeCss_ECIcons();
			wp_send_json_success( $result );
		}

		wp_send_json_error( array( 'status_save' => 'deletefailed' ) );
	}

	/**
	 * Regenerate CSS file
	 */
	public function ec_icons_regenerate() {
		if ( ! $this->can_manage_icons() ) {
			wp_send_json_error( array( 'status_regen' => 'failed' ) );
		}

		$options = get_option( 'ec_icons_fonts' );

		if ( ! empty( $options ) && is_array( $options ) ) {

			$newoptions = array();

			foreach ( $options as $key => $font ) {

				if ( empty( $font['data'] ) ) {
					continue;
				}

				$font_decode = json_decode( $font['data'], true );

				$font_data = $this->get_config_font( $font_decode['file_name'] );

				if ( ! $font_data ) {
					continue;
				}

				$newoptions[ $font_data['name'] ] = array(
					'status' => '1',
					'data'   => wp_json_encode( $font_data ),
				);

			}
			update_option( 'ec_icons_fonts', $newoptions );

		}

		new MergeCss_ECIcons();

		wp_send_json_success(
			array(
				'status_regen' => 'regen',
			)
		);
	}
}
