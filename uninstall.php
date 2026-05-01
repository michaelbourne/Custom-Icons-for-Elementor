<?php
/**
 * Plugin uninstall cleanup for Custom Icons for Elementor.
 *
 * @package CustomIconsForElementor
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	die;
}

/**
 * Recursively remove a directory and all files.
 *
 * @param string $dir Directory path.
 * @return void
 */
function ecicons_rrmdir( $dir ) {

	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( '.' !== $object && '..' !== $object ) {
				if ( is_dir( $dir . '/' . $object ) ) {
					ecicons_rrmdir( $dir . '/' . $object );
				} else {
					wp_delete_file( $dir . '/' . $object );
				}
			}
		}
		global $wp_filesystem;
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem();
		if ( $wp_filesystem ) {
			$wp_filesystem->rmdir( $dir, true );
		}
	}
}

$upload     = wp_upload_dir();
$upload_dir = $upload['basedir'] . '/elementor_icons_files';

$options = get_option( 'ec_icons_fonts' );

if ( ! empty( $options ) && is_array( $options ) ) {

	foreach ( $options as $key => $font ) {

		if ( empty( $font['data'] ) ) {
			continue;
		}

		$font_decode = json_decode( $font['data'], true );

		ecicons_rrmdir( $upload_dir . '/' . $font_decode['file_name'] );
		if ( file_exists( $upload_dir . '/' . $font_decode['name'] . '.json' ) ) {
			wp_delete_file( $upload_dir . '/' . $font_decode['name'] . '.json' );
		}
	}
}
wp_delete_file( $upload_dir . '/merged-icons-font.css' );
delete_option( 'ec_icons_fonts' );
