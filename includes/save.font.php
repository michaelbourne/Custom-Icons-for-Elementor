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
	 * Initializes the class by setting up AJAX actions based on the request.
	 *
	 * This method retrieves the 'action' parameter from the request and checks if it corresponds
	 * to a callable method within the class. If so, it registers the method as an AJAX action
	 * using the 'wp_ajax_' hook.
	 *
	 * @return void
	 */
	public function init() {

		$action = $this->getRequest( 'eci_action' );

		// ajax events.
		if ( ! empty( $action ) && is_callable( array( $this, $action ) ) ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}
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

		if ( wp_verify_nonce( $this->getRequest( '_wpnonce' ), 'ec_icons_nonce' ) && current_user_can( 'manage_options' ) ) {

			if ( ! class_exists( 'ZipArchive' ) ) {
				$result['status_save'] = 'failedopen';
				echo wp_json_encode( $result );
				die();
			}

			$file_name = str_replace( '.zip', '', $this->getRequest( 'file_name', 'font' ) );

			$result = array();

			if (
				! empty( $_FILES )
				&& ! empty( $_FILES['source_file'] )
				&& ! empty( $_FILES['source_file']['name'] )
				&& ! empty( $_FILES['source_file']['tmp_name'] )
			 ) {

				// Sanitize file name
				$sanitized_file_name = sanitize_file_name( $_FILES['source_file']['name'] );

				// Check file type.
				$file_type = wp_check_filetype( $sanitized_file_name );
				if ( 'zip' !== $file_type['ext'] ) {
						$result['status_save'] = 'invalidfiletype';
						echo wp_json_encode( $result );
						die();
				}

				// Validate the temporary file
				$tmp_file = $_FILES['source_file']['tmp_name'];
				if ( ! is_uploaded_file( $tmp_file ) ) {
						$result['status_save'] = 'invalidfile';
						echo wp_json_encode( $result );
						die();
				}

				$zip = new ZipArchive();
				$res = $zip->open( $tmp_file );

				if ( true === $res ) {
					$allowed_extensions = array( 'eot', 'svg', 'ttf', 'woff', 'woff2', 'css', 'json' );
					$extract_dir        = $this->upload_dir . '/' . sanitize_file_name( $file_name );

					if ( ! is_dir( $extract_dir ) ) {
						mkdir( $extract_dir, 0755, true );
					}

					for ( $i = 0; $i < $zip->numFiles; $i++ ) {
						$stat             = $zip->statIndex( $i );
						$file_name_in_zip = $stat['name'];
						$file_extension   = strtolower( pathinfo( $file_name_in_zip, PATHINFO_EXTENSION ) );

						// Skip unsupported files
						if ( ! in_array( $file_extension, $allowed_extensions, true ) ) {
							continue;
						}

						// Generate the target path, keeping the original folder structure
						$relative_path = ltrim( $file_name_in_zip, '/' ); // Remove leading slashes
						$target_path   = $extract_dir . '/' . $relative_path;

						// Create the directory structure if it doesn't exist
						$target_dir = dirname( $target_path );
						if ( ! is_dir( $target_dir ) ) {
							mkdir( $target_dir, 0755, true );
						}

						// Ensure no path traversal (the extracted path should stay within the intended directory)
						if ( strpos( realpath( $target_dir ), realpath( $extract_dir ) ) === 0 ) {
							// Copy the file to the target location
							if ( ! copy( 'zip://' . $tmp_file . '#' . $file_name_in_zip, $target_path ) ) {
								$result['status_save'] = 'failedcopy';
								echo wp_json_encode( $result );
								die();
							}
							chmod( $target_path, 0644 ); // Secure file permissions
						}
					}

					$zip->close();

					// Verify if any files were extracted successfully
					if ( count( glob( $extract_dir . '/*', GLOB_NOSORT ) ) === 0 ) {
						$result['status_save'] = 'failedextract';
						echo wp_json_encode( $result );
						die();
					}
				} else {
					$result['status_save'] = 'failedopen';
					echo wp_json_encode( $result );
					die();
				}

				$font_data = $this->get_config_font( $file_name );

				$icons = $this->parse_css( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );

				if ( ! empty( $icons ) && is_array( $icons ) ) {
					$result['count_icons'] = count( $icons );
					$first_icon            = ! empty( $icons ) ? key( $icons ) : '';
					$result['first_icon']  = $first_icon;
					$iconlist              = '';
					foreach ( $icons as $iconkey => $iconcode ) {
						$iconlist .= '<div><i class="eci ' . esc_attr( $iconkey ) . '" style="font-size: 16px;"></i><span>' . esc_html( $iconkey ) . '</span></div>';
					}
					$result['iconlist'] = $iconlist;

					$result['name']        = $font_data['name'];
					$result['status_save'] = $this->update_options( $font_data, '1' );
					$result['data']        = $font_data;

					new MergeCss_ECIcons();
				} else {
					$result['status_save'] = 'emptyfile';
				}
			} else {
				$result['status_save'] = 'emptyfile';
			}

			echo wp_json_encode( $result );
		}

		die();
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

		if ( wp_verify_nonce( $this->getRequest( '_wpnonce' ), 'ec_icons_nonce' ) && current_user_can( 'manage_options' ) ) {

			$file_name = $this->getRequest( 'file_name', 'font' );

			$options = get_option( 'ec_icons_fonts' );

			if ( empty( $options[ $file_name ] ) ) {
				return false;
			}

			$data = json_decode( $options[ $file_name ]['data'], true );

			// Validate and sanitize file paths.
			$upload_dir = (string) ec_icons_manager()->upload_dir;
			$file_path  = realpath( $upload_dir . '/' . $data['file_name'] );
			$json_path  = realpath( $upload_dir . '/' . $data['name'] . '.json' );

			// Ensure the file paths are within the upload directory.
			if ( strpos( $file_path, $upload_dir ) !== 0 || strpos( $json_path, $upload_dir ) !== 0 ) {
					$result = array(
						'status_save' => 'deletefailed',
						'message'     => 'Invalid file path',
					);
					echo wp_json_encode( $result );
					die();
			}

			// remove option.
			unset( $options[ $file_name ] );

			// remove file.
			$dir_path = ec_icons_manager()->upload_dir . '/' . str_replace( '.zip', '', $data['file_name'] );
			if ( is_dir( $dir_path ) ) {
				$this->rrmdir( $dir_path );
			} elseif ( is_dir( ec_icons_manager()->upload_dir . '/' . $data['file_name'] ) ) {
				// fallback for previous versions.
				$this->rrmdir( ec_icons_manager()->upload_dir . '/' . $data['file_name'] );
			}
			wp_delete_file( ec_icons_manager()->upload_dir . '/' . $data['name'] . '.json' );

			$result = array(
				'name'        => $file_name,
				'status_save' => 'none',
			);

			if ( update_option( 'ec_icons_fonts', $options ) ) {
				$result['status_save'] = 'remove';

				new MergeCss_ECIcons();
			}

			echo wp_json_encode( $result );

		} else {

			$result = array(
				'status_save' => 'deletefailed',
			);

			echo wp_json_encode( $result );

		}

		die();
	}

	/**
	 * Regenerate CSS file
	 */
	public function ec_icons_regenerate() {

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

		$result                 = array();
		$result['status_regen'] = 'regen';
		echo wp_json_encode( $result );

		die();
	}


}
