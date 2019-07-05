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

class SaveFont_ECIcons extends ECIcons {


	public function init() {

		$action = $this->getRequest( 'action' );

		// ajax events
		if ( ! empty( $action ) && is_callable( array( $this, $action ) ) ) {
			add_action( 'wp_ajax_' . $action, array( $this, $action ) );
		}
	}


	/**
	 * Upload ZIP file
	 *
	 */
	public function ec_icons_save_font() {

		if ( wp_verify_nonce( $this->getRequest( '_wpnonce' ), 'ec_icons_nonce' ) && current_user_can( 'manage_options' ) ) {

			if ( ! class_exists( 'ZipArchive' ) ) {
				$result['status_save'] = 'failedopen';
				echo json_encode( $result );
				die();
			}

			$file_name = $this->getRequest( 'file_name', 'font' );

			$result = array();

			if ( ! empty( $_FILES ) && ! empty( $_FILES['source_file'] ) ) {

				$zip = new ZipArchive;
				$res = $zip->open( $_FILES['source_file']['tmp_name'] );
				if ( $res === true ) {
					$ex = $zip->extractTo( $this->upload_dir . '/' . $file_name );
					$zip->close();
					if ( $ex === false ) {
						$result['status_save'] = 'failedextract';
						echo json_encode( $result );
						die();
					}
				} else {
					$result['status_save'] = 'failedopen';
					echo json_encode( $result );
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
						$iconlist .= '<div><i class="eci ' . $iconkey . '" style="font-size: 16px;"></i><span>' . $iconkey . '</span></div>';
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

			echo json_encode( $result );
		}

		die();

	}

	/**
	 * Update Options table
	 *
	 * @param array $font_data
	 * @param string $status
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
			'data'   => json_encode( $font_data ),
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

			// remove option
			unset( $options[ $file_name ] );

			// remove file
			$this->rrmdir( ec_icons_manager()->upload_dir . '/' . $data['file_name'] );
			unlink( ec_icons_manager()->upload_dir . '/' . $data['name'] . '.json' );

			$result = array(
				'name'        => $file_name,
				'status_save' => 'none',
			);

			if ( update_option( 'ec_icons_fonts', $options ) ) {
				$result['status_save'] = 'remove';

				new MergeCss_ECIcons();
			}

			echo json_encode( $result );

		} else {

			$result = array(
				'status_save' => 'deletefailed',
			);

			echo json_encode( $result );

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

				if ( ! $font_data ) continue;

				$newoptions[ $font_data['name'] ] = array(
					'status' => '1',
					'data'   => json_encode( $font_data ),
				);

			}
			update_option( 'ec_icons_fonts', $newoptions );

		}

		new MergeCss_ECIcons();

		$result                 = array();
		$result['status_regen'] = 'regen';
		echo json_encode( $result );

		die();
	}


}
