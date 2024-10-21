<?php
/**
 * Class for merging CSS from all uploaded fonts
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
 * Class MergeCss_ECIcons
 *
 * This class extends the ECIcons class and is responsible for merging CSS files.
 *
 * @package custom-icons-for-elementor
 * @subpackage includes
 */
class MergeCss_ECIcons extends ECIcons {

	/**
	 * Constructor for the class.
	 *
	 * This method initializes the class and sets up any necessary properties or methods.
	 */
	public function __construct() {
		$this->generate_css();
		$this->generate_json();
	}

	/**
	 * Generate new CSS
	 */
	private function generate_css() {

		$options = get_option( 'ec_icons_fonts' );

		$css_content = "i.eci { 
			display: block;
    		font: normal normal normal 14px/1 FontAwesome;
    		font-size: inherit;
    		text-rendering: auto;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
     	}
     	.select2-container i.eci,
     	.elementor-icon-list-icon i.eci {
     	  display: inline-block;
     	}
     	.elementor-icons-manager__tab__item__icon.eci {
    		font-size: 28px;
		}
		.elementor-icons-manager__tab-link i.eci {
    		display: inline-block;
    		font-size: 18px;
		}\n";
		if ( ! empty( $options ) && is_array( $options ) ) {
			foreach ( $options as $key => $font ) {

				if ( isset( $font['status'] ) && '1' !== $font['status'] ) {
					continue;
				}

				if ( empty( $font['data'] ) ) {
					continue;
				}

				$font_data = json_decode( $font['data'], true );

				if ( isset( $font_data['nameempty'] ) && true == $font_data['nameempty'] ) {
					$fontfilename = 'fontello';
				} else {
					$fontfilename = strtolower( $font_data['name'] );
				}

				$randomver    = wp_rand();
				$css_content .= "@font-face {
						 font-family: '" . strtolower( $font_data['name'] ) . "';
						  src: url('" . $font_data['font_url'] . '/' . $fontfilename . '.eot?' . $randomver . "');
						  src: url('" . $font_data['font_url'] . '/' . $fontfilename . '.eot?' . $randomver . "#iefix') format('embedded-opentype'),
						       url('" . $font_data['font_url'] . '/' . $fontfilename . '.woff2?' . $randomver . "') format('woff2'),
						       url('" . $font_data['font_url'] . '/' . $fontfilename . '.woff?' . $randomver . "') format('woff'),
						       url('" . $font_data['font_url'] . '/' . $fontfilename . '.ttf?' . $randomver . "') format('truetype'),
						       url('" . $font_data['font_url'] . '/' . $fontfilename . '.svg?' . $randomver . '#' . $fontfilename . "') format('svg');
						  font-weight: normal;
						  font-style: normal;
						}\n";

				$icons = $this->parse_css( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );

				if ( ! empty( $icons ) && is_array( $icons ) ) {

					foreach ( $icons as $name_icon => $code ) {
						$css_content .= '.eci.' . $name_icon . "::before { content: '\\" . $code . "'; font-family: '" . strtolower( $font_data['name'] ) . "'; }\n";

					}
				}

				$css_content .= "\n\n";

			}
		}

		$css_content = preg_replace( '/\t+/', '', $css_content );
		if ( is_dir( (string) ec_icons_manager()->upload_dir ) ) {
			global $wp_filesystem;
			if ( ! function_exists( 'WP_Filesystem' ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
			}
			WP_Filesystem();
			$wp_filesystem->put_contents( ec_icons_manager()->upload_dir . '/merged-icons-font.css', $css_content );
			update_option( 'eci_css_timestamp', time(), true );
		} else {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Error saving Fontello CSS file to: ' . ec_icons_manager()->upload_dir );
			}
		}
	}

	/**
	 * Generate new JSON
	 */
	private function generate_json() {

		$options = get_option( 'ec_icons_fonts' );

		if ( ! empty( $options ) && is_array( $options ) ) {
			foreach ( $options as $key => $font ) {

				if ( isset( $font['status'] ) && '1' !== $font['status'] ) {
					continue;
				}

				if ( empty( $font['data'] ) ) {
					continue;
				}

				$font_data = json_decode( $font['data'], true );

				$icons = $this->parse_css( $font_data['css_root'], $font_data['name'], $font_data['css_url'] );

				if ( ! empty( $icons ) && is_array( $icons ) ) {

					$json          = array();
					$json['icons'] = array();

					foreach ( $icons as $name_icon => $code ) {
						$json['icons'][] = $name_icon;

					}
				}

				if ( is_dir( (string) ec_icons_manager()->upload_dir ) ) {
					global $wp_filesystem;
					if ( ! function_exists( 'WP_Filesystem' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}
					WP_Filesystem();
					$wp_filesystem->put_contents( ec_icons_manager()->upload_dir . '/' . $font_data['name'] . '.json', wp_json_encode( $json ) );
				}
			}
		}
	}
}
